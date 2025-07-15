<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreProduct;
use App\Models\OwnerNotification;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'user'])->latest();

        // Check if request is from admin panel
        $isAdmin = $request->headers->get('origin') === 'http://localhost:5174' || 
                   $request->bearerToken() !== null;

        if (!$isAdmin) {
            // Client sees only their orders
            $userId = $request->input('user_id') ?? $request->header('X-User-Id');
            if (!$userId) {
                return response()->json(['error' => 'User ID required'], 401);
            }
            $query->where('user_id', $userId);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        return response()->json($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:store_products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = StoreProduct::find($item['product_id']);
                
                if (!$product || !$product->is_active) {
                    throw new \Exception("Product {$item['product_id']} is not available");
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal
                ];

                // Update stock if tracking is enabled
                if ($product->stock_quantity !== null) {
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            $tax = $subtotal * 0.24; // Greek VAT
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Create simple notification for admin (skip for now due to table structure)
            // TODO: Fix notification table structure to support order notifications

            DB::commit();

            return response()->json([
                'success' => true,
                'order' => $order->load('items'),
                'message' => 'Η παραγγελία σας καταχωρήθηκε επιτυχώς!'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order)
    {
        // Check authorization
        $isAdmin = $request->headers->get('origin') === 'http://localhost:5174' || 
                   $request->bearerToken() !== null;

        if (!$isAdmin) {
            $userId = $request->input('user_id') ?? $request->header('X-User-Id');
            if ($order->user_id != $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        return response()->json($order->load(['items.product', 'user']));
    }

    /**
     * Update order status (admin only).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,ready_for_pickup,completed,cancelled'
        ]);

        $oldStatus = $order->status;

        // Update order based on status
        switch ($validated['status']) {
            case 'ready_for_pickup':
                $order->markAsReady();
                
                // Create notification for customer
                $notification = Notification::create([
                    'title' => 'Παραγγελία Έτοιμη!',
                    'message' => "Η παραγγελία σας #{$order->order_number} είναι έτοιμη για παραλαβή από το γυμναστήριο!",
                    'type' => 'order_ready',
                    'priority' => 'high',
                    'sender_id' => auth()->id(),
                    'sender_type' => 'admin'
                ]);
                
                // Create recipient
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $order->user_id,
                    'delivered_at' => now()
                ]);
                break;

            case 'completed':
                $order->markAsCompleted();
                break;

            case 'cancelled':
                $order->cancel();
                
                // Restore stock if cancelled
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->stock_quantity !== null) {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                }
                break;

            default:
                $order->update(['status' => $validated['status']]);
        }

        return response()->json([
            'success' => true,
            'order' => $order->fresh()->load(['items.product', 'user']),
            'message' => 'Η κατάσταση της παραγγελίας ενημερώθηκε'
        ]);
    }

    /**
     * Get user's order history.
     */
    public function userOrders(Request $request)
    {
        $userId = $request->input('user_id') ?? $request->header('X-User-Id');
        
        if (!$userId) {
            return response()->json(['error' => 'User ID required'], 401);
        }

        $orders = Order::with(['items.product'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json($orders);
    }
}