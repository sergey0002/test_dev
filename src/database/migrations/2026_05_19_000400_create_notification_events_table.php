<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->index();
            $table->string('reason', 128)->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['notification_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
