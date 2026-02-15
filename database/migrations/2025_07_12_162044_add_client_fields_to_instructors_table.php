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
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
            $table->string('title')->nullable();
            $table->text('image_url')->nullable();
            $table->longText('bio')->nullable();
            $table->json('certifications')->nullable();
            $table->json('services')->nullable();
            $table->integer('display_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn(['slug', 'title', 'image_url', 'bio', 'certifications', 'services', 'display_order']);
        });
    }
};
