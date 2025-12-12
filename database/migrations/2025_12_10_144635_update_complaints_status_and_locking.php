// database/migrations/xxxx_update_complaints_status_and_locking.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Change status enum
            DB::statement("ALTER TABLE complaints MODIFY COLUMN status ENUM('new', 'in_progress', 'finished', 'declined') DEFAULT 'new'");

            // Add locking mechanism
            $table->timestamp('locked_at')->nullable()->after('assigned_to');
            $table->timestamp('lock_expires_at')->nullable()->after('locked_at');

            // Add info request tracking
            $table->boolean('info_requested')->default(false)->after('lock_expires_at');
            $table->text('info_request_message')->nullable()->after('info_requested');
            $table->timestamp('info_requested_at')->nullable()->after('info_request_message');

            // Add version tracking for optimistic locking
            $table->integer('version')->default(1)->after('info_requested_at');

            $table->index(['status', 'entity_id']);
            $table->index('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'locked_at',
                'lock_expires_at',
                'info_requested',
                'info_request_message',
                'info_requested_at',
                'version'
            ]);
        });
    }
};
