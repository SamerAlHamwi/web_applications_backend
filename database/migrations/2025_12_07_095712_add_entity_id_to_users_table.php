// database/migrations/xxxx_xx_xx_add_entity_id_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add entity_id for employees
            $table->foreignId('entity_id')
                ->nullable()
                ->after('role')
                ->constrained('entities')
                ->nullOnDelete();

            $table->index('entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });
    }
};
