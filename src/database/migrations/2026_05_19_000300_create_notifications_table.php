<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('notification_batches')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('subscribers')->restrictOnDelete();
            $table->string('channel', 16)->index();
            $table->string('type', 32)->index();
            $table->text('message_snapshot');
            $table->string('status', 32)->index();
            $table->unsignedSmallInteger('priority');
            $table->string('provider_message_id', 128)->nullable();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->string('last_error_code', 64)->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'subscriber_id', 'channel']);
            $table->index(['subscriber_id', 'created_at']);
            $table->index(['priority', 'queued_at']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
