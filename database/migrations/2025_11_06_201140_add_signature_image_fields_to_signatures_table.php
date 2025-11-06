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
        Schema::table('signatures', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->text('description')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('image_url');
            $table->integer('image_width')->nullable()->after('image_path');
            $table->integer('image_height')->nullable()->after('image_width');
            $table->integer('file_size')->nullable()->after('image_height');
            $table->string('mime_type')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'image_path',
                'image_width',
                'image_height',
                'file_size',
                'mime_type',
            ]);
        });
    }
};
