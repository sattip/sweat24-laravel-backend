<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\CashRegisterEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BusinessExpense;
use App\Models\Package;
use App\Models\Service;
use App\Models\Store;
use App\Models\User;
use App\Traits\ApiResponseTrait;

class FinancialReportsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Σύνταξη οικονομικών αναφορών - Dashboard Overview
     */
    public function dashboard(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        // Συνολικά έσοδα
        $totalRevenue = $this->getTotalRevenue($startDate, $endDate, $storeId);

        // Έσοδα ανά κατηγορία
        $revenueByCategory = $this->getRevenueByCategory($startDate, $endDate, $storeId);

        // Τάσεις εσόδων
        $revenueTrends = $this->getRevenueTrends($startDate, $endDate, $storeId);

        // Κορυφαίοι πελάτες
        $topCustomers = $this->getTopCustomers($startDate, $endDate, $storeId, 10);

        // Στατιστικά πακέτων
        $packageStats = $this->getPackageStatistics($startDate, $endDate, $storeId);

        // Στατιστικά προϊόντων
        $productStats = $this->getProductStatistics($startDate, $endDate, $storeId);

        // Ανάλυση εξόδων
        $expenseAnalysis = $this->getExpenseAnalysis($startDate, $endDate, $storeId);

        // Κέρδος/Ξημία
        $profitLoss = $this->calculateProfitLoss($totalRevenue, $expenseAnalysis);

        // Customer Journey Data
        $customerJourneyData = $this->getCustomerJourneyForDashboard($startDate, $endDate, $storeId);

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'store_id' => $storeId
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $expenseAnalysis['total_expenses'],
                'net_profit' => $profitLoss['net_profit'],
                'profit_margin' => $profitLoss['profit_margin']
            ],
            'customer_journey' => $customerJourneyData,
            'revenue_by_category' => $revenueByCategory,
            'revenue_trends' => $revenueTrends,
            'top_customers' => $topCustomers,
            'package_statistics' => $packageStats,
            'product_statistics' => $productStats,
            'expense_analysis' => $expenseAnalysis,
            'profit_loss_analysis' => $profitLoss
        ]);
    }

    /**
     * Συνολικά έσοδα ανά περίοδο
     */
    public function totalRevenue(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');
        $groupBy = $request->get('group_by', 'month'); // day, week, month, year

        $revenue = $this->getTotalRevenueGrouped($startDate, $endDate, $storeId, $groupBy);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId', 'groupBy'),
            'data' => $revenue
        ]);
    }

    /**
     * Έσοδα ανά πελάτη
     */
    public function revenuePerCustomer(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');
        $limit = $request->get('limit', 50);
        $sortBy = $request->get('sort_by', 'total_revenue'); // total_revenue, booking_count, avg_booking_value

        $customers = $this->getCustomerRevenueAnalysis($startDate, $endDate, $storeId, $limit, $sortBy);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId', 'limit', 'sortBy'),
            'customers' => $customers
        ]);
    }

    /**
     * Έσοδα ανά υπηρεσία
     */
    public function revenuePerService(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $services = $this->getServiceRevenueAnalysis($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'services' => $services
        ]);
    }

    /**
     * Έσοδα ανά κατάστημα
     */
    public function revenuePerStore(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $stores = $this->getStoreRevenueAnalysis($startDate, $endDate);

        return response()->json([
            'period' => compact('startDate', 'endDate'),
            'stores' => $stores
        ]);
    }

    /**
     * Κορυφαίοι πελάτες
     */
    public function topCustomers(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $storeId = $request->get('store_id');
        $limit = $request->get('limit', 20);
        $metric = $request->get('metric', 'revenue'); // revenue, bookings, avg_value

        $customers = $this->getTopCustomers($startDate, $endDate, $storeId, $limit, $metric);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId', 'limit', 'metric'),
            'top_customers' => $customers
        ]);
    }

    /**
     * Στατιστικά πακέτων
     */
    public function packageStatistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $stats = $this->getPackageStatistics($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'package_statistics' => $stats
        ]);
    }

    /**
     * Στατιστικά προϊόντων
     */
    public function productStatistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $stats = $this->getProductStatistics($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'product_statistics' => $stats
        ]);
    }

    /**
     * Ανάλυση εξόδων
     */
    public function expenseAnalysis(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $analysis = $this->getExpenseAnalysis($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'expense_analysis' => $analysis
        ]);
    }

    /**
     * Τάσεις εσόδων
     */
    public function revenueTrends(Request $request)
    {
        $periods = $request->get('periods', 12); // τελευταίοι Χ μήνες
        $storeId = $request->get('store_id');

        $trends = $this->getRevenueTrendsAnalysis($periods, $storeId);

        return response()->json([
            'periods' => $periods,
            'store_id' => $storeId,
            'trends' => $trends
        ]);
    }

    /**
     * Ανάλυση μεθόδων πληρωμής
     */
    public function paymentMethods(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $methods = $this->getPaymentMethodAnalysis($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'payment_methods' => $methods
        ]);
    }

    /**
     * Ανάλυση κερδοφορίας
     */
    public function profitabilityAnalysis(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $totalRevenue = $this->getTotalRevenue($startDate, $endDate, $storeId);
        $expenseAnalysis = $this->getExpenseAnalysis($startDate, $endDate, $storeId);
        $profitLoss = $this->calculateProfitLoss($totalRevenue, $expenseAnalysis);

        // Ανάλυση ανά κατηγορία
        $categoryProfitability = $this->getCategoryProfitability($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'profitability' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $expenseAnalysis['total_expenses'],
                'net_profit' => $profitLoss['net_profit'],
                'profit_margin' => $profitLoss['profit_margin'],
                'expense_ratio' => $profitLoss['expense_ratio']
            ],
            'category_profitability' => $categoryProfitability
        ]);
    }

    // ========== PRIVATE METHODS FOR CALCULATIONS ==========

    private function getTotalRevenue($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('cash_register_entries')
            ->where('type', 'income')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->sum('amount');
    }

    private function getRevenueByCategory($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('cash_register_entries')
            ->select('category', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
            ->where('type', 'income')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $results = $query->groupBy('category')->get();

        return $results->map(function ($item) {
            return [
                'category' => $item->category,
                'total_amount' => (float) $item->total_amount,
                'transaction_count' => (int) $item->transaction_count,
                'average_transaction' => $item->transaction_count > 0 ? (float) $item->total_amount / $item->transaction_count : 0
            ];
        });
    }

    private function getRevenueTrends($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('cash_register_entries')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $results = $query->groupBy('date')->orderBy('date')->get();

        return $results->map(function ($item) {
            return [
                'date' => $item->date,
                'revenue' => (float) $item->revenue,
                'expenses' => (float) $item->expenses,
                'net' => (float) $item->revenue - $item->expenses
            ];
        });
    }

    private function getTotalRevenueGrouped($startDate, $endDate, $storeId = null, $groupBy = 'month')
    {
        $dateFormat = match($groupBy) {
            'day' => "strftime('%Y-%m-%d', created_at)",
            'week' => "strftime('%Y-%W', created_at)",
            'month' => "strftime('%Y-%m', created_at)",
            'year' => "strftime('%Y', created_at)",
            default => "strftime('%Y-%m', created_at)"
        };

        $query = DB::table('cash_register_entries')
            ->select(
                DB::raw("{$dateFormat} as period"),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses'),
                DB::raw('COUNT(*) as transactions')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $results = $query->groupBy('period')->orderBy('period')->get();

        return $results->map(function ($item) {
            return [
                'period' => $item->period,
                'revenue' => (float) $item->revenue,
                'expenses' => (float) $item->expenses,
                'net_profit' => (float) $item->revenue - $item->expenses,
                'transactions' => (int) $item->transactions
            ];
        });
    }

    private function getTopCustomers($startDate, $endDate, $storeId = null, $limit = 10, $metric = 'revenue')
    {
        $query = DB::table('cash_register_entries')
            ->join('users', 'cash_register_entries.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('SUM(CASE WHEN cash_register_entries.type = "income" THEN cash_register_entries.amount ELSE 0 END) as total_revenue'),
                DB::raw('COUNT(CASE WHEN cash_register_entries.type = "income" THEN 1 END) as transaction_count'),
                DB::raw('AVG(CASE WHEN cash_register_entries.type = "income" THEN cash_register_entries.amount END) as avg_transaction_value'),
                DB::raw('MAX(cash_register_entries.created_at) as last_transaction_date')
            )
            ->where('cash_register_entries.type', 'income')
            ->whereBetween('cash_register_entries.created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('cash_register_entries.store_id', $storeId);
        }

        $results = $query->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy(match($metric) {
                'revenue' => 'total_revenue',
                'bookings' => 'transaction_count',
                'avg_value' => 'avg_transaction_value',
                default => 'total_revenue'
            }, 'desc')
            ->limit($limit)
            ->get();

        return $results->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'total_revenue' => (float) $item->total_revenue,
                'transaction_count' => (int) $item->transaction_count,
                'avg_transaction_value' => $item->avg_transaction_value ? (float) $item->avg_transaction_value : 0,
                'last_transaction_date' => $item->last_transaction_date
            ];
        });
    }

    private function getCustomerRevenueAnalysis($startDate, $endDate, $storeId = null, $limit = 50, $sortBy = 'total_revenue')
    {
        // Ανάλυση εσόδων από πακέτα
        $packageRevenue = DB::table('user_packages')
            ->join('packages', 'user_packages.package_id', '=', 'packages.id')
            ->leftJoin('users', 'user_packages.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('SUM(COALESCE(user_packages.custom_price, packages.price)) as package_revenue'),
                DB::raw('COUNT(*) as packages_purchased'),
                DB::raw('AVG(COALESCE(user_packages.custom_price, packages.price)) as avg_package_value')
            )
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate]);

        if ($storeId) {
            $packageRevenue->where('user_packages.store_id', $storeId);
        }

        $packageRevenue = $packageRevenue->groupBy('users.id', 'users.name', 'users.email')->get();

        // Ανάλυση εσόδων από προϊόντα
        $productRevenue = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('SUM(orders.total) as product_revenue'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('AVG(orders.total) as avg_order_value')
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed');

        if ($storeId) {
            $productRevenue->where('orders.store_id', $storeId);
        }

        $productRevenue = $productRevenue->groupBy('users.id', 'users.name', 'users.email')->get();

        // Συνδυασμός δεδομένων
        $customers = [];
        $allCustomers = collect([...$packageRevenue, ...$productRevenue])
            ->groupBy('id')
            ->map(function ($customerData) {
                $combined = [
                    'id' => $customerData->first()->id,
                    'name' => $customerData->first()->name,
                    'email' => $customerData->first()->email,
                    'package_revenue' => 0,
                    'packages_purchased' => 0,
                    'product_revenue' => 0,
                    'orders_count' => 0
                ];

                foreach ($customerData as $data) {
                    if (isset($data->package_revenue)) {
                        $combined['package_revenue'] = (float) $data->package_revenue;
                        $combined['packages_purchased'] = (int) $data->packages_purchased;
                    }
                    if (isset($data->product_revenue)) {
                        $combined['product_revenue'] = (float) $data->product_revenue;
                        $combined['orders_count'] = (int) $data->orders_count;
                    }
                }

                $combined['total_revenue'] = $combined['package_revenue'] + $combined['product_revenue'];
                $combined['total_transactions'] = $combined['packages_purchased'] + $combined['orders_count'];
                $combined['avg_transaction_value'] = $combined['total_transactions'] > 0
                    ? $combined['total_revenue'] / $combined['total_transactions']
                    : 0;

                return $combined;
            });

        // Ταξινόμηση
        $sorted = $allCustomers->sortByDesc($sortBy)->take($limit);

        return $sorted->values();
    }

    private function getServiceRevenueAnalysis($startDate, $endDate, $storeId = null)
    {
        // Έσοδα από πακέτα ανά υπηρεσία
        $packageRevenue = DB::table('user_packages')
            ->join('packages', 'user_packages.package_id', '=', 'packages.id')
            ->join('package_service', 'packages.id', '=', 'package_service.package_id')
            ->join('services', 'package_service.service_id', '=', 'services.id')
            ->select(
                'services.id',
                'services.name',
                DB::raw('SUM(COALESCE(user_packages.custom_price, packages.price)) as revenue'),
                DB::raw('COUNT(*) as packages_sold'),
                DB::raw('AVG(COALESCE(user_packages.custom_price, packages.price)) as avg_package_price')
            )
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate]);

        if ($storeId) {
            $packageRevenue->where('user_packages.store_id', $storeId);
        }

        $packageRevenue = $packageRevenue->groupBy('services.id', 'services.name')->get();

        // Προσθήκη στατιστικών κρατήσεων ανά υπηρεσία
        $bookingStats = DB::table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->select(
                'services.id',
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_bookings'),
                DB::raw('COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_bookings')
            )
            ->whereBetween('bookings.created_at', [$startDate, $endDate]);

        if ($storeId) {
            $bookingStats->where('bookings.store_id', $storeId);
        }

        $bookingStats = $bookingStats->groupBy('services.id')->get()->keyBy('id');

        return $packageRevenue->map(function ($service) use ($bookingStats) {
            $bookingData = $bookingStats->get($service->id);

            return [
                'id' => $service->id,
                'name' => $service->name,
                'revenue' => (float) $service->revenue,
                'packages_sold' => (int) $service->packages_sold,
                'avg_package_price' => (float) $service->avg_package_price,
                'total_bookings' => $bookingData ? (int) $bookingData->total_bookings : 0,
                'completed_bookings' => $bookingData ? (int) $bookingData->completed_bookings : 0,
                'cancelled_bookings' => $bookingData ? (int) $bookingData->cancelled_bookings : 0,
                'completion_rate' => $bookingData && $bookingData->total_bookings > 0
                    ? round(($bookingData->completed_bookings / $bookingData->total_bookings) * 100, 2)
                    : 0
            ];
        });
    }

    private function getStoreRevenueAnalysis($startDate, $endDate)
    {
        $query = DB::table('cash_register_entries')
            ->join('stores', 'cash_register_entries.store_id', '=', 'stores.id')
            ->select(
                'stores.id',
                'stores.name',
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_revenue'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expenses'),
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('AVG(CASE WHEN type = "income" THEN amount END) as avg_transaction_value')
            )
            ->whereBetween('cash_register_entries.created_at', [$startDate, $endDate])
            ->groupBy('stores.id', 'stores.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return $query->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'total_revenue' => (float) $store->total_revenue,
                'total_expenses' => (float) $store->total_expenses,
                'net_profit' => (float) $store->total_revenue - $store->total_expenses,
                'total_transactions' => (int) $store->total_transactions,
                'avg_transaction_value' => $store->avg_transaction_value ? (float) $store->avg_transaction_value : 0,
                'profit_margin' => $store->total_revenue > 0
                    ? round((($store->total_revenue - $store->total_expenses) / $store->total_revenue) * 100, 2)
                    : 0
            ];
        });
    }

    private function getPackageStatistics($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('user_packages')
            ->join('packages', 'user_packages.package_id', '=', 'packages.id')
            ->select(
                'packages.id',
                'packages.name',
                DB::raw('SUM(COALESCE(user_packages.custom_price, packages.price)) as total_revenue'),
                DB::raw('COUNT(*) as packages_sold'),
                DB::raw('AVG(COALESCE(user_packages.custom_price, packages.price)) as avg_price'),
                DB::raw('SUM(user_packages.remaining_sessions) as total_remaining_sessions'),
                DB::raw('AVG(packages.sessions) as avg_sessions_per_package')
            )
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('user_packages.store_id', $storeId);
        }

        $results = $query->groupBy('packages.id', 'packages.name')->orderBy('total_revenue', 'desc')->get();

        // Συνολικά στατιστικά
        $totalStats = [
            'total_packages_sold' => $results->sum('packages_sold'),
            'total_revenue' => $results->sum('total_revenue'),
            'avg_revenue_per_package' => $results->avg('total_revenue'),
            'most_popular_package' => $results->first() ? $results->first()->name : null
        ];

        return [
            'summary' => $totalStats,
            'packages' => $results->map(function ($package) use ($totalStats) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'total_revenue' => (float) $package->total_revenue,
                    'packages_sold' => (int) $package->packages_sold,
                    'avg_price' => (float) $package->avg_price,
                    'total_remaining_sessions' => (int) $package->total_remaining_sessions,
                    'avg_sessions_per_package' => (float) $package->avg_sessions_per_package,
                    'revenue_share' => $totalStats['total_revenue'] > 0
                        ? round(($package->total_revenue / $totalStats['total_revenue']) * 100, 2)
                        : 0
                ];
            })
        ];
    }

    private function getProductStatistics($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('store_products', 'order_items.product_id', '=', 'store_products.id')
            ->select(
                'store_products.id',
                'store_products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('AVG(order_items.price) as avg_unit_price'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count')
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed');

        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        $results = $query->groupBy('store_products.id', 'store_products.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalStats = [
            'total_products_sold' => $results->sum('total_quantity_sold'),
            'total_revenue' => $results->sum('total_revenue'),
            'avg_revenue_per_product' => $results->avg('total_revenue'),
            'most_popular_product' => $results->first() ? $results->first()->name : null
        ];

        return [
            'summary' => $totalStats,
            'products' => $results->map(function ($product) use ($totalStats) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_quantity_sold' => (int) $product->total_quantity_sold,
                    'total_revenue' => (float) $product->total_revenue,
                    'avg_unit_price' => (float) $product->avg_unit_price,
                    'orders_count' => (int) $product->orders_count,
                    'revenue_share' => $totalStats['total_revenue'] > 0
                        ? round(($product->total_revenue / $totalStats['total_revenue']) * 100, 2)
                        : 0
                ];
            })
        ];
    }

    private function getExpenseAnalysis($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('business_expenses')
            ->select(
                'category',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('AVG(amount) as avg_expense_amount')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $categoryExpenses = $query->groupBy('category')->get();

        $totalExpenses = $categoryExpenses->sum('total_amount');

        return [
            'total_expenses' => (float) $totalExpenses,
            'categories' => $categoryExpenses->map(function ($category) use ($totalExpenses) {
                return [
                    'category' => $category->category,
                    'total_amount' => (float) $category->total_amount,
                    'expense_count' => (int) $category->expense_count,
                    'avg_expense_amount' => (float) $category->avg_expense_amount,
                    'percentage_of_total' => $totalExpenses > 0
                        ? round(($category->total_amount / $totalExpenses) * 100, 2)
                        : 0
                ];
            }),
            'expense_count' => $categoryExpenses->sum('expense_count'),
            'avg_expense_amount' => $categoryExpenses->avg('total_amount')
        ];
    }

    private function getRevenueTrendsAnalysis($periods = 12, $storeId = null)
    {
        $endDate = now();
        $startDate = now()->subMonths($periods);

        $query = DB::table('cash_register_entries')
            ->select(
                DB::raw('strftime("%Y-%m", created_at) as month'),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $results = $query->groupBy('month')->orderBy('month')->get();

        $trends = [];
        $previousRevenue = 0;

        foreach ($results as $result) {
            $currentRevenue = (float) $result->revenue;
            $currentExpenses = (float) $result->expenses;
            $netProfit = $currentRevenue - $currentExpenses;

            $growthRate = $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : 0;

            $trends[] = [
                'month' => $result->month,
                'revenue' => $currentRevenue,
                'expenses' => $currentExpenses,
                'net_profit' => $netProfit,
                'growth_rate' => $growthRate
            ];

            $previousRevenue = $currentRevenue;
        }

        return $trends;
    }

    private function getPaymentMethodAnalysis($startDate, $endDate, $storeId = null)
    {
        $query = DB::table('cash_register_entries')
            ->select(
                'payment_method',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(amount) as avg_transaction_amount')
            )
            ->where('type', 'income')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $results = $query->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->orderBy('total_amount', 'desc')
            ->get();

        $totalRevenue = $results->sum('total_amount');

        return $results->map(function ($method) use ($totalRevenue) {
            return [
                'payment_method' => $method->payment_method,
                'total_amount' => (float) $method->total_amount,
                'transaction_count' => (int) $method->transaction_count,
                'avg_transaction_amount' => (float) $method->avg_transaction_amount,
                'percentage_of_total' => $totalRevenue > 0
                    ? round(($method->total_amount / $totalRevenue) * 100, 2)
                    : 0
            ];
        });
    }

    private function calculateProfitLoss($totalRevenue, $expenseAnalysis)
    {
        $totalExpenses = $expenseAnalysis['total_expenses'];
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_expenses' => (float) $totalExpenses,
            'net_profit' => (float) $netProfit,
            'profit_margin' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0,
            'expense_ratio' => $totalRevenue > 0 ? round(($totalExpenses / $totalRevenue) * 100, 2) : 0
        ];
    }

    private function getCategoryProfitability($startDate, $endDate, $storeId = null)
    {
        // Ανάλυση κερδοφορίας ανά κατηγορία εσόδων
        $revenueByCategory = DB::table('cash_register_entries')
            ->select(
                'category',
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $revenueByCategory->where('store_id', $storeId);
        }

        $results = $revenueByCategory->groupBy('category')->get();

        return $results->map(function ($category) {
            $revenue = (float) $category->revenue;
            $expenses = (float) $category->expenses;
            $profit = $revenue - $expenses;

            return [
                'category' => $category->category,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $profit,
                'profit_margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0
            ];
        })->sortByDesc('profit');
    }

    // ========== CUSTOMER JOURNEY ANALYTICS METHODS ==========

    /**
     * Ανάλυση μετατροπής trial χρηστών
     */
    public function customerConversion(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->get('store_id');

        $conversionData = $this->getCustomerConversionAnalysis($startDate, $endDate, $storeId);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId'),
            'conversion_funnel' => $conversionData['funnel'],
            'time_to_convert' => $conversionData['time_analysis'],
            'conversion_sources' => $conversionData['sources']
        ]);
    }

    /**
     * Customer Lifetime Value (LTV) Analysis
     */
    public function customerLtv(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonths(12)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $storeId = $request->get('store_id');
        $cohortPeriod = $request->get('cohort_period', 'monthly');

        $ltvData = $this->getCustomerLtvAnalysis($startDate, $endDate, $storeId, $cohortPeriod);

        return response()->json([
            'period' => compact('startDate', 'endDate', 'storeId', 'cohortPeriod'),
            'cohort_analysis' => $ltvData['cohorts'],
            'overall_ltv' => $ltvData['overall'],
            'ltv_forecast' => $ltvData['forecast']
        ]);
    }

    /**
     * Retention & Churn Analysis
     */
    public function retentionAnalysis(Request $request)
    {
        $periods = $request->get('periods', 12);
        $storeId = $request->get('store_id');

        $retentionData = $this->getRetentionAnalysis($periods, $storeId);

        return response()->json([
            'periods' => $periods,
            'store_id' => $storeId,
            'retention_rates' => $retentionData['retention'],
            'churn_rates' => $retentionData['churn'],
            'cohort_retention' => $retentionData['cohorts']
        ]);
    }

    private function getCustomerConversionAnalysis($startDate, $endDate, $storeId = null)
    {
        // Trial users - users who signed up but haven't made a purchase
        $trialUsers = DB::table('users')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('user_packages')
                      ->whereRaw('user_packages.user_id = users.id');
            })
            ->count();

        // First purchase users - users who made their first purchase in the period
        $firstPurchaseUsers = DB::table('user_packages')
            ->select('user_id')
            ->whereBetween('assigned_date', [$startDate, $endDate])
            ->distinct('user_id')
            ->count();

        // Repeat customers - users who have made multiple purchases
        $repeatCustomers = DB::table('user_packages')
            ->select('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->whereBetween('assigned_date', [$startDate, $endDate])
            ->groupBy('user_id')
            ->get()
            ->count();

        // Calculate conversion rates
        $conversionRateTrialToFirst = $trialUsers > 0 ? round(($firstPurchaseUsers / ($trialUsers + $firstPurchaseUsers)) * 100, 2) : 0;
        $conversionRateFirstToRepeat = $firstPurchaseUsers > 0 ? round(($repeatCustomers / $firstPurchaseUsers) * 100, 2) : 0;

        // Time to convert analysis
        $conversionTimes = DB::table('users')
            ->join('user_packages', 'users.id', '=', 'user_packages.user_id')
            ->select(
                DB::raw('(julianday(user_packages.assigned_date) - julianday(users.created_at)) as days_to_convert')
            )
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate])
            ->whereRaw('user_packages.assigned_date > users.created_at')
            ->get();

        $avgDaysToConvert = $conversionTimes->avg('days_to_convert') ?? 0;
        $medianDaysToConvert = $conversionTimes->median('days_to_convert') ?? 0;
        $fastestConversion = $conversionTimes->min('days_to_convert') ?? 0;
        $slowestConversion = $conversionTimes->max('days_to_convert') ?? 0;

        return [
            'funnel' => [
                'trial_users' => $trialUsers,
                'first_purchase' => $firstPurchaseUsers,
                'repeat_customers' => $repeatCustomers,
                'conversion_rate_trial_to_first' => $conversionRateTrialToFirst . '%',
                'conversion_rate_first_to_repeat' => $conversionRateFirstToRepeat . '%'
            ],
            'time_analysis' => [
                'average_days' => round($avgDaysToConvert, 1),
                'median_days' => round($medianDaysToConvert, 1),
                'fastest_conversion' => $fastestConversion,
                'slowest_conversion' => $slowestConversion
            ],
            'sources' => $this->getConversionSources($startDate, $endDate, $storeId)
        ];
    }

    private function getCustomerLtvAnalysis($startDate, $endDate, $storeId = null, $cohortPeriod = 'monthly')
    {
        // Get customer cohorts based on first purchase date
        $cohorts = DB::table('users')
            ->join('user_packages', function ($join) {
                $join->on('users.id', '=', 'user_packages.user_id')
                     ->whereRaw('user_packages.assigned_date = (
                         SELECT MIN(assigned_date)
                         FROM user_packages up
                         WHERE up.user_id = users.id
                     )');
            })
            ->select(
                DB::raw($this->getCohortGroupBy($cohortPeriod, 'user_packages.assigned_date') . ' as cohort'),
                DB::raw('COUNT(DISTINCT users.id) as customers'),
                DB::raw('SUM(COALESCE(user_packages.custom_price, user_packages.total_sessions * 0)) as cohort_value'),
                DB::raw('AVG(COALESCE(user_packages.custom_price, user_packages.total_sessions * 0)) as avg_customer_value')
            )
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate])
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get();

        // Calculate LTV projections
        $ltvProjections = $cohorts->map(function ($cohort) {
            $currentLtv = (float) $cohort->avg_customer_value;
            $projectedLtv = $currentLtv * 1.5; // Simple projection multiplier

            return [
                'cohort' => $cohort->cohort,
                'current_ltv' => $currentLtv,
                'projected_ltv' => $projectedLtv,
                'customers' => (int) $cohort->customers
            ];
        });

        return [
            'cohorts' => $cohorts,
            'overall' => [
                'avg_ltv' => $cohorts->avg('avg_customer_value'),
                'total_customers' => $cohorts->sum('customers'),
                'total_value' => $cohorts->sum('cohort_value')
            ],
            'forecast' => $ltvProjections
        ];
    }

    private function getRetentionAnalysis($periods = 12, $storeId = null)
    {
        $retentionData = [];

        for ($i = 0; $i < $periods; $i++) {
            $periodStart = now()->subMonths($i + 1)->startOfMonth();
            $periodEnd = now()->subMonths($i)->endOfMonth();

            // Customers who made purchases in this period
            $activeCustomers = DB::table('user_packages')
                ->whereBetween('assigned_date', [$periodStart, $periodEnd])
                ->distinct('user_id')
                ->count('user_id');

            // Customers who were active in the previous period
            $previousPeriodStart = $periodStart->copy()->subMonths(1);
            $previousPeriodEnd = $periodEnd->copy()->subMonths(1);

            $previousActiveCustomers = DB::table('user_packages')
                ->whereBetween('assigned_date', [$previousPeriodStart, $previousPeriodEnd])
                ->distinct('user_id')
                ->count('user_id');

            // Calculate retention rate
            $retentionRate = $previousActiveCustomers > 0
                ? round(($activeCustomers / $previousActiveCustomers) * 100, 2)
                : 0;

            $churnRate = 100 - $retentionRate;

            $retentionData[] = [
                'period' => $periodStart->format('Y-m'),
                'active_customers' => $activeCustomers,
                'previous_active' => $previousActiveCustomers,
                'retention_rate' => $retentionRate,
                'churn_rate' => $churnRate
            ];
        }

        return [
            'retention' => collect($retentionData)->pluck('retention_rate', 'period'),
            'churn' => collect($retentionData)->pluck('churn_rate', 'period'),
            'cohorts' => $retentionData
        ];
    }

    private function getConversionSources($startDate, $endDate, $storeId = null)
    {
        // This would typically track referral sources, marketing campaigns, etc.
        // For now, return a basic structure
        return [
            'organic' => 35,
            'referral' => 25,
            'social_media' => 20,
            'paid_ads' => 15,
            'other' => 5
        ];
    }

    private function getCohortGroupBy($period, $dateColumn)
    {
        return match($period) {
            'monthly' => "strftime('%Y-%m', {$dateColumn})",
            'quarterly' => "strftime('%Y', {$dateColumn}) || '-Q' || ((strftime('%m', {$dateColumn}) - 1) / 3 + 1)",
            'yearly' => "strftime('%Y', {$dateColumn})",
            default => "strftime('%Y-%m', {$dateColumn})"
        };
    }

    private function getCustomerJourneyForDashboard($startDate, $endDate, $storeId = null)
    {
        // New trial users in the period
        $newTrialUsers = DB::table('users')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Users converted to paid in the period
        $convertedToPaid = DB::table('user_packages')
            ->whereBetween('assigned_date', [$startDate, $endDate])
            ->distinct('user_id')
            ->count();

        // Conversion rate
        $conversionRate = $newTrialUsers > 0
            ? round(($convertedToPaid / $newTrialUsers) * 100, 2)
            : 0;

        // Average time to convert
        $avgConversionTime = DB::table('users')
            ->join('user_packages', 'users.id', '=', 'user_packages.user_id')
            ->selectRaw('AVG(julianday(user_packages.assigned_date) - julianday(users.created_at)) as avg_days')
            ->whereBetween('user_packages.assigned_date', [$startDate, $endDate])
            ->whereRaw('user_packages.assigned_date > users.created_at')
            ->first();

        return [
            'new_trial_users' => $newTrialUsers,
            'converted_to_paid' => $convertedToPaid,
            'conversion_rate' => $conversionRate . '%',
            'avg_conversion_time_days' => round($avgConversionTime->avg_days ?? 0, 1)
        ];
    }
}
