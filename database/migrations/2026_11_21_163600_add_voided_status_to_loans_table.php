<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('UNPAID', 'PARTIAL', 'PAID', 'VOIDED') NOT NULL DEFAULT 'UNPAID'");
    }

    public function down(): void
    {
        DB::statement("UPDATE loans SET status = 'PAID' WHERE status = 'VOIDED'");
        DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('UNPAID', 'PARTIAL', 'PAID') NOT NULL DEFAULT 'UNPAID'");
    }
};