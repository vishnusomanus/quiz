<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'entity',
        'entity_id',
        'user_id',
        'change_log',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'change_log' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
