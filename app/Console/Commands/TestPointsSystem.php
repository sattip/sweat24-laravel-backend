<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Services\PointsService;

class TestPointsSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:points-system {--create-test-data} {--test-order-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the points awarding system';

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
        $this->info('🎯 Testing Points System');
        $this->line('');

        // Display current points settings
        $this->displayPointsSettings();

        if ($this->option('create-test-data')) {
            $this->createTestData();
        }

        if ($testOrderId = $this->option('test-order-id')) {
            $this->testSpecificOrder($testOrderId);
        } else {
            $this->testRandomOrder();
        }

        $this->line('');
        $this->info('✅ Testing completed!');
    }

    protected function displayPointsSettings()
    {
        $this->info('📊 Current Points Settings:');
        $settings = $this->pointsService->getPointsSettings();
        
        foreach ($settings as $key => $value) {
            $displayKey = str_replace('_', ' ', ucfirst($key));
            $this->line("  • {$displayKey}: {$value}");
        }
        $this->line('');
    }

    protected function createTestData()
    {
        $this->info('🏗️  Creating test data...');

        // Find or create a test user
        $user = User::firstOrCreate(
            ['email' => 'test-points@sweat24.com'],
            [
                'name' => 'Test Points User',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'join_date' => now(),
                'status' => 'active',
                'registration_status' => 'approved'
            ]
        );

        // Create a test order
        $order = Order::create([
            'order_number' => 'TEST-' . date('Ymd') . '-' . rand(1000, 9999),
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 5.00,
            'total' => 55.00,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
        ]);

        $this->line("  Created test order: {$order->order_number} (ID: {$order->id})");
        $this->line("  Order total: €{$order->total}");
        $this->line('');

        return $order;
    }

    protected function testSpecificOrder($orderId)
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            $this->error("Order with ID {$orderId} not found!");
            return;
        }

        $this->testOrder($order);
    }

    protected function testRandomOrder()
    {
        // Find a completed order or create test data
        $order = Order::where('status', 'completed')->first();
        
        if (!$order) {
            $this->warn('No completed orders found. Creating test data...');
            $order = $this->createTestData();
        }

        $this->testOrder($order);
    }

    protected function testOrder(Order $order)
    {
        $this->info("🧪 Testing order: {$order->order_number}");
        $this->line("  User: {$order->user->name} ({$order->user->email})");
        $this->line("  Status: {$order->status}");
        $this->line("  Total: €{$order->total}");
        $this->line("  Points applied: " . ($order->hasPointsApplied() ? 'Yes' : 'No'));
        
        if ($order->hasPointsApplied()) {
            $this->line("  Points awarded: {$order->points_awarded}");
            $this->line("  Applied at: {$order->points_applied_at}");
        }
        
        $this->line('');

        // Get points preview
        $preview = $order->getPointsPreview();
        $this->info('📋 Points Preview:');
        $this->line("  Order total: €{$preview['order_total']}");
        $this->line("  Points to award: {$preview['points_to_award']}");
        $this->line("  Eligible: " . ($preview['eligible'] ? 'Yes' : 'No'));
        $this->line("  Already applied: " . ($preview['already_applied'] ? 'Yes' : 'No'));
        $this->line('');

        // Test completion if not already completed
        if ($order->status !== 'completed') {
            if ($this->confirm('Mark this order as completed to test points awarding?', true)) {
                $this->info('📦 Marking order as completed...');
                
                // Get user's balance before
                $balanceBefore = $this->pointsService->getUserPointsBalance($order->user_id);
                $this->line("  User balance before: {$balanceBefore} points");

                // Mark as completed (this should trigger the observer)
                $order->markAsCompleted();

                // Get balance after
                $balanceAfter = $this->pointsService->getUserPointsBalance($order->user_id);
                $this->line("  User balance after: {$balanceAfter} points");
                
                // Refresh order data
                $order->refresh();
                
                if ($order->hasPointsApplied()) {
                    $this->info("  ✅ Points successfully awarded: {$order->points_awarded}");
                    $this->line("  Applied at: {$order->points_applied_at}");
                } else {
                    $this->warn("  ⚠️  Points were not applied");
                }
            }
        } else {
            // Test double application prevention
            if (!$order->hasPointsApplied()) {
                $this->info('🔄 Testing points awarding for already completed order...');
                $success = $this->pointsService->awardPointsForOrder($order);
                
                if ($success) {
                    $this->info('  ✅ Points awarded successfully');
                } else {
                    $this->warn('  ⚠️  Points were not awarded');
                }
            } else {
                $this->info('🔒 Testing double application prevention...');
                $success = $this->pointsService->awardPointsForOrder($order);
                
                if (!$success) {
                    $this->info('  ✅ Double application correctly prevented');
                } else {
                    $this->error('  ❌ Double application was NOT prevented!');
                }
            }
        }

        // Show recent loyalty points for this user
        $this->displayUserLoyaltyPoints($order->user_id);
    }

    protected function displayUserLoyaltyPoints($userId)
    {
        $this->info('💰 Recent loyalty points for user:');
        
        $recentPoints = LoyaltyPoint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentPoints->isEmpty()) {
            $this->line('  No loyalty points found');
            return;
        }

        foreach ($recentPoints as $point) {
            $this->line("  • {$point->created_at->format('Y-m-d H:i')} | {$point->type} | {$point->amount} points | {$point->description}");
        }

        $totalBalance = $this->pointsService->getUserPointsBalance($userId);
        $this->line('');
        $this->info("  Total balance: {$totalBalance} points");
    }
}