<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying ENUM columns directly
        // So we'll recreate the table with the new ENUM values

        // Step 1: Create temporary table with new structure
        Schema::create('new_member_info_temp', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('category', ['general', 'rules', 'benefits', 'schedule', 'equipment', 'faq', 'employee_manual']);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('order');
        });

        // Step 2: Copy existing data
        DB::statement('INSERT INTO new_member_info_temp SELECT * FROM new_member_info');

        // Step 3: Drop old table
        Schema::dropIfExists('new_member_info');

        // Step 4: Rename temp table to original name
        Schema::rename('new_member_info_temp', 'new_member_info');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table without employee_manual
        Schema::create('new_member_info_temp', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('category', ['general', 'rules', 'benefits', 'schedule', 'equipment', 'faq']);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('order');
        });

        // Copy data (excluding employee_manual entries)
        DB::statement('INSERT INTO new_member_info_temp SELECT * FROM new_member_info WHERE category != "employee_manual"');

        Schema::dropIfExists('new_member_info');
        Schema::rename('new_member_info_temp', 'new_member_info');
    }
};
