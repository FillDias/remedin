<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_for');
            $table->string('status', 10)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->string('whatsapp_message_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scheduled_for']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
