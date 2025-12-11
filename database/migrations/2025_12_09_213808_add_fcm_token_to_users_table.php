<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FCM tokens are typically 152-163 characters
            // We'll use 255 to be safe, but won't index it due to MySQL limitations
            $table->string('fcm_token', 255)->nullable()->after('email');

            // Don't add index - FCM tokens are too long for MySQL index
            // We don't need to search by FCM token anyway, only store and retrieve
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
