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
        Schema::create('shift_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('time_tracking_id')->nullable()->constrained('time_trackings')->onDelete('set null');
            $table->enum('type', ['opening', 'closing']);

            // Cash register
            $table->enum('cash_counted', ['yes', 'no', 'na'])->nullable();
            $table->decimal('cash_amount', 10, 2)->nullable();

            // Inventory counts
            $table->enum('towels_checked', ['yes', 'no', 'na'])->nullable();
            $table->integer('towels_count')->nullable();
            $table->enum('water_checked', ['yes', 'no', 'na'])->nullable();
            $table->integer('water_count')->nullable();

            // Equipment
            $table->enum('equipment_checked', ['yes', 'no', 'na'])->nullable();
            $table->text('equipment_notes')->nullable();

            // Cleanliness & Organization
            $table->enum('area_tidy', ['yes', 'no', 'na'])->nullable();
            $table->enum('locker_rooms_checked', ['yes', 'no', 'na'])->nullable();
            $table->enum('showers_checked', ['yes', 'no', 'na'])->nullable();

            // Security (for closing)
            $table->enum('doors_locked', ['yes', 'no', 'na'])->nullable();
            $table->enum('lights_off', ['yes', 'no', 'na'])->nullable();
            $table->enum('ac_off', ['yes', 'no', 'na'])->nullable();
            $table->enum('alarm_set', ['yes', 'no', 'na'])->nullable();

            // General notes
            $table->text('notes')->nullable();
            $table->text('issues_reported')->nullable();

            $table->timestamp('completed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_checklists');
    }
};
