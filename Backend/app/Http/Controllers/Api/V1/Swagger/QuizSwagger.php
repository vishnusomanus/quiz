<?php

namespace App\Http\Controllers\Api\V1\Swagger;

/**
 * @OA\Tag(
 *     name="Quizzes",
 *     description="Quiz listing, details, admin CRUD, submissions, and leaderboards",
 * )
 * @OA\Tag(
 *     name="Quiz Attempts",
 *     description="Start, resume, and attend a quiz one question at a time",
 * )
 *
 * @OA\Schema(
 *     schema="QuizListItem",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid", example="a80a7ee9-3294-4c0d-b646-9fe59fd63a1b"),
 *     @OA\Property(property="title", type="string", example="India General Knowledge"),
 *     @OA\Property(property="description", type="string", example="A short quiz about India."),
 *     @OA\Property(property="time_limit_seconds", type="integer", nullable=true, example=180),
 *     @OA\Property(property="per_question_time_seconds", type="integer", nullable=true, example=30),
 *     @OA\Property(property="countries", type="array", @OA\Items(type="string", example="IN")),
 *     @OA\Property(property="question_count", type="integer", example=3),
 *     @OA\Property(property="is_completed", type="boolean", example=false, description="Present on the public customer list"),
 *     @OA\Property(property="in_progress_attempt_id", type="string", format="uuid", nullable=true, description="Present on the public customer list when the user has an unfinished attempt"),
 *     @OA\Property(property="completed_attempt_id", type="string", format="uuid", nullable=true, description="Present on the public customer list when the user already finished the quiz"),
 *     @OA\Property(property="is_published", type="boolean", example=true, description="Present on the admin catalog list")
 * )
 *
 * @OA\Schema(
 *     schema="QuizOption",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="label", type="string", example="New Delhi"),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true, example="https://flagcdn.com/w160/in.png"),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="is_correct", type="boolean", description="Included for admins only")
 * )
 *
 * @OA\Schema(
 *     schema="QuizQuestion",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="prompt", type="string", example="What is the capital of India?"),
 *     @OA\Property(property="type", type="string", enum={"single_choice", "multiple_choice"}, example="single_choice"),
 *     @OA\Property(property="points", type="integer", example=1),
 *     @OA\Property(property="time_limit_seconds", type="integer", nullable=true, example=30),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="options", type="array", @OA\Items(ref="#/components/schemas/QuizOption"))
 * )
 *
 * @OA\Schema(
 *     schema="QuizDetail",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="title", type="string", example="India General Knowledge"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="time_limit_seconds", type="integer", nullable=true, example=180),
 *     @OA\Property(property="per_question_time_seconds", type="integer", nullable=true, example=30),
 *     @OA\Property(property="is_published", type="boolean", example=true),
 *     @OA\Property(property="countries", type="array", @OA\Items(type="string", example="IN")),
 *     @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/QuizQuestion"))
 * )
 *
 * @OA\Schema(
 *     schema="QuizWriteOption",
 *     required={"is_correct"},
 *
 *     @OA\Property(property="id", type="string", format="uuid", description="Existing option UUID when updating"),
 *     @OA\Property(property="label", type="string", example="Paris", description="Optional when image_url is provided"),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true, example="https://flagcdn.com/w160/fr.png"),
 *     @OA\Property(property="is_correct", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="QuizWriteQuestion",
 *     required={"prompt", "options"},
 *
 *     @OA\Property(property="id", type="string", format="uuid", description="Existing question UUID when updating"),
 *     @OA\Property(property="prompt", type="string", example="What is the capital of France?"),
 *     @OA\Property(property="type", type="string", enum={"single_choice", "multiple_choice"}, example="single_choice"),
 *     @OA\Property(property="points", type="integer", example=1),
 *     @OA\Property(property="time_limit_seconds", type="integer", nullable=true, example=20),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="options", type="array", @OA\Items(ref="#/components/schemas/QuizWriteOption"))
 * )
 *
 * @OA\Schema(
 *     schema="QuizAttemptAnswer",
 *     type="object",
 *
 *     @OA\Property(property="question_id", type="string", format="uuid"),
 *     @OA\Property(property="selected_option_ids", type="array", @OA\Items(type="string", format="uuid")),
 *     @OA\Property(property="is_correct", type="boolean", nullable=true, example=true),
 *     @OA\Property(property="points_awarded", type="integer", example=1),
 *     @OA\Property(property="time_taken_seconds", type="integer", nullable=true, example=8)
 * )
 *
 * @OA\Schema(
 *     schema="QuizAttempt",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="quiz_id", type="string", format="uuid"),
 *     @OA\Property(property="status", type="string", enum={"in_progress", "completed", "expired"}, example="in_progress"),
 *     @OA\Property(property="score", type="integer", nullable=true, example=2),
 *     @OA\Property(property="time_taken_seconds", type="integer", nullable=true, example=42),
 *     @OA\Property(property="remaining_seconds", type="integer", nullable=true, example=179),
 *     @OA\Property(property="current_question_remaining_seconds", type="integer", nullable=true, example=28),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="progress",
 *         type="object",
 *         @OA\Property(property="answered", type="integer", example=1),
 *         @OA\Property(property="total", type="integer", example=3)
 *     ),
 *     @OA\Property(property="current_question", ref="#/components/schemas/QuizQuestion", nullable=true),
 *     @OA\Property(property="answers", type="array", @OA\Items(ref="#/components/schemas/QuizAttemptAnswer"))
 * )
 *
 * @OA\Schema(
 *     schema="LeaderboardEntry",
 *     type="object",
 *
 *     @OA\Property(property="rank", type="integer", example=1),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="user_name", type="string", example="Admin User"),
 *     @OA\Property(property="score", type="integer", example=3),
 *     @OA\Property(property="time_taken_seconds", type="integer", example=42),
 *     @OA\Property(property="completed_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *
 *     @OA\Property(property="total", type="integer", example=3),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="next", type="integer", example=0),
 *     @OA\Property(property="prev", type="integer", example=0)
 * )
 */
class QuizSwagger
{
    /**
     * @OA\Get(
     *     path="/v1/quizzes",
     *     tags={"Quizzes"},
     *     summary="List quizzes",
     *     description="Public: pass user_id to list published quizzes for that user's country (worldwide quizzes included). Each item includes is_completed, in_progress_attempt_id, and completed_attempt_id. Admin: send a Passport Bearer token and omit user_id to list all quizzes, including unpublished ones. Each admin item includes is_published. Optional country and is_published filters apply to the admin catalog.",
     *     security={
     *         {},
     *         {"passport":{}}
     *     },
     *
     *     @OA\Parameter(name="user_id", in="query", required=false, description="Public user UUID. Required unless authenticated as admin.", @OA\Schema(type="string", format="uuid", example="c7f14bca-9287-489d-b92d-743e78af13d6")),
     *     @OA\Parameter(name="country", in="query", required=false, description="ISO 3166-1 alpha-2 country code. Defaults to the user's stored country on the public list.", @OA\Schema(type="string", example="IN")),
     *     @OA\Parameter(name="is_published", in="query", required=false, description="Admin catalog only. Filter by published status.", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated quiz list",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/QuizListItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="array", @OA\Items(type="string"), example={"The user_id field is required."})
     *             )
     *         )
     *     )
     * )
     */
    public function index() {}

    /**
     * @OA\Get(
     *     path="/v1/quizzes/{quiz}",
     *     tags={"Quizzes"},
     *     summary="Get quiz details",
     *     description="Returns title, description, questions, and options. Correctness flags are included only for admins.",
     *     security={},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, description="Quiz UUID", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quiz detail",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizDetail")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Quiz not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Quiz not found")
     *         )
     *     )
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *     path="/v1/quizzes",
     *     tags={"Quizzes"},
     *     summary="Create a quiz",
     *     description="Admin-only. Assign a quiz to one or more countries; omit countries for a worldwide quiz.",
     *     security={{"passport":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title","questions"},
     *
     *             @OA\Property(property="title", type="string", example="World Capitals"),
     *             @OA\Property(property="description", type="string", example="Know your capitals"),
     *             @OA\Property(property="time_limit_seconds", type="integer", example=180),
     *             @OA\Property(property="per_question_time_seconds", type="integer", example=30),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="countries", type="array", @OA\Items(type="string", example="IN")),
     *             @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/QuizWriteQuestion"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Quiz created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizDetail")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store() {}

    /**
     * @OA\Put(
     *     path="/v1/quizzes/{quiz}",
     *     tags={"Quizzes"},
     *     summary="Replace a quiz",
     *     description="Admin-only. Send the full quiz payload, including countries and questions, to replace it.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", example="World Capitals"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="time_limit_seconds", type="integer", example=180),
     *             @OA\Property(property="per_question_time_seconds", type="integer", example=30),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="countries", type="array", @OA\Items(type="string", example="US")),
     *             @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/QuizWriteQuestion"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quiz updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizDetail")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Quiz not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update() {}

    /**
     * @OA\Patch(
     *     path="/v1/quizzes/{quiz}",
     *     tags={"Quizzes"},
     *     summary="Partially update a quiz",
     *     description="Admin-only. Any omitted field is left unchanged. Sending questions replaces the full question set.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", example="World Capitals"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *             @OA\Property(property="per_question_time_seconds", type="integer", nullable=true),
     *             @OA\Property(property="is_published", type="boolean"),
     *             @OA\Property(property="countries", type="array", @OA\Items(type="string", example="IN")),
     *             @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/QuizWriteQuestion"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quiz updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizDetail")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Quiz not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function patch() {}

    /**
     * @OA\Delete(
     *     path="/v1/quizzes/{quiz}",
     *     tags={"Quizzes"},
     *     summary="Delete a quiz",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quiz deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz deleted successfully"),
     *             @OA\Property(property="data", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Post(
     *     path="/v1/quizzes/{quiz}/submit",
     *     tags={"Quizzes"},
     *     summary="Submit quiz responses",
     *     description="Learner-only. Scores a full set of answers. If the user has an in-progress attend attempt, remaining answers are applied and that attempt is finalized into the leaderboard. If the user already completed the quiz, the existing attempt is returned and a second attempt is not created.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"answers"},
     *
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     required={"question_id","option_ids"},
     *
     *                     @OA\Property(property="question_id", type="string", format="uuid"),
     *                     @OA\Property(property="option_ids", type="array", @OA\Items(type="string", format="uuid"))
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quiz submitted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz submitted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Quiz not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function submit() {}

    /**
     * @OA\Get(
     *     path="/v1/quizzes/{quiz}/leaderboard",
     *     tags={"Quizzes"},
     *     summary="Quiz leaderboard",
     *     description="Best score per user (then fastest completion time) from completed and expired attempts.",
     *     security={},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Leaderboard",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LeaderboardEntry")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function leaderboard() {}

    /**
     * @OA\Post(
     *     path="/v1/quizzes/{quiz}/attempts",
     *     tags={"Quiz Attempts"},
     *     summary="Start or resume a quiz attempt",
     *     description="Learner-only. Creates an in-progress attempt or resumes an existing one. If the user already completed or expired the quiz, that attempt is returned instead of starting a new one (retakes are not allowed). Serves the current question only while in progress.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="quiz", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attempt ready",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attempt ready"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function startAttempt() {}

    /**
     * @OA\Get(
     *     path="/v1/attempts/{attempt}",
     *     tags={"Quiz Attempts"},
     *     summary="Get current attempt state",
     *     description="Returns progress, remaining quiz/question time, and the current question. Timed-out questions are auto-scored as incorrect.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="attempt", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attempt state",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Resource not found")
     * )
     */
    public function showAttempt() {}

    /**
     * @OA\Post(
     *     path="/v1/attempts/{attempt}/answers",
     *     tags={"Quiz Attempts"},
     *     summary="Record an answer",
     *     description="Validates the current question, option ownership, and server-side time limits. Completing the last question (or hitting the quiz time limit) finalizes score and time for the submit/leaderboard APIs.",
     *     security={{"passport":{}}},
     *
     *     @OA\Parameter(name="attempt", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"question_id","option_ids"},
     *
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="option_ids", type="array", @OA\Items(type="string", format="uuid"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Answer recorded or quiz completed",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Answer recorded"),
     *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=409, description="Attempt is no longer in progress"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function answer() {}
}
