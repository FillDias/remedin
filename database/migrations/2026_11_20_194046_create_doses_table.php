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
        Schema::create('doses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_schedule_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_for');
            $table->decimal('amount', 8, 2);
            $table->string('status', 10)->default('pending');
            $table->timestamp('taken_at')->nullable();
            $table->timestamps();

            $table->unique(['medication_schedule_id', 'scheduled_for']);
            $table->index(['medication_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doses');
    }
};
