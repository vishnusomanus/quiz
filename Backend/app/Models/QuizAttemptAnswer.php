<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_option_ids',
        'is_correct',
        'points_awarded',
        'served_at',
        'answered_at',
        'time_taken_seconds',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
            'served_at' => 'datetime',
            'answered_at' => 'datetime',
            'time_taken_seconds' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
