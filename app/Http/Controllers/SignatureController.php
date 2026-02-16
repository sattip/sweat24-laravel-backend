<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SignatureController extends Controller
{
    /**
     * Store a new signature for user registration
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'signature_data' => 'required|string',
            'document_type' => 'string|in:terms_and_conditions,privacy_policy',
            'document_version' => 'string',
        ]);

        try {
            DB::beginTransaction();

            $signature = Signature::create([
                'user_id' => $validated['user_id'],
                'signature_data' => $validated['signature_data'],
                'ip_address' => $request->ip() ?: '127.0.0.1', // Fallback for testing
                'signed_at' => now(),
                'document_type' => $validated['document_type'] ?? 'terms_and_conditions',
                'document_version' => $validated['document_version'] ?? '1.0',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Signature saved successfully',
                'signature' => $signature->load('user')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to save signature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get signatures for a specific user
     */
    public function userSignatures($userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        $signatures = $user->signatures()
            ->orderBy('signed_at', 'desc')
            ->get();

        return response()->json([
            'user' => $user,
            'signatures' => $signatures
        ]);
    }

    /**
     * Get signatures for a specific user (Admin Panel format)
     */
    public function getUserSignatures($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $signatures = $user->signatures()
                ->orderBy('signed_at', 'desc')
                ->get()
                ->map(function ($signature) {
                    return [
                        'id' => $signature->id,
                        'document_type' => $signature->document_type,
                        'signed_at' => $signature->signed_at->toISOString(),
                        'document_version' => $signature->document_version,
                        'signature_data' => $signature->signature_data,
                        'ip_address' => $signature->ip_address,
                    ];
                });

            return response()->json([
                'data' => [
                    'signatures' => $signatures
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'data' => [
                    'signatures' => []
                ]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Σφάλμα κατά την ανάκτηση των υπογραφών',
                'data' => [
                    'signatures' => []
                ]
            ], 500);
        }
    }

    /**
     * Get a specific signature
     */
    public function show($id): JsonResponse
    {
        $signature = Signature::with('user')->findOrFail($id);

        return response()->json([
            'signature' => $signature
        ]);
    }

    /**
     * Get all signatures (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Signature::with('user');

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('date_from')) {
            $query->where('signed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('signed_at', '<=', $request->date_to);
        }

        $signatures = $query->orderBy('signed_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($signatures);
    }
}
