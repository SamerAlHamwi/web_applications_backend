// database/migrations/xxxx_xx_xx_create_complaint_attachments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // image or pdf
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->timestamps();

            $table->index(['complaint_id', 'file_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_attachments');
    }
};
