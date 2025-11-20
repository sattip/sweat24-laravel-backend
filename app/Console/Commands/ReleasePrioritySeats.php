<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PriorityBookingService;
use App\Models\PriorityBookingSettings;
use Illuminate\Support\Facades\Log;

class ReleasePrioritySeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'priority:release-seats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release unused priority seats for upcoming classes';

    protected $priorityService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PriorityBookingService $priorityService)
    {
        parent::__construct();
        $this->priorityService = $priorityService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $settings = PriorityBookingSettings::getSettings();

        if (!$settings->priority_system_enabled) {
            $this->info('Priority booking system is disabled');
            return 0;
        }

        if (!$settings->auto_release_enabled) {
            $this->info('Auto-release is disabled');
            return 0;
        }

        $this->info('Checking for priority seats to release...');
        
        try {
            $releasedCount = $this->priorityService->releaseUpcomingPrioritySeats();
            
            $this->info("Released priority seats for {$releasedCount} classes");
            
            Log::info('Priority seats release completed', [
                'classes_updated' => $releasedCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error releasing priority seats: ' . $e->getMessage());
            
            Log::error('Priority seats release failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}