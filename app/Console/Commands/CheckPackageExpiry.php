<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PackageNotificationService;
use App\Models\UserPackage;

class CheckPackageExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check package expiry dates and send notifications';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(PackageNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking package expiry dates...');

        // Update all package statuses
        $this->updatePackageStatuses();

        // Send expiry notifications
        $this->notificationService->checkAndSendExpiryNotifications();

        // Process auto-renewals
        $this->info('Processing auto-renewals...');
        $results = $this->notificationService->processAutoRenewals();
        
        $this->info("Auto-renewal results: {$results['success']} successful, {$results['failed']} failed out of {$results['total']} total");

        $this->info('Package expiry check completed!');
    }

    /**
     * Update all package statuses based on their expiry dates
     */
    protected function updatePackageStatuses()
    {
        $this->info('Updating package statuses...');

        // Get all non-expired, non-frozen packages
        $packages = UserPackage::where('is_frozen', false)
            ->whereNotIn('status', [UserPackage::STATUS_EXPIRED, UserPackage::STATUS_FROZEN])
            ->get();

        $updated = 0;
        foreach ($packages as $package) {
            $oldStatus = $package->status;
            $package->updateLifecycleStatus();
            
            if ($oldStatus !== $package->status) {
                $updated++;
            }
        }

        $this->info("Updated {$updated} package statuses");
    }
}