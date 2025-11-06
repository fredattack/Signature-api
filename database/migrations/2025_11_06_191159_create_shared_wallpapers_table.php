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
        Schema::create('shared_wallpapers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallpaper_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['facebook', 'twitter', 'instagram', 'other']);
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamps();

            $table->index('wallpaper_id');
            $table->index('user_id');
            $table->index('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_wallpapers');
    }
};
