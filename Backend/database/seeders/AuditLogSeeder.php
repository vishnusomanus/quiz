<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Audit logs are written at runtime by AuditLogService.
     * No initial data required.
     */
    public function run(): void
    {
    }
}
