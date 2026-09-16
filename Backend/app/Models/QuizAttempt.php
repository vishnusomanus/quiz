<?php

namespace App\Models;

use App\Enums\QuizAttemptStatus;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'quiz_id',
        'user_id',
        'status',
        'started_at',
        'completed_at',
        'expires_at',
        'current_question_served_at',
        'score',
        'time_taken_seconds',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'status' => QuizAttemptStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'current_question_served_at' => 'datetime',
            'score' => 'integer',
            'time_taken_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attempt): void {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === QuizAttemptStatus::InProgress;
    }

    public function remainingSeconds(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return max(0, $this->expires_at->getTimestamp() - now()->getTimestamp());
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now());
    }
}
