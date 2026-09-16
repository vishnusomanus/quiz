<?php

namespace App\Models;

use App\Enums\QuizAttemptStatus;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'description',
        'time_limit_seconds',
        'per_question_time_seconds',
        'is_published',
        'created_by',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'time_limit_seconds' => 'integer',
            'per_question_time_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $quiz): void {
            if (empty($quiz->uuid)) {
                $quiz->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function countries(): HasMany
    {
        return $this->hasMany(QuizCountry::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * @param  Builder<Quiz>  $query
     * @return Builder<Quiz>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Quiz>  $query
     * @return Builder<Quiz>
     */
    public function scopeForCountry(Builder $query, ?string $country): Builder
    {
        if ($country === null || $country === '') {
            return $query;
        }

        $country = strtoupper($country);

        return $query->where(function (Builder $builder) use ($country): void {
            $builder->whereDoesntHave('countries')
                ->orWhereHas('countries', function (Builder $countryQuery) use ($country): void {
                    $countryQuery->where('country_code', $country);
                });
        });
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', [QuizAttemptStatus::Completed, QuizAttemptStatus::Expired])
            ->exists();
    }
}
