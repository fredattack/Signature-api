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
        Schema::create('signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('celebrity_name', 100);
            $table->string('color', 7); // HEX color #FFFFFF
            $table->longText('image_data')->nullable(); // Base64 or SVG data
            $table->string('image_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('capture_date')->useCurrent();
            $table->json('device_info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('celebrity_name');
            $table->index('capture_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
