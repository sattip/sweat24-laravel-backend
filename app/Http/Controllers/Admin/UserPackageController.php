<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPackageController extends Controller
{
    /**
     * Assign a package to a user (Admin only)
     */
    public function assign(Request $request, User $user)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'starts_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $package = Package::where('id', $validated['package_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // Enforce one active membership at a time
        if ($package->type === 'membership') {
            $hasActiveMembership = UserPackage::where('user_id', $user->id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->whereHas('package', function ($q) {
                    $q->where('type', 'membership');
                })
                ->exists();

            if ($hasActiveMembership) {
                return response()->json([
                    'message' => 'User already has an active membership package',
                ], 409);
            }
        }

        $startsAt = isset($validated['starts_at']) ? 
            \Carbon\Carbon::parse($validated['starts_at']) : now();

        $expiresAt = $package->duration ? $startsAt->copy()->addDays((int) $package->duration) : null;

        $userPackage = null;
        DB::transaction(function () use ($user, $package, $startsAt, $expiresAt, $request, &$userPackage) {
            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'name' => $package->name,
                'assigned_date' => $startsAt,
                'expiry_date' => $expiresAt,
                'remaining_sessions' => $package->sessions,
                'total_sessions' => $package->sessions,
                'status' => UserPackage::STATUS_ACTIVE,
                'auto_renew' => false,
            ]);

            $userPackage->logHistory('assigned', [
                'price' => $package->price,
                'notes' => $request->input('notes'),
                'admin_id' => auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Package assigned successfully.',
            'data' => [
                'id' => $userPackage->id,
                'user_id' => $user->id,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'assigned_at' => $userPackage->assigned_date?->toISOString(),
                'starts_at' => $userPackage->assigned_date?->toISOString(),
                'expires_at' => $userPackage->expiry_date?->toDateString(),
                'total_sessions' => $userPackage->total_sessions,
                'remaining_sessions' => $userPackage->remaining_sessions,
                'status' => $userPackage->status,
            ],
        ], 201);
    }

    /**
     * Hard delete a specific user package by id, scoped to the given user.
     */
    public function destroy(Request $request, int $userId, int $userPackageId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $userPackage = UserPackage::where('id', $userPackageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userPackage) {
            return response()->json(['message' => 'User package not found'], 404);
        }

        $userPackage->delete(); // Hard delete; FKs are ON DELETE CASCADE for related tables

        return response()->json(['message' => 'User package deleted'], 200);
    }
} 