<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GymClass;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CheckDataConsistency extends Command
{
    protected $signature = 'data:check-consistency {--fix : Fix any inconsistencies found}';
    protected $description = 'Check and optionally fix data consistency across the application';

    public function handle()
    {
        $this->info('🔍 Starting data consistency check...');
        
        $issues = [];
        
        // Check 1: GymClass current_participants consistency
        $this->info('Checking GymClass current_participants...');
        $classes = GymClass::all();
        
        foreach ($classes as $class) {
            $actualParticipants = Booking::where('class_id', $class->id)
                ->whereNotIn('status', ['cancelled', 'waitlist'])
                ->count();
                
            if ($class->current_participants !== $actualParticipants) {
                $issues[] = [
                    'type' => 'current_participants_mismatch',
                    'model' => 'GymClass',
                    'id' => $class->id,
                    'expected' => $actualParticipants,
                    'actual' => $class->current_participants,
                ];
                
                $this->warn("⚠️  Class {$class->id}: expected {$actualParticipants}, got {$class->current_participants}");
                
                if ($this->option('fix')) {
                    $class->update(['current_participants' => $actualParticipants]);
                    $this->info("✅ Fixed Class {$class->id} current_participants");
                }
            }
        }
        
        // Check 2: UserPackage remaining_sessions consistency
        $this->info('Checking UserPackage remaining_sessions...');
        $packages = UserPackage::where('status', 'active')->get();
        
        foreach ($packages as $package) {
            $usedSessions = Booking::where('user_id', $package->user_id)
                ->where('status', 'confirmed')
                ->where('created_at', '>=', $package->created_at)
                ->count();
                
            $expectedRemaining = max(0, $package->total_sessions - $usedSessions);
            
            if ($package->remaining_sessions !== $expectedRemaining) {
                $issues[] = [
                    'type' => 'remaining_sessions_mismatch',
                    'model' => 'UserPackage',
                    'id' => $package->id,
                    'user_id' => $package->user_id,
                    'expected' => $expectedRemaining,
                    'actual' => $package->remaining_sessions,
                ];
                
                $this->warn("⚠️  Package {$package->id} (User {$package->user_id}): expected {$expectedRemaining}, got {$package->remaining_sessions}");
                
                if ($this->option('fix')) {
                    $package->update(['remaining_sessions' => $expectedRemaining]);
                    $this->info("✅ Fixed Package {$package->id} remaining_sessions");
                }
            }
        }
        
        // Check 3: Orphaned bookings
        $this->info('Checking for orphaned bookings...');
        $orphanedBookings = Booking::whereDoesntHave('gymClass')
            ->orWhereDoesntHave('user')
            ->get();
            
        if ($orphanedBookings->count() > 0) {
            $issues[] = [
                'type' => 'orphaned_bookings',
                'count' => $orphanedBookings->count(),
                'ids' => $orphanedBookings->pluck('id')->toArray(),
            ];
            
            $this->warn("⚠️  Found {$orphanedBookings->count()} orphaned bookings");
            
            if ($this->option('fix')) {
                foreach ($orphanedBookings as $booking) {
                    $booking->update(['status' => 'cancelled']);
                }
                $this->info("✅ Cancelled all orphaned bookings");
            }
        }
        
        // Summary
        if (empty($issues)) {
            $this->info('🎉 No consistency issues found!');
        } else {
            $this->warn("🚨 Found " . count($issues) . " consistency issues");
            
            if (!$this->option('fix')) {
                $this->info('Run with --fix flag to automatically resolve issues');
            }
        }
        
        // Log results
        Log::info('Data consistency check completed', [
            'issues_found' => count($issues),
            'auto_fixed' => $this->option('fix'),
            'issues' => $issues,
            'timestamp' => now(),
        ]);
        
        return 0;
    }
}
