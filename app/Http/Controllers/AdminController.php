<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    use ApiResponseTrait;

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

    /**
     * Approve a pending user
     */
    public function approveUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Check if user is in pending_approval status
            if ($user->status !== 'pending_approval') {
                return $this->errorResponse(
                    'Ο χρήστης δεν είναι σε κατάσταση αναμονής έγκρισης',
                    400
                );
            }

            // Update user status to active
            $user->update([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            Log::info('👤 User approved by admin', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'admin_id' => $request->user()->id,
                'admin_email' => $request->user()->email,
                'timestamp' => now()
            ]);

            return $this->successResponse([
                'message' => 'Ο χρήστης εγκρίθηκε επιτυχώς',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'membership_type' => $user->membership_type,
                    'join_date' => $user->join_date,
                    'approved_at' => $user->approved_at,
                    'approved_by' => $user->approved_by,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Ο χρήστης δεν βρέθηκε');
        } catch (\Exception $e) {
            Log::error('Error approving user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id ?? null,
            ]);

            return $this->serverErrorResponse('Παρουσιάστηκε σφάλμα κατά την έγκριση του χρήστη');
        }
    }

    /**
     * Reject a pending user
     */
    public function rejectUser(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $user = User::findOrFail($id);

            // Check if user is in pending_approval status
            if ($user->status !== 'pending_approval') {
                return $this->errorResponse(
                    'Ο χρήστης δεν είναι σε κατάσταση αναμονής έγκρισης',
                    400
                );
            }

            $reason = $request->input('reason', 'Δεν καλύπτει τα κριτήρια εγγραφής');

            // Log the rejection reason in notes field
            $user->update([
                'status' => 'inactive',
                'notes' => ($user->notes ? $user->notes . ' | ' : '') . "ΑΠΟΡΡΙΦΘΗΚΕ: {$reason} (από {$request->user()->name} στις " . now()->format('d/m/Y H:i') . ")",
            ]);

            Log::info('👤 User rejected by admin', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'admin_id' => $request->user()->id,
                'admin_email' => $request->user()->email,
                'reason' => $reason,
                'timestamp' => now()
            ]);

            return $this->successResponse([
                'message' => 'Ο χρήστης απορρίφθηκε',
                'reason' => $reason,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Ο χρήστης δεν βρέθηκε');
        } catch (\Exception $e) {
            Log::error('Error rejecting user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id ?? null,
            ]);

            return $this->serverErrorResponse('Παρουσιάστηκε σφάλμα κατά την απόρριψη του χρήστη');
        }
    }
}