<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\PointsService;

class BackfillOrderPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:backfill-orders 
                           {--dry-run : Preview what would be done without making changes}
                           {--limit=100 : Maximum number of orders to process}
                           {--status=completed : Order status to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill points for existing completed orders that don\'t have points applied';

    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        parent::__construct();
        $this->pointsService = $pointsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $status = $this->option('status');

        $this->info('🔄 Backfilling Order Points');
        $this->line('');

        // Display current settings
        $settings = $this->pointsService->getPointsSettings();
        $this->info('📊 Current Points Settings:');
        foreach ($settings as $key => $value) {
            $displayKey = str_replace('_', ' ', ucfirst($key));
            $this->line("  • {$displayKey}: {$value}");
        }
        $this->line('');

        if (!$settings['system_active']) {
            $this->warn('⚠️  Points system is currently DISABLED. Points will be calculated but not awarded in production.');
            $this->line('');
        }

        // Find eligible orders
        $query = Order::where('status', $status)
            ->where('points_applied', false)
            ->with('user')
            ->orderBy('created_at', 'desc');

        $totalEligible = $query->count();
        $this->info("📦 Found {$totalEligible} eligible orders with status '{$status}' that don't have points applied");

        if ($totalEligible === 0) {
            $this->info('✅ No orders need points backfill');
            return 0;
        }

        $ordersToProcess = $query->limit($limit)->get();
        $this->line("📋 Processing {$ordersToProcess->count()} orders (limit: {$limit})");
        $this->line('');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Preview what will be done
        $totalPointsToAward = 0;
        $eligibleCount = 0;
        $previewTable = [];

        foreach ($ordersToProcess as $order) {
            $preview = $this->pointsService->previewPointsForOrder($order);
            
            if ($preview['points_to_award'] > 0) {
                $eligibleCount++;
                $totalPointsToAward += $preview['points_to_award'];
                
                $previewTable[] = [
                    $order->order_number,
                    $order->user->name ?? 'Unknown',
                    '€' . number_format($order->total, 2),
                    $preview['points_to_award'],
                    $order->created_at->format('Y-m-d'),
                ];
            }
        }

        if ($eligibleCount === 0) {
            $this->info('✅ No orders are eligible for points (they don\'t meet minimum requirements)');
            return 0;
        }

        $this->info("📈 Summary:");
        $this->line("  • Eligible orders: {$eligibleCount}");
        $this->line("  • Total points to award: {$totalPointsToAward}");
        $this->line('');

        // Show preview table
        $this->table(
            ['Order Number', 'Customer', 'Total', 'Points', 'Date'],
            $previewTable
        );

        if ($dryRun) {
            $this->info('🔍 Dry run completed. Use without --dry-run to apply changes.');
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm("Proceed with awarding points to {$eligibleCount} orders?", false)) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        // Process orders
        $this->info('🚀 Processing orders...');
        $progressBar = $this->output->createProgressBar($eligibleCount);
        $progressBar->start();

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($ordersToProcess as $order) {
            $preview = $this->pointsService->previewPointsForOrder($order);
            
            if ($preview['points_to_award'] <= 0) {
                $results['skipped']++;
                continue;
            }

            try {
                $success = $this->pointsService->awardPointsForOrder($order);
                
                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to award points for order {$order->order_number}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Order {$order->order_number}: " . $e->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        // Display results
        $this->info('📊 Results:');
        $this->line("  ✅ Successfully processed: {$results['success']}");
        $this->line("  ❌ Failed: {$results['failed']}");
        $this->line("  ⏭️  Skipped: {$results['skipped']}");

        if (!empty($results['errors'])) {
            $this->line('');
            $this->error('❌ Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }

        $this->line('');
        $this->info('✅ Backfill operation completed!');

        return 0;
    }
}