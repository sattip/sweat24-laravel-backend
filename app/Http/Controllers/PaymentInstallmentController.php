<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstallment;
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
            'payment_method' => 'nullable|in:cash,card,transfer',
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
            'payment_method' => 'nullable|in:cash,card,transfer',
            'status' => 'sometimes|in:pending,paid,overdue',
            'notes' => 'nullable|string',
        ]);
        
        $paymentInstallment->update($validated);
        return response()->json($paymentInstallment);
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
