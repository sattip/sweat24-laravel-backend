<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DisableNotificationDatabase extends Command
{
    protected $signature = 'notifications:disable-database {--restore : Restore database channel}';
    
    protected $description = 'Temporarily disable database channel in notifications for testing';

    public function handle()
    {
        $restore = $this->option('restore');
        
        // Get all notification files
        $notificationFiles = File::glob(app_path('Notifications/**/*.php'));
        
        $modifiedCount = 0;
        
        foreach ($notificationFiles as $file) {
            $content = File::get($file);
            
            if ($restore) {
                // Restore database channel
                if (str_contains($content, "return ['mail']; // TEMP: disabled database")) {
                    $newContent = str_replace(
                        "return ['mail']; // TEMP: disabled database",
                        "return ['mail', 'database'];",
                        $content
                    );
                    File::put($file, $newContent);
                    $modifiedCount++;
                    $this->line("Restored: " . basename($file));
                }
            } else {
                // Disable database channel
                if (str_contains($content, "return ['mail', 'database'];")) {
                    $newContent = str_replace(
                        "return ['mail', 'database'];",
                        "return ['mail']; // TEMP: disabled database",
                        $content
                    );
                    File::put($file, $newContent);
                    $modifiedCount++;
                    $this->line("Modified: " . basename($file));
                }
            }
        }
        
        if ($restore) {
            $this->info("Restored database channel in {$modifiedCount} notification files.");
        } else {
            $this->info("Disabled database channel in {$modifiedCount} notification files.");
            $this->warn("Remember to restore with: php artisan notifications:disable-database --restore");
        }
    }
}