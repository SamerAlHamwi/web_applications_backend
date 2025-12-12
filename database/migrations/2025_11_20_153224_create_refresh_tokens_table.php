<?php


// database/migrations/xxxx_create_refresh_tokens_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('token');
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);
            $table->string('device_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_revoked']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refresh_tokens');
    }


    /**
     * Reverse the migrations.
     */

};
