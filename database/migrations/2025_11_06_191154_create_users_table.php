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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password_hash')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('is_premium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
