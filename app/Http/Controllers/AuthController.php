<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MedicalHistory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is still pending approval
        if ($user->status === 'pending_approval') {
            throw ValidationException::withMessages([
                'email' => ['Ο λογαριασμός σας περιμένει έγκριση από τον διαχειριστή.'],
            ]);
        }

        // Check if user is inactive
        if ($user->status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => ['Ο λογαριασμός σας είναι ανενεργός. Επικοινωνήστε με τον διαχειριστή.'],
            ]);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log the login activity
        ActivityLogger::logLogin($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'membership_type' => $user->membership_type,
                'role' => $user->role,
                'phone' => $user->phone,
                'status' => $user->status,
                'registration_status' => $user->registration_status,
                'remaining_sessions' => $user->remaining_sessions,
                'join_date' => $user->join_date,
                'last_visit' => $user->last_visit,
                'has_signed_terms' => $user->signatures()->where('document_type', 'terms_and_conditions')->exists(),
                'terms_accepted_at' => $user->terms_accepted_at,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Log the logout activity
        if ($user) {
            ActivityLogger::logLogout($user);
        }
        
        // Check if it's an API request
        if ($request->expectsJson()) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        }
        
        // Web logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/admin/login')->with('success', 'Logged out successfully');
    }
    
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();
            
            // Check if user is admin
            if ($user->membership_type !== 'Admin') {
                Auth::logout();
                return redirect()->back()->with('error', 'Unauthorized. Admin access only.');
            }
            
            $request->session()->regenerate();
            
            // Log the admin login activity
            ActivityLogger::logLogin($user);
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid credentials.');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'membership_type' => $user->membership_type,
                'role' => $user->role,
                'phone' => $user->phone,
                'status' => $user->status,
                'registration_status' => $user->registration_status,
                'remaining_sessions' => $user->remaining_sessions,
                'join_date' => $user->join_date,
                'last_visit' => $user->last_visit,
                'has_signed_terms' => $user->signatures()->where('document_type', 'terms_and_conditions')->exists(),
                'terms_accepted_at' => $user->terms_accepted_at,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'membership_type' => 'nullable|string',
            // Medical history validation
            'medical_history' => 'nullable|array',
            'medical_history.medical_conditions' => 'nullable|array',
            'medical_history.current_health_problems' => 'nullable|array',
            'medical_history.prescribed_medications' => 'nullable|array',
            'medical_history.smoking' => 'nullable|array',
            'medical_history.physical_activity' => 'nullable|array',
            'medical_history.emergency_contact' => 'nullable|array',
            'medical_history.liability_declaration_accepted' => 'nullable|boolean',
            'medical_history.submitted_at' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'membership_type' => $request->membership_type ?? 'Basic',
                'role' => 'member',
                'join_date' => now(),
                'status' => 'pending_approval',
                'registration_status' => 'pending_approval',
                'remaining_sessions' => 0,
                'total_sessions' => 0,
            ]);

            // Save medical history if provided
            if ($request->has('medical_history') && !empty($request->medical_history)) {
                $medicalHistory = $request->medical_history;
                
                MedicalHistory::create([
                    'user_id' => $user->id,
                    'medical_conditions' => $medicalHistory['medical_conditions'] ?? [],
                    'current_health_problems' => $medicalHistory['current_health_problems'] ?? [],
                    'prescribed_medications' => $medicalHistory['prescribed_medications'] ?? [],
                    'smoking' => $medicalHistory['smoking'] ?? [],
                    'physical_activity' => $medicalHistory['physical_activity'] ?? [],
                    'emergency_contact' => $medicalHistory['emergency_contact'] ?? [],
                    'liability_declaration_accepted' => $medicalHistory['liability_declaration_accepted'] ?? false,
                    'submitted_at' => isset($medicalHistory['submitted_at']) 
                        ? Carbon::parse($medicalHistory['submitted_at']) 
                        : now(),
                ]);
            }

            DB::commit();

            // Log the registration activity
            ActivityLogger::logRegistration($user);

            // Don't provide auth token for pending approval users
            return response()->json([
                'success' => true,
                'message' => 'Η εγγραφή σας υποβλήθηκε επιτυχώς. Περιμένετε την έγκριση από τον διαχειριστή.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'membership_type' => $user->membership_type,
                    'registration_status' => $user->registration_status,
                    'status' => $user->status,
                    'next_step' => 'Waiting for admin approval',
                ],
                'medical_history_saved' => $request->has('medical_history'),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            \Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.',
            ], 500);
        }
    }
}