<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class QuizApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_list_requires_user_id(): void
    {
        $this->getJson('/api/v1/quizzes')
            ->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_quiz_list_filters_by_country_and_marks_completion(): void
    {
        $user = User::factory()->customer('IN')->create();
        $indiaQuiz = Quiz::factory()->forCountries(['IN'])->withQuestions(2)->create(['title' => 'India Quiz']);
        Quiz::factory()->forCountries(['US'])->withQuestions(2)->create(['title' => 'US Quiz']);
        Quiz::factory()->withQuestions(2)->create(['title' => 'Worldwide Quiz']);
        Quiz::factory()->unpublished()->withQuestions(2)->create(['title' => 'Hidden Quiz']);

        QuizAttempt::factory()->completed(2, 15)->create([
            'quiz_id' => $indiaQuiz->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/quizzes?user_id='.$user->uuid)
            ->assertOk()
            ->assertJsonPath('status', true);

        $titles = collect($response->json('data'))->pluck('title')->all();

        $this->assertContains('India Quiz', $titles);
        $this->assertContains('Worldwide Quiz', $titles);
        $this->assertNotContains('US Quiz', $titles);
        $this->assertNotContains('Hidden Quiz', $titles);

        $indiaItem = collect($response->json('data'))->firstWhere('title', 'India Quiz');
        $this->assertTrue($indiaItem['is_completed']);
        $this->assertNull($indiaItem['in_progress_attempt_id']);
        $this->assertNotNull($indiaItem['completed_attempt_id']);

        $worldwide = collect($response->json('data'))->firstWhere('title', 'Worldwide Quiz');
        $this->assertFalse($worldwide['is_completed']);
        $this->assertNull($worldwide['in_progress_attempt_id']);
        $this->assertNull($worldwide['completed_attempt_id']);

        $usFiltered = $this->getJson('/api/v1/quizzes?user_id='.$user->uuid.'&country=US')
            ->assertOk();

        $usTitles = collect($usFiltered->json('data'))->pluck('title')->all();
        $this->assertContains('US Quiz', $usTitles);
        $this->assertContains('Worldwide Quiz', $usTitles);
        $this->assertNotContains('India Quiz', $usTitles);
    }

    public function test_admin_can_list_all_quizzes_including_unpublished(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        Quiz::factory()->forCountries(['IN'])->withQuestions(1)->create(['title' => 'India Quiz']);
        Quiz::factory()->unpublished()->withQuestions(1)->create(['title' => 'Draft Quiz']);

        $response = $this->getJson('/api/v1/quizzes')
            ->assertOk()
            ->assertJsonPath('status', true);

        $items = collect($response->json('data'));
        $titles = $items->pluck('title')->all();

        $this->assertContains('India Quiz', $titles);
        $this->assertContains('Draft Quiz', $titles);

        $draft = $items->firstWhere('title', 'Draft Quiz');
        $this->assertFalse($draft['is_published']);
        $this->assertArrayNotHasKey('is_completed', $draft);
    }

    public function test_admin_can_filter_quiz_list_by_published_status(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        Quiz::factory()->withQuestions(1)->create(['title' => 'Live Quiz']);
        Quiz::factory()->unpublished()->withQuestions(1)->create(['title' => 'Draft Quiz']);

        $drafts = $this->getJson('/api/v1/quizzes?is_published=0')
            ->assertOk();

        $draftTitles = collect($drafts->json('data'))->pluck('title')->all();
        $this->assertContains('Draft Quiz', $draftTitles);
        $this->assertNotContains('Live Quiz', $draftTitles);
    }

    public function test_authenticated_customer_still_requires_user_id_to_list_quizzes(): void
    {
        $customer = User::factory()->customer('IN')->create();
        Passport::actingAs($customer, ['*'], 'api');

        $this->getJson('/api/v1/quizzes')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_quiz_detail_hides_correct_answers(): void
    {
        $quiz = Quiz::factory()->withQuestions(1)->create();
        $quiz->load('questions.options');

        $response = $this->getJson('/api/v1/quizzes/'.$quiz->uuid)
            ->assertOk()
            ->assertJsonPath('data.title', $quiz->title)
            ->assertJsonPath('data.questions.0.prompt', $quiz->questions->first()->prompt);

        $this->assertArrayNotHasKey('is_correct', $response->json('data.questions.0.options.0'));
        $this->assertArrayHasKey('image_url', $response->json('data.questions.0.options.0'));
    }

    public function test_unpublished_quiz_is_hidden_from_public_detail(): void
    {
        $quiz = Quiz::factory()->unpublished()->withQuestions(1)->create();

        $this->getJson('/api/v1/quizzes/'.$quiz->uuid)
            ->assertStatus(404)
            ->assertJsonPath('message', 'Quiz not found');
    }

    public function test_admin_can_create_update_and_delete_a_quiz_for_multiple_countries(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        $create = $this->postJson('/api/v1/quizzes', [
            'title' => 'Capitals',
            'description' => 'Know your capitals',
            'time_limit_seconds' => 120,
            'per_question_time_seconds' => 20,
            'countries' => ['IN', 'SG'],
            'questions' => [
                [
                    'prompt' => 'Capital of India?',
                    'type' => 'single_choice',
                    'points' => 2,
                    'options' => [
                        ['label' => 'Delhi', 'is_correct' => true],
                        ['label' => 'Mumbai', 'is_correct' => false],
                    ],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Capitals')
            ->assertJsonPath('data.countries.0', 'IN')
            ->assertJsonPath('data.questions.0.options.0.is_correct', true);

        $quizId = $create->json('data.id');

        $this->patchJson('/api/v1/quizzes/'.$quizId, [
            'title' => 'World Capitals',
            'countries' => ['IN', 'US'],
        ])->assertOk()
            ->assertJsonPath('data.title', 'World Capitals')
            ->assertJsonPath('data.countries.1', 'US');

        $this->deleteJson('/api/v1/quizzes/'.$quizId)
            ->assertOk()
            ->assertJsonPath('message', 'Quiz deleted successfully');

        $this->assertSoftDeleted('quizzes', ['uuid' => $quizId]);
    }

    public function test_non_admin_cannot_create_a_quiz(): void
    {
        $customer = User::factory()->customer('IN')->create();
        Passport::actingAs($customer, ['*'], 'api');

        $this->postJson('/api/v1/quizzes', [
            'title' => 'Nope',
            'questions' => [
                [
                    'prompt' => 'Q?',
                    'options' => [
                        ['label' => 'A', 'is_correct' => true],
                        ['label' => 'B', 'is_correct' => false],
                    ],
                ],
            ],
        ])->assertForbidden();
    }

    public function test_admin_cannot_attend_or_submit_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->withQuestions(1)->create(['title' => 'Admin Attend Quiz']);
        $quiz->load('questions.options');
        Passport::actingAs($admin, ['*'], 'api');

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertForbidden()
            ->assertJsonPath('message', 'Only learners can attend quizzes');

        $question = $quiz->questions->first();
        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/submit', [
            'answers' => [
                [
                    'question_id' => $question->uuid,
                    'option_ids' => [$this->correctOptionUuid($question)],
                ],
            ],
        ])->assertForbidden()
            ->assertJsonPath('message', 'Only learners can attend quizzes');
    }

    public function test_admin_can_view_leaderboard_for_any_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Quiz::factory()->unpublished()->withQuestions(1)->create();
        Passport::actingAs($admin, ['*'], 'api');

        $this->getJson('/api/v1/quizzes/'.$draft->uuid.'/leaderboard')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_submit_quiz_scores_answers_and_feeds_leaderboard(): void
    {
        $user = User::factory()->customer('IN')->create();
        $other = User::factory()->customer('IN')->create(['name' => 'Faster User']);
        $quiz = Quiz::factory()->withQuestions(2)->create();
        $quiz->load('questions.options');
        Passport::actingAs($user, ['*'], 'api');

        $answers = $quiz->questions->map(function (Question $question): array {
            return [
                'question_id' => $question->uuid,
                'option_ids' => [$this->correctOptionUuid($question)],
            ];
        })->all();

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/submit', [
            'answers' => $answers,
        ])->assertOk()
            ->assertJsonPath('data.status', QuizAttemptStatus::Completed->value)
            ->assertJsonPath('data.score', 2);

        QuizAttempt::factory()->completed(1, 5)->create([
            'quiz_id' => $quiz->id,
            'user_id' => $other->id,
        ]);

        $leaderboard = $this->getJson('/api/v1/quizzes/'.$quiz->uuid.'/leaderboard')
            ->assertOk();

        $this->assertSame($user->name, $leaderboard->json('data.0.user_name'));
        $this->assertSame(2, $leaderboard->json('data.0.score'));
        $this->assertSame($other->name, $leaderboard->json('data.1.user_name'));
        $this->assertArrayHasKey('time_taken_seconds', $leaderboard->json('data.0'));
    }

    public function test_attend_serves_one_question_at_a_time_and_resumes(): void
    {
        $user = User::factory()->customer('IN')->create();
        $quiz = Quiz::factory()->withQuestions(2)->create([
            'time_limit_seconds' => 300,
            'per_question_time_seconds' => 30,
        ]);
        $quiz->load('questions.options');
        Passport::actingAs($user, ['*'], 'api');

        $first = $quiz->questions[0];
        $second = $quiz->questions[1];

        $start = $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->assertJsonPath('data.status', QuizAttemptStatus::InProgress->value)
            ->assertJsonPath('data.current_question.id', $first->uuid)
            ->assertJsonPath('data.progress.total', 2);

        $this->assertArrayNotHasKey('is_correct', $start->json('data.current_question.options.0'));

        $attemptId = $start->json('data.id');

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->assertJsonPath('data.id', $attemptId)
            ->assertJsonPath('data.current_question.id', $first->uuid);

        $this->postJson('/api/v1/attempts/'.$attemptId.'/answers', [
            'question_id' => $second->uuid,
            'option_ids' => [$this->correctOptionUuid($second)],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'It is not this question\'s turn');

        $this->postJson('/api/v1/attempts/'.$attemptId.'/answers', [
            'question_id' => $first->uuid,
            'option_ids' => [$this->correctOptionUuid($first)],
        ])->assertOk()
            ->assertJsonPath('data.current_question.id', $second->uuid)
            ->assertJsonPath('data.progress.answered', 1);

        $this->postJson('/api/v1/attempts/'.$attemptId.'/answers', [
            'question_id' => $second->uuid,
            'option_ids' => [$this->correctOptionUuid($second)],
        ])->assertOk()
            ->assertJsonPath('data.status', QuizAttemptStatus::Completed->value)
            ->assertJsonPath('data.score', 2)
            ->assertJsonPath('data.current_question', null);

        $this->getJson('/api/v1/quizzes/'.$quiz->uuid.'/leaderboard')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $user->uuid)
            ->assertJsonPath('data.0.score', 2);

        $listed = collect($this->getJson('/api/v1/quizzes?user_id='.$user->uuid)->json('data'))
            ->firstWhere('id', $quiz->uuid);

        $this->assertTrue($listed['is_completed']);
        $this->assertSame($attemptId, $listed['completed_attempt_id']);
        $this->assertNull($listed['in_progress_attempt_id']);

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->assertJsonPath('data.id', $attemptId)
            ->assertJsonPath('data.status', QuizAttemptStatus::Completed->value);

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/submit', [
            'answers' => [
                [
                    'question_id' => $first->uuid,
                    'option_ids' => [$this->correctOptionUuid($first)],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.id', $attemptId)
            ->assertJsonPath('data.score', 2);
    }

    public function test_list_exposes_in_progress_attempt_for_resume(): void
    {
        $user = User::factory()->customer('IN')->create();
        $quiz = Quiz::factory()->withQuestions(2)->create();
        Passport::actingAs($user, ['*'], 'api');

        $attemptId = $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->json('data.id');

        $item = collect($this->getJson('/api/v1/quizzes?user_id='.$user->uuid)->json('data'))
            ->firstWhere('id', $quiz->uuid);

        $this->assertFalse($item['is_completed']);
        $this->assertSame($attemptId, $item['in_progress_attempt_id']);
        $this->assertNull($item['completed_attempt_id']);
    }

    public function test_blank_answer_skips_the_current_question(): void
    {
        $user = User::factory()->customer('IN')->create();
        $quiz = Quiz::factory()->withQuestions(2)->create();
        $quiz->load('questions');
        Passport::actingAs($user, ['*'], 'api');

        $first = $quiz->questions[0];
        $second = $quiz->questions[1];

        $attemptId = $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->json('data.id');

        $this->postJson('/api/v1/attempts/'.$attemptId.'/answers', [
            'question_id' => $first->uuid,
            'option_ids' => [],
        ])->assertOk()
            ->assertJsonPath('data.current_question.id', $second->uuid)
            ->assertJsonPath('data.progress.answered', 1);
    }

    public function test_per_question_time_limit_is_enforced_server_side(): void
    {
        $user = User::factory()->customer('IN')->create();
        $quiz = Quiz::factory()->withQuestions(2)->create([
            'time_limit_seconds' => 300,
            'per_question_time_seconds' => 10,
        ]);
        $quiz->load('questions.options');
        Passport::actingAs($user, ['*'], 'api');

        $first = $quiz->questions[0];
        $second = $quiz->questions[1];

        $attemptId = $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->json('data.id');

        $this->travel(11)->seconds();

        $this->postJson('/api/v1/attempts/'.$attemptId.'/answers', [
            'question_id' => $first->uuid,
            'option_ids' => [$this->correctOptionUuid($first)],
        ])->assertOk()
            ->assertJsonPath('data.current_question.id', $second->uuid)
            ->assertJsonPath('data.progress.answered', 1);

        $this->assertDatabaseHas('quiz_attempt_answers', [
            'is_correct' => false,
            'points_awarded' => 0,
        ]);
    }

    public function test_quiz_time_limit_expires_the_attempt(): void
    {
        $user = User::factory()->customer('IN')->create();
        $quiz = Quiz::factory()->withQuestions(2)->create([
            'time_limit_seconds' => 15,
            'per_question_time_seconds' => 30,
        ]);
        Passport::actingAs($user, ['*'], 'api');

        $attemptId = $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->json('data.id');

        $this->travel(16)->seconds();

        $this->getJson('/api/v1/attempts/'.$attemptId)
            ->assertOk()
            ->assertJsonPath('data.status', QuizAttemptStatus::Expired->value)
            ->assertJsonPath('data.score', 0);

        $this->getJson('/api/v1/quizzes?user_id='.$user->uuid)
            ->assertOk();

        $item = collect($this->getJson('/api/v1/quizzes?user_id='.$user->uuid)->json('data'))
            ->firstWhere('id', $quiz->uuid);

        $this->assertTrue($item['is_completed']);
        $this->assertSame($attemptId, $item['completed_attempt_id']);

        $this->postJson('/api/v1/quizzes/'.$quiz->uuid.'/attempts')
            ->assertOk()
            ->assertJsonPath('data.id', $attemptId)
            ->assertJsonPath('data.status', QuizAttemptStatus::Expired->value);
    }

    public function test_admin_can_create_a_quiz_with_image_options(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        $this->postJson('/api/v1/quizzes', [
            'title' => 'Flags',
            'questions' => [
                [
                    'prompt' => 'Which flag is India?',
                    'type' => 'single_choice',
                    'options' => [
                        [
                            'label' => 'India',
                            'image_url' => 'https://flagcdn.com/w160/in.png',
                            'is_correct' => true,
                        ],
                        [
                            'label' => '',
                            'image_url' => 'https://flagcdn.com/w160/us.png',
                            'is_correct' => false,
                        ],
                    ],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.questions.0.options.0.image_url', 'https://flagcdn.com/w160/in.png')
            ->assertJsonPath('data.questions.0.options.1.image_url', 'https://flagcdn.com/w160/us.png');
    }

    public function test_option_without_label_or_image_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        $this->postJson('/api/v1/quizzes', [
            'title' => 'Empty option',
            'questions' => [
                [
                    'prompt' => 'Choose one',
                    'options' => [
                        ['label' => 'Valid', 'is_correct' => true],
                        ['label' => '', 'image_url' => null, 'is_correct' => false],
                    ],
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_upload_an_option_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['*'], 'api');

        $file = UploadedFile::fake()->image('flag.png', 80, 50);

        $response = $this->post('/api/v1/uploads/option-images', [
            'image' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', true);

        $path = $response->json('data.path');
        $this->assertNotEmpty($path);
        $this->assertNotEmpty($response->json('data.url'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_non_admin_cannot_upload_an_option_image(): void
    {
        $customer = User::factory()->customer('IN')->create();
        Passport::actingAs($customer, ['*'], 'api');

        $this->post('/api/v1/uploads/option-images', [
            'image' => UploadedFile::fake()->image('flag.png'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    private function correctOptionUuid(Question $question): string
    {
        $question->loadMissing('options');

        return (string) $question->options->firstWhere('is_correct', true)?->uuid;
    }
}
