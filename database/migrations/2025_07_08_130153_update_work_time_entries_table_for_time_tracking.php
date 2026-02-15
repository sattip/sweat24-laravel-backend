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
        // Check if columns already exist
        if (!Schema::hasColumn('work_time_entries', 'status')) {
            Schema::table('work_time_entries', function (Blueprint $table) {
                $table->string('status')->default('in_progress');
            });
        }
        
        if (!Schema::hasColumn('work_time_entries', 'duration')) {
            Schema::table('work_time_entries', function (Blueprint $table) {
                $table->integer('duration')->nullable();
            });
        }
        
        if (!Schema::hasColumn('work_time_entries', 'notes')) {
            Schema::table('work_time_entries', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_time_entries', function (Blueprint $table) {
            $table->dropColumn(['status', 'duration', 'notes']);
        });
    }
};