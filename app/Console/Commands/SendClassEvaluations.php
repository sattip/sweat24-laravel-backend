<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GymClass;
use App\Models\ClassEvaluation;
use Carbon\Carbon;

class SendClassEvaluations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluations:send {--days=1 : Number of days to look back for completed classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send evaluation links for completed classes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $startDate = Carbon::now()->subDays($days);
        
        // Find classes that ended in the specified time frame
        $completedClasses = GymClass::where('status', 'active')
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<', today())
            ->get()
            ->filter(function ($class) {
                // Check if class has ended (considering time and duration)
                $classEnd = Carbon::parse($class->date->format('Y-m-d') . ' ' . $class->time)
                    ->addMinutes($class->duration);
                return $classEnd->isPast();
            });
        
        $this->info("Found {$completedClasses->count()} completed classes");
        
        $totalCreated = 0;
        foreach ($completedClasses as $class) {
            // Get attendees
            $attendees = $class->bookings()
                ->where('status', 'completed')
                ->where('attended', true)
                ->get();
            
            $created = 0;
            foreach ($attendees as $booking) {
                // Check if evaluation already exists
                $existing = ClassEvaluation::where('booking_id', $booking->id)->first();
                if (!$existing) {
                    $evaluation = ClassEvaluation::create([
                        'class_id' => $class->id,
                        'booking_id' => $booking->id,
                        'sent_at' => now(),
                    ]);
                    
                    // In a real application, you would send an email here
                    // For now, we'll just display the link
                    $link = config('app.url') . '/evaluation/' . $evaluation->evaluation_token;
                    $this->line("Created evaluation for {$booking->customer_name}: {$link}");
                    
                    $created++;
                }
            }
            
            if ($created > 0) {
                $this->info("Created {$created} evaluations for class: {$class->name} ({$class->date->format('d/m/Y')})");
                $totalCreated += $created;
            }
        }
        
        $this->info("Total evaluations created: {$totalCreated}");
    }
}