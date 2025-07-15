<?php

namespace App\Services;

use App\Models\UserPackage;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class PackageNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check and send expiry notifications for all packages
     */
    public function checkAndSendExpiryNotifications()
    {
        // Get packages expiring in 7 days
        $this->sendNotificationsForDaysBefore(7, UserPackage::NOTIFICATION_7_DAYS);
        
        // Get packages expiring in 3 days
        $this->sendNotificationsForDaysBefore(3, UserPackage::NOTIFICATION_3_DAYS);
        
        // Get expired packages
        $this->sendExpiredNotifications();
    }

    /**
     * Send notifications for packages expiring in X days
     */
    protected function sendNotificationsForDaysBefore($days, $stage)
    {
        $packages = UserPackage::whereDate('expiry_date', now()->addDays($days)->toDateString())
            ->where(function ($query) use ($stage) {
                $query->whereNull('notification_stage')
                    ->orWhere('notification_stage', '!=', $stage);
            })
            ->where('status', '!=', UserPackage::STATUS_EXPIRED)
            ->with('user')
            ->get();

        foreach ($packages as $package) {
            $this->sendExpiryNotification($package, $days);
            
            // Update notification stage
            $package->update([
                'notification_stage' => $stage,
                'last_notification_sent_at' => now(),
            ]);
        }
    }

    /**
     * Send notifications for expired packages
     */
    protected function sendExpiredNotifications()
    {
        $packages = UserPackage::whereDate('expiry_date', '<', now())
            ->where('status', '!=', UserPackage::STATUS_EXPIRED)
            ->where('notification_stage', '!=', UserPackage::NOTIFICATION_EXPIRED)
            ->with('user')
            ->get();

        foreach ($packages as $package) {
            // Update status to expired
            $package->updateLifecycleStatus();
            
            // Send notification
            $this->sendExpiredNotification($package);
            
            // Update notification stage
            $package->update([
                'notification_stage' => UserPackage::NOTIFICATION_EXPIRED,
                'last_notification_sent_at' => now(),
            ]);
        }
    }

    /**
     * Send expiry notification
     */
    public function sendExpiryNotification(UserPackage $package, $daysUntilExpiry = null)
    {
        if ($daysUntilExpiry === null) {
            $daysUntilExpiry = $package->getDaysUntilExpiry();
        }

        $title = $daysUntilExpiry > 0 
            ? "Your {$package->name} package expires in {$daysUntilExpiry} days"
            : "Your {$package->name} package has expired";

        $message = $daysUntilExpiry > 0
            ? "Your {$package->name} package will expire on {$package->expiry_date->format('F j, Y')}. " .
              "You have {$package->remaining_sessions} sessions remaining. " .
              "Renew now to continue enjoying our services without interruption."
            : "Your {$package->name} package expired on {$package->expiry_date->format('F j, Y')}. " .
              "Renew now to continue enjoying our services.";

        try {
            // Create notification
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'package_expiry',
                'priority' => $daysUntilExpiry <= 3 ? 'high' : 'medium',
                'channels' => ['in_app', 'email'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$package->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            // Log successful notification
            $package->logNotification(
                $daysUntilExpiry > 0 ? "expiring_{$daysUntilExpiry}_days" : 'expired',
                'email',
                true
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package expiry notification', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            // Log failed notification
            $package->logNotification(
                $daysUntilExpiry > 0 ? "expiring_{$daysUntilExpiry}_days" : 'expired',
                'email',
                false,
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Send expired notification
     */
    public function sendExpiredNotification(UserPackage $package)
    {
        return $this->sendExpiryNotification($package, 0);
    }

    /**
     * Send package purchase notification
     */
    public function sendPackagePurchaseNotification(UserPackage $package)
    {
        $title = "Welcome to your {$package->name} package!";
        $message = "Thank you for purchasing the {$package->name} package. " .
                   "Your package includes {$package->total_sessions} sessions and is valid until {$package->expiry_date->format('F j, Y')}. " .
                   "We look forward to seeing you at the gym!";

        try {
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'package_purchase',
                'priority' => 'medium',
                'channels' => ['in_app', 'email'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$package->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            $package->logNotification('purchase', 'email', true);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package purchase notification', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->logNotification('purchase', 'email', false, $e->getMessage());
            return false;
        }
    }

    /**
     * Send package renewal notification
     */
    public function sendPackageRenewalNotification(UserPackage $package)
    {
        $title = "Your {$package->name} package has been renewed!";
        $message = "Your {$package->name} package has been successfully renewed. " .
                   "You now have {$package->total_sessions} sessions available until {$package->expiry_date->format('F j, Y')}. " .
                   "Thank you for your continued membership!";

        try {
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'package_renewal',
                'priority' => 'medium',
                'channels' => ['in_app', 'email'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$package->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            $package->logNotification('renewal', 'email', true);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package renewal notification', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->logNotification('renewal', 'email', false, $e->getMessage());
            return false;
        }
    }

    /**
     * Process auto-renewals
     */
    public function processAutoRenewals()
    {
        $packages = UserPackage::where('auto_renew', true)
            ->whereDate('expiry_date', '<=', now()->addDays(1))
            ->where('status', '!=', UserPackage::STATUS_EXPIRED)
            ->with(['user', 'package'])
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => $packages->count(),
        ];

        foreach ($packages as $package) {
            try {
                // Attempt to renew the package
                $newPackage = $package->renew();
                
                if ($newPackage) {
                    $this->sendPackageRenewalNotification($newPackage);
                    $results['success']++;
                } else {
                    $results['failed']++;
                    Log::error('Auto-renewal failed for package', ['package_id' => $package->id]);
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Auto-renewal error', [
                    'package_id' => $package->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Send package extension notification
     */
    public function sendPackageExtensionNotification(UserPackage $package)
    {
        $title = "Your {$package->name} package has been extended!";
        $message = "Good news! Your {$package->name} package has been extended. " .
                   "Your new expiry date is {$package->expiry_date->format('F j, Y')} and you have {$package->remaining_sessions} sessions remaining. " .
                   "Enjoy your extended membership!";

        try {
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'package_extension',
                'priority' => 'medium',
                'channels' => ['in_app', 'email'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$package->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            $package->logNotification('extension', 'email', true);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package extension notification', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->logNotification('extension', 'email', false, $e->getMessage());
            return false;
        }
    }

    /**
     * Send pricing adjustment notification
     */
    public function sendPricingAdjustmentNotification(UserPackage $package)
    {
        $title = "Pricing adjustment applied to your {$package->name} package";
        $message = "A pricing adjustment has been applied to your {$package->name} package. " .
                   "Please check your account for details or contact our support team if you have any questions.";

        try {
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'pricing_adjustment',
                'priority' => 'high',
                'channels' => ['in_app', 'email'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$package->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            $package->logNotification('pricing_adjustment', 'email', true);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send pricing adjustment notification', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->logNotification('pricing_adjustment', 'email', false, $e->getMessage());
            return false;
        }
    }
}