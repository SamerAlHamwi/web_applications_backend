// database/migrations/xxxx_xx_xx_create_complaints_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique(); // CMP-2025-000001
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Citizen
            $table->foreignId('entity_id')->constrained()->onDelete('cascade'); // Entity receiving
            $table->string('complaint_kind'); // Type/category of complaint
            $table->text('description');
            $table->string('location'); // Citizen's location
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('status', [
                'new',
                'under_review',
                'in_progress',
                'resolved',
                'rejected',
                'closed'
            ])->default('new');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Employee
            $table->text('admin_notes')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tracking_number');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['entity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
