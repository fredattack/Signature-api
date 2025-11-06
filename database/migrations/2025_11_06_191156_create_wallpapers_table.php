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
        Schema::create('wallpapers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('template_id')->constrained('wallpaper_templates')->onDelete('restrict');
            $table->string('wallpaper_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('resolution', 20); // 1080x1920
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->enum('generation_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('signature_id');
            $table->index('template_id');
            $table->index('generation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallpapers');
    }
};
