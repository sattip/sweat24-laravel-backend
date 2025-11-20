<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Only admin can list all users
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = User::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Sort by registration date (created_at) in descending order by default
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy('created_at', $sortOrder);
        
        $users = $query->with('packages', 'activityLogs')->paginate(20);
        
        return response()->json($users);
    }

    public function store(Request $request)
    {
        // Only admin can create users
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:8',
            'membership_type' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['join_date'] = now();

        $user = User::create($validated);
        
        return response()->json($user, 201);
    }

    public function show(Request $request, User $user)
    {
        // Only admin or the user themselves can view
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $userData = $user->load('packages', 'bookings', 'activityLogs', 'parentConsent')->toArray();
        
        // Ensure medical_history is decoded as JSON object, not string
        if (isset($userData['medical_history']) && is_string($userData['medical_history'])) {
            $userData['medical_history'] = json_decode($userData['medical_history'], true);
        }
        
        return response()->json($userData);
    }

    public function update(Request $request, User $user)
    {
        // Only admin or the user themselves can update
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'membership_type' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,expired',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'weight' => 'nullable|numeric|between:30,300',
            'height' => 'nullable|numeric|between:100,250',
            'medical_history' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'emergency_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Handle password update if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        // Add profile update timestamp
        $validated['profile_last_updated'] = now();
        
        $user->update($validated);
        
        // Log the update activity
        ActivityLogger::log(
            'user_management',
            'updated user profile',
            $user,
            ['changes' => $validated]
        );
        
        return response()->json($user->load('packages', 'bookings'));
    }

    public function destroy(Request $request, User $user)
    {
        // Only admin can delete users
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }
}