// database/migrations/xxxx_remove_unnecessary_fields_from_complaints_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
        });
    }
};
