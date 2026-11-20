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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->timestamp('whatsapp_opt_in_at')->nullable();
            $table->timestamp('privacy_consent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'timezone',
                'whatsapp_opt_in_at',
                'privacy_consent_at',
            ]);
        });
    }
};
