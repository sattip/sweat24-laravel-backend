<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::where('membership_type', 'Admin')->count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_revenue' => \App\Models\PaymentInstallment::where('status', 'paid')->sum('amount'),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Display a listing of admin users
     */
    public function index()
    {
        $admins = User::where('membership_type', 'Admin')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('admin.users.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin user
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created admin user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'membership_type' => 'Admin',
            'status' => 'active',
            'join_date' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user created successfully!');
    }

    /**
     * Show the form for editing an admin user
     */
    public function edit($id)
    {
        $admin = User::where('membership_type', 'Admin')->findOrFail($id);
        return view('admin.users.edit', compact('admin'));
    }

    /**
     * Update the specified admin user
     */
    public function update(Request $request, $id)
    {
        $admin = User::where('membership_type', 'Admin')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->status = $request->status;
        
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        
        $admin->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user updated successfully!');
    }

    /**
     * Remove the specified admin user
     */
    public function destroy($id)
    {
        $admin = User::where('membership_type', 'Admin')->findOrFail($id);
        
        // Prevent deleting yourself
        if ($admin->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account!');
        }
        
        $admin->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user deleted successfully!');
    }
}