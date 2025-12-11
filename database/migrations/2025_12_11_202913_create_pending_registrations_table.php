<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password'); // Hashed
            $table->string('role')->default('citizen');
            $table->string('code', 6); // 6-digit verification code
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['email', 'code']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
