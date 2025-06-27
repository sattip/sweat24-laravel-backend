<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
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
        
        $users = $query->with('packages', 'activityLogs')->paginate(15);
        
        return response()->json($users);
    }

    public function store(Request $request)
    {
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

    public function show(User $user)
    {
        return response()->json($user->load('packages', 'bookings', 'activityLogs'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'membership_type' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,expired',
            'medical_history' => 'nullable|string',
        ]);

        $user->update($validated);
        
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }
}