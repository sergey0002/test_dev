<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->unsignedInteger('attempt_no');
            $table->string('provider', 64)->index();
            $table->string('result', 32)->index();
            $table->string('provider_message_id', 128)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->unique(['notification_id', 'attempt_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_attempts');
    }
};
