<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
            'found_us_via' => 'nullable|string|in:facebook,instagram,google,friend,member,website,walk_in,flyer,event,other',
            'social_platform' => 'nullable|string|required_if:found_us_via,facebook,instagram',
            'referral_code_or_name' => 'nullable|string',
            'referrer_id' => 'nullable|exists:users,id',
        ]);

        $userData = [
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
            'found_us_via' => $request->found_us_via,
            'social_platform' => $request->social_platform,
            'referral_code_or_name' => $request->referral_code_or_name,
        ];

        // Handle referral validation
        if ($request->referrer_id) {
            $userData['referrer_id'] = $request->referrer_id;
            $userData['referral_validated'] = true;
            $userData['referral_validated_at'] = now();
        } elseif ($request->found_us_via === 'member' && $request->referral_code_or_name) {
            // Use the service to find the referrer
            $referrer = ReferralService::findReferrer($request->referral_code_or_name);

            if ($referrer) {
                $userData['referrer_id'] = $referrer->id;
                $userData['referral_validated'] = true;
                $userData['referral_validated_at'] = now();
            }
        }

        $user = User::create($userData);

        // Log the registration activity
        ActivityLogger::logRegistration($user);

        // Log referral activity if applicable
        if ($user->referrer_id) {
            ActivityLogger::log(
                'referral', // The type of activity
                'made_referral', // The action
                $user, // The new user is the subject of this log
                ['referred_user_name' => $user->name],
                $user->referrer_id // The ID of the user who made the referral
            );
        }

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
                'found_us_via' => $user->found_us_via,
                'referral_validated' => $user->referral_validated,
                'next_step' => 'Waiting for admin approval',
            ],
        ], 201);
    }
}