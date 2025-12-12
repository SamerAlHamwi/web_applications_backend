<?php
// database/migrations/xxxx_xx_xx_add_employee_role_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the role enum to include 'employee'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citizen', 'company', 'admin', 'employee') NOT NULL DEFAULT 'citizen'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role enum back to original (remove 'employee')
        // WARNING: This will fail if any users have role='employee'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citizen', 'company', 'admin') NOT NULL DEFAULT 'citizen'");
    }
};
