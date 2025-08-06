<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgeVerificationLog;
use App\Models\ParentConsent;
use App\Services\ActivityLogger;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * @deprecated Use registerWithConsent() instead to ensure proper age verification
     * This endpoint is maintained for backward compatibility but should not be used for new registrations
     */
    public function register(Request $request)
    {
        // Log deprecation warning
        Log::warning('Deprecated registration endpoint used', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'membership_type' => 'nullable|string',
            'date_of_birth' => 'required|date|before:today', // Now required to check age
            'found_us_via' => 'nullable|string|in:facebook,instagram,google,friend,member,website,walk_in,flyer,event,other',
            'social_platform' => 'nullable|string|required_if:found_us_via,facebook,instagram',
            'referral_code_or_name' => 'nullable|string',
            'referrer_id' => 'nullable|exists:users,id',
        ]);

        // Check if user is minor - if so, redirect to proper endpoint
        if ($request->has('date_of_birth')) {
            $birthDate = Carbon::parse($request->date_of_birth);
            $age = $birthDate->age;
            
            if ($age < 18) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minor registration requires parent consent. Please use the /register-with-consent endpoint.',
                    'requires_parent_consent' => true,
                    'age' => $age
                ], 422);
            }
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth ?? null,
            'is_minor' => false,
            'age_at_registration' => isset($birthDate) ? $birthDate->age : null,
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
    
    /**
     * Check if a user is a minor based on birth date
     * CRITICAL: Age calculation must be done on server for legal validity
     */
    public function checkAge(Request $request)
    {
        $request->validate([
            'birth_date' => 'required|date|before:today'
        ]);
        
        $birthDate = Carbon::parse($request->birth_date);
        $serverDate = Carbon::now();
        $age = $birthDate->age;
        $isMinor = $age < 18;
        
        // Log age verification for audit trail
        AgeVerificationLog::create([
            'birth_date' => $birthDate->toDateString(),
            'calculated_age' => $age,
            'is_minor' => $isMinor,
            'server_date' => $serverDate->toDateString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        Log::info('Age verification performed', [
            'birth_date' => $birthDate->toDateString(),
            'calculated_age' => $age,
            'is_minor' => $isMinor,
            'ip' => $request->ip()
        ]);
        
        return response()->json([
            'is_minor' => $isMinor,
            'age' => $age,
            'server_date' => $serverDate->toDateString()
        ]);
    }
    
    /**
     * Enhanced registration with parent consent support
     */
    public function registerWithConsent(Request $request)
    {
        // Basic validation
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'birthDate' => 'required|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'phone' => 'nullable|string|max:20',
            'signature' => 'required|string',
            'signedAt' => 'required|date',
            'documentType' => 'required|string',
            'documentVersion' => 'required|string',
            'medicalHistory' => 'nullable|array'
        ];
        
        // Check if user is minor
        $birthDate = Carbon::parse($request->birthDate);
        $age = $birthDate->age;
        $isMinor = $age < 18;
        
        // Add parent consent validation if minor - REQUIRED for minors
        if ($isMinor) {
            $rules['parentConsent'] = 'required|array';
            $rules['parentConsent.parentFullName'] = 'required|string|max:255';
            $rules['parentConsent.fatherFirstName'] = 'required|string|max:100';
            $rules['parentConsent.fatherLastName'] = 'required|string|max:100';
            $rules['parentConsent.motherFirstName'] = 'required|string|max:100';
            $rules['parentConsent.motherLastName'] = 'required|string|max:100';
            $rules['parentConsent.parentBirthDate'] = 'required|date|before:' . now()->subYears(18)->toDateString();
            $rules['parentConsent.parentIdNumber'] = 'required|string|max:20|unique:parent_consents,parent_id_number';
            $rules['parentConsent.parentPhone'] = 'required|string|max:20';
            $rules['parentConsent.parentLocation'] = 'required|string|max:100';
            $rules['parentConsent.parentStreet'] = 'required|string|max:255';
            $rules['parentConsent.parentStreetNumber'] = 'required|string|max:20';
            $rules['parentConsent.parentPostalCode'] = 'required|string|max:10';
            $rules['parentConsent.parentEmail'] = 'required|email|max:255';
            $rules['parentConsent.consentAccepted'] = 'required|boolean|accepted';
            $rules['parentConsent.signature'] = 'required|string';
        }
        
        $validated = $request->validate($rules);
        
        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $validated['firstName'] . ' ' . $validated['lastName'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $birthDate,
                'is_minor' => $isMinor,
                'age_at_registration' => $age,
                'membership_type' => 'Basic',
                'role' => 'member',
                'join_date' => now(),
                'status' => 'pending_approval',
                'registration_status' => 'pending_approval',
                'remaining_sessions' => 0,
                'total_sessions' => 0,
                'medical_history' => isset($validated['medicalHistory']) ? json_encode($validated['medicalHistory']) : null
            ]);
            
            // Create parent consent if minor
            if ($isMinor && isset($validated['parentConsent'])) {
                $parentConsent = $validated['parentConsent'];
                
                ParentConsent::create([
                    'user_id' => $user->id,
                    'parent_full_name' => $parentConsent['parentFullName'],
                    'father_first_name' => $parentConsent['fatherFirstName'],
                    'father_last_name' => $parentConsent['fatherLastName'],
                    'mother_first_name' => $parentConsent['motherFirstName'],
                    'mother_last_name' => $parentConsent['motherLastName'],
                    'parent_birth_date' => $parentConsent['parentBirthDate'],
                    'parent_id_number' => $parentConsent['parentIdNumber'],
                    'parent_phone' => $parentConsent['parentPhone'],
                    'parent_location' => $parentConsent['parentLocation'],
                    'parent_street' => $parentConsent['parentStreet'],
                    'parent_street_number' => $parentConsent['parentStreetNumber'],
                    'parent_postal_code' => $parentConsent['parentPostalCode'],
                    'parent_email' => $parentConsent['parentEmail'],
                    'consent_accepted' => true,
                    'signature' => $parentConsent['signature'],
                    'consent_text' => 'Parent consent for minor registration',
                    'consent_version' => '1.0',
                    'server_timestamp' => now()
                ]);
                
                Log::info('Minor registration with parent consent', [
                    'user_id' => $user->id,
                    'age' => $age,
                    'parent_id' => $parentConsent['parentIdNumber']
                ]);
            }
            
            // Create signature record
            $user->signatures()->create([
                'signature_data' => $validated['signature'],
                'signed_at' => $validated['signedAt'],
                'document_type' => $validated['documentType'],
                'document_version' => $validated['documentVersion'],
                'ip_address' => $request->ip()
            ]);
            
            DB::commit();
            
            // Log the registration
            ActivityLogger::logRegistration($user);
            
            return response()->json([
                'success' => true,
                'message' => $isMinor 
                    ? 'Η εγγραφή του ανηλίκου υποβλήθηκε επιτυχώς με γονική συγκατάθεση. Περιμένετε την έγκριση από τον διαχειριστή.'
                    : 'Η εγγραφή σας υποβλήθηκε επιτυχώς. Περιμένετε την έγκριση από τον διαχειριστή.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_minor' => $user->is_minor,
                    'age_at_registration' => $user->age_at_registration,
                    'status' => $user->status,
                    'registration_status' => $user->registration_status
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}