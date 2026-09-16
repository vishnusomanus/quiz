<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(array $payload, ?Request $request = null): AuditLog
    {
        $request = $request ?? request();
        $data    = [
            'action'     => $payload['action']     ?? null,
            'entity'     => $payload['entity']     ?? null,
            'entity_id'  => $payload['entity_id']  ?? null,
            'user_id'    => $payload['user_id']    ?? Auth::id(),
            'change_log' => $payload['change_log'] ?? null,
            'ip_address' => $payload['ip_address'] ?? $request->ip(),
            'user_agent' => $payload['user_agent'] ?? $request->userAgent(),
        ];

        return AuditLog::query()->create($data);
    }
}
