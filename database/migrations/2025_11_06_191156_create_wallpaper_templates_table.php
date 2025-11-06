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
        Schema::create('wallpaper_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('preview_url');
            $table->string('category', 50);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_premium');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallpaper_templates');
    }
};
