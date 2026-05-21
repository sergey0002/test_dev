<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_batches', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 128)->unique();
            $table->string('payload_hash', 64);
            $table->string('channel', 16)->index();
            $table->string('type', 32)->index();
            $table->text('message');
            $table->unsignedInteger('requested_count');
            $table->unsignedInteger('accepted_count');
            $table->string('status', 32)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_batches');
    }
};
