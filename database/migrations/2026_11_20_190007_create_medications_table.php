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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('strength', 50)->nullable();
            $table->string('form', 20);
            $table->decimal('dose_amount', 8, 2);
            $table->string('dose_unit', 30);
            $table->string('meal_relation', 10)->default('any');
            $table->decimal('stock_quantity', 8, 2)->nullable();
            $table->unsignedSmallInteger('low_stock_days')->default(5);
            $table->timestamp('low_stock_notified_at')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
