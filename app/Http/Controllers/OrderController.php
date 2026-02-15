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
use App\Notifications\Orders\OrderConfirmationNotification;
use App\Notifications\Orders\OrderReadyNotification;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'user'])->latest();

        // Authenticate via Sanctum token
        $authUser = $request->user('sanctum');
        $isAdmin = $authUser && in_array($authUser->role, ['admin', 'trainer']);

        if (!$isAdmin) {
            // Client sees only their orders
            $userId = $authUser ? $authUser->id : $request->input('user_id');
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
            $hasPreorderProduct = false;

            foreach ($validated['items'] as $item) {
                $product = StoreProduct::find($item['product_id']);

                if (!$product || !$product->is_active) {
                    throw new \Exception("Product {$item['product_id']} is not available");
                }

                // Check if any product is preorder
                if ($product->is_preorder) {
                    $hasPreorderProduct = true;
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                    'is_preorder' => $product->is_preorder
                ];

                // Atomic stock check-and-decrement to prevent race conditions
                if (!$product->is_preorder && $product->stock_quantity !== null) {
                    $decremented = StoreProduct::where('id', $product->id)
                        ->where('stock_quantity', '>=', $item['quantity'])
                        ->decrement('stock_quantity', $item['quantity']);

                    if (!$decremented) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }
                }
            }

            $tax = $subtotal * 0.24; // Greek VAT
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'status' => 'pending',
                'is_preorder' => $hasPreorderProduct,
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

            // Send order confirmation email to customer
            if ($order->user) {
                $order->user->notify(new OrderConfirmationNotification($order));
            }

            // Notify admins about new order
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new OrderConfirmationNotification($order));
            }

            DB::commit();

            $message = $hasPreorderProduct
                ? 'Το αίτημα προπαραγγελίας σας καταχωρήθηκε επιτυχώς! Θα ενημερωθείτε όταν είναι διαθέσιμο.'
                : 'Η παραγγελία σας καταχωρήθηκε επιτυχώς!';

            return response()->json([
                'success' => true,
                'order' => $order->load('items'),
                'message' => $message
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
        // Authenticate via Sanctum token
        $authUser = $request->user('sanctum');
        $isAdmin = $authUser && in_array($authUser->role, ['admin', 'trainer']);

        if (!$isAdmin) {
            $userId = $authUser ? $authUser->id : $request->input('user_id');
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
        $newStatus = $validated['status'];

        // Skip if status hasn't changed
        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => true,
                'order' => $order->load(['items.product', 'user']),
                'message' => 'Η κατάσταση της παραγγελίας δεν άλλαξε'
            ]);
        }

        // Update order based on status
        switch ($newStatus) {
            case 'ready_for_pickup':
                $order->markAsReady();
                
                // Send order ready notification to customer
                if ($order->user) {
                    $order->user->notify(new OrderReadyNotification($order));
                }
                break;

            case 'completed':
                $order->markAsCompleted();
                break;

            case 'cancelled':
                $order->cancel();

                // Restore stock if cancelled (only for non-preorder items)
                foreach ($order->items as $item) {
                    if (!$item->is_preorder && $item->product && $item->product->stock_quantity !== null) {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                }
                break;

            default:
                $order->update(['status' => $newStatus]);
        }

        // Dispatch the OrderStatusChanged event for notifications
        \App\Events\OrderStatusChanged::dispatch($order, $oldStatus, $newStatus);

        return response()->json([
            'success' => true,
            'order' => $order->fresh()->load(['items.product', 'user']),
            'message' => 'Η κατάσταση της παραγγελίας ενημερώθηκε και στάλθηκε ειδοποίηση στον πελάτη'
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

    /**
     * Get order history for authenticated user (client app)
     */
    public function orderHistory(Request $request)
    {
        // Check for authentication first (from bearer token if available)
        $user = $request->user();
        $userId = null;

        if ($user) {
            // User is authenticated via Sanctum
            $userId = $user->id;
        } else {
            // Fallback to query parameter for client app compatibility
            $userId = $request->query('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ο χρήστης πρέπει να είναι συνδεδεμένος ή να παρέχει user_id'
                ], 401);
            }
        }

        try {
            $orders = Order::with(['items.product'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'status_display' => $this->getStatusDisplay($order->status),
                        'subtotal' => $order->subtotal,
                        'tax' => $order->tax,
                        'total' => $order->total,
                        'customer_name' => $order->customer_name,
                        'customer_email' => $order->customer_email,
                        'customer_phone' => $order->customer_phone,
                        'notes' => $order->notes,
                        'ready_at' => $order->ready_at,
                        'completed_at' => $order->completed_at,
                        'created_at' => $order->created_at,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'product_name' => $item->product_name,
                                'price' => $item->price,
                                'quantity' => $item->quantity,
                                'subtotal' => $item->subtotal,
                                'product' => $item->product ? [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'image_url' => $item->product->image_url,
                                    'category' => $item->product->category,
                                    'slug' => $item->product->slug,
                                ] : null
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => $orders->count(),
                'message' => 'Το ιστορικό παραγγελιών ανακτήθηκε επιτυχώς'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Σφάλμα κατά την ανάκτηση του ιστορικού παραγγελιών',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get display text for order status
     */
    private function getStatusDisplay($status)
    {
        $statusMap = [
            'pending' => 'Εκκρεμής',
            'processing' => 'Σε Επεξεργασία',
            'ready_for_pickup' => 'Έτοιμη για Παραλαβή',
            'completed' => 'Ολοκληρωμένη',
            'cancelled' => 'Ακυρωμένη'
        ];

        return $statusMap[$status] ?? $status;
    }
}