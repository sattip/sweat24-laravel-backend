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
            $table->string('slug')->unique()->nullable()->after('name');
            $table->string('title')->nullable()->after('slug');
            $table->text('image_url')->nullable()->after('title');
            $table->longText('bio')->nullable()->after('image_url');
            $table->json('certifications')->nullable()->after('specialties');
            $table->json('services')->nullable()->after('certifications');
            $table->integer('display_order')->default(0)->after('status');
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
