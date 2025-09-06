<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstallment;
use App\Notifications\Payments\PaymentInstallmentReceivedNotification;
use Illuminate\Http\Request;

class PaymentInstallmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PaymentInstallment::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('customer')) {
            $query->where('customer_name', 'LIKE', '%' . $request->customer . '%');
        }
        
        $installments = $query->orderBy('due_date')->get();
        return response()->json($installments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'customer_name' => 'required|string|max:255',
            'package_id' => 'required|exists:packages,id',
            'package_name' => 'required|string|max:255',
            'installment_number' => 'required|integer|min:1',
            'total_installments' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,card,transfer,iris,cash_a',
            'status' => 'required|in:pending,paid,overdue',
            'notes' => 'nullable|string',
        ]);
        
        $installment = PaymentInstallment::create($validated);
        return response()->json($installment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentInstallment $paymentInstallment)
    {
        return response()->json($paymentInstallment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentInstallment $paymentInstallment)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:users,id',
            'customer_name' => 'sometimes|string|max:255',
            'package_id' => 'sometimes|exists:packages,id',
            'package_name' => 'sometimes|string|max:255',
            'installment_number' => 'sometimes|integer|min:1',
            'total_installments' => 'sometimes|integer|min:1',
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'paid_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,card,transfer,iris,cash_a',
            'status' => 'sometimes|in:pending,paid,overdue',
            'notes' => 'nullable|string',
        ]);
        
        $previousStatus = $paymentInstallment->status;
        $paymentInstallment->update($validated);
        
        // Send payment received notification if status changed to 'paid'
        if ($previousStatus !== 'paid' && $paymentInstallment->status === 'paid') {
            // Find the customer user
            $customer = \App\Models\User::find($paymentInstallment->customer_id);
            if ($customer) {
                $customer->notify(new PaymentInstallmentReceivedNotification($paymentInstallment));
            }
            
            // Notify admins about payment received
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new PaymentInstallmentReceivedNotification($paymentInstallment));
            }
        }
        
        return response()->json($paymentInstallment);
    }

    /**
     * Mark payment installment as paid
     */
    public function markAsPaid(Request $request, PaymentInstallment $paymentInstallment)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,transfer,iris,cash_a',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($paymentInstallment->status === 'paid') {
            return response()->json([
                'message' => 'Payment installment is already marked as paid'
            ], 400);
        }

        $paymentInstallment->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'paid_date' => $validated['paid_date'] ?? now(),
            'notes' => $validated['notes'] ?? $paymentInstallment->notes,
        ]);

        // Send payment received notification
        $customer = \App\Models\User::find($paymentInstallment->customer_id);
        if ($customer) {
            $customer->notify(new PaymentInstallmentReceivedNotification($paymentInstallment));
        }
        
        // Notify admins about payment received
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new PaymentInstallmentReceivedNotification($paymentInstallment));
        }

        return response()->json([
            'message' => 'Payment installment marked as paid and notifications sent',
            'data' => $paymentInstallment
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentInstallment $paymentInstallment)
    {
        $paymentInstallment->delete();
        return response()->json(['message' => 'Payment installment deleted successfully']);
    }
}
