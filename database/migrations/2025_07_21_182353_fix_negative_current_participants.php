<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix negative current_participants values by calculating actual counts from bookings
     */
    public function up(): void
    {
        // Get all gym classes with potentially incorrect current_participants counts
        $classes = DB::table('gym_classes')->get();
        
        foreach ($classes as $class) {
            // Calculate actual participants from bookings table
            $actualParticipants = DB::table('bookings')
                ->where('class_id', $class->id)
                ->whereNotIn('status', ['cancelled', 'waitlist'])
                ->count();
            
            // Update the class with correct count
            DB::table('gym_classes')
                ->where('id', $class->id)
                ->update(['current_participants' => $actualParticipants]);
        }
        
        // Log the fix for debugging
        \Log::info('🔧 Fixed current_participants counts for all gym classes', [
            'total_classes_updated' => $classes->count(),
            'timestamp' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need to be reversed
        // as it only fixes data consistency
    }
};
