<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgeVerificationLog;
use App\Models\ParentConsent;
use App\Services\ActivityLogger;
use App\Services\ReferralService;
use App\Notifications\Auth\RegistrationConfirmationNotification;
use App\Notifications\Admin\NewRegistrationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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

        // Only allow admins and trainers to login to the panel
        if (!in_array($user->role, ['admin', 'trainer'])) {
            throw ValidationException::withMessages([
                'email' => ['Δεν έχετε πρόσβαση. Μόνο διαχειριστές και γυμναστές μπορούν να συνδεθούν σε αυτό το panel.'],
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
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                'date_of_birth' => $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : null,
                'gender' => $user->gender,
                'weight' => $user->weight,
                'height' => $user->height,
                'emergency_contact' => $user->emergency_contact,
                'emergency_phone' => $user->emergency_phone,
                'membership_type' => $user->membership_type,
                'role' => $user->role,
                'status' => $user->status,
                'registration_status' => $user->registration_status,
                'approved_at' => $user->approved_at ? $user->approved_at->toISOString() : null,
                'profile_last_updated' => $user->profile_last_updated ? $user->profile_last_updated->toISOString() : null,
                'is_minor' => $user->is_minor,
                'age_at_registration' => $user->age_at_registration,
                'remaining_sessions' => $user->remaining_sessions,
                'total_sessions' => $user->total_sessions,
                'join_date' => $user->join_date,
                'last_visit' => $user->last_visit,
                'medical_history' => $user->medical_history,
                'notes' => $user->notes,
                'has_signed_terms' => $user->approved_at ? 
                    $user->signatures()
                        ->where('document_type', 'terms_and_conditions')
                        ->where('signed_at', '>', $user->approved_at)
                        ->exists() : false,
                'terms_accepted_at' => $user->terms_accepted_at,
                'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
                'updated_at' => $user->updated_at ? $user->updated_at->toISOString() : null,
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
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                'date_of_birth' => $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : null,
                'gender' => $user->gender,
                'weight' => $user->weight,
                'height' => $user->height,
                'emergency_contact' => $user->emergency_contact,
                'emergency_phone' => $user->emergency_phone,
                'membership_type' => $user->membership_type,
                'role' => $user->role,
                'status' => $user->status,
                'registration_status' => $user->registration_status,
                'approved_at' => $user->approved_at ? $user->approved_at->toISOString() : null,
                'profile_last_updated' => $user->profile_last_updated ? $user->profile_last_updated->toISOString() : null,
                'is_minor' => $user->is_minor,
                'age_at_registration' => $user->age_at_registration,
                'remaining_sessions' => $user->remaining_sessions,
                'total_sessions' => $user->total_sessions,
                'join_date' => $user->join_date,
                'last_visit' => $user->last_visit,
                'medical_history' => $user->medical_history,
                'notes' => $user->notes,
                'has_signed_terms' => $user->approved_at ?
                    $user->signatures()
                        ->where('document_type', 'terms_and_conditions')
                        ->where('signed_at', '>', $user->approved_at)
                        ->exists() : false,
                'terms_accepted_at' => $user->terms_accepted_at,
                // Priority Booking fields
                'has_priority_booking' => $user->has_priority_booking,
                'priority_booking_expires_at' => $user->priority_booking_expires_at ? $user->priority_booking_expires_at->format('Y-m-d') : null,
                'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
                'updated_at' => $user->updated_at ? $user->updated_at->toISOString() : null,
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

        try {
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
                // Medical History (optional at registration)
                'medicalHistory' => 'nullable|array'
            ]);
        } catch (ValidationException $e) {
            // Custom error message for liability declaration
            $errors = $e->errors();
            if (isset($errors['medicalHistory.liability_declaration_accepted'])) {
                $errors['medicalHistory.liability_declaration_accepted'] = [
                    'Η αποδοχή της Υπεύθυνης Δήλωσης είναι υποχρεωτική'
                ];
            }
            if (isset($errors['medicalHistory.ems_liability_accepted'])) {
                $errors['medicalHistory.ems_liability_accepted'] = [
                    'Πρέπει να αποδεχθείτε την Υπεύθυνη Δήλωση EMS για να συνεχίσετε'
                ];
            }

            // Get the first error message dynamically
            $firstErrorMessage = 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα';
            if (!empty($errors)) {
                $firstErrorKey = array_key_first($errors);
                if (isset($errors[$firstErrorKey][0])) {
                    $firstErrorMessage = $errors[$firstErrorKey][0];
                }
            }

            return response()->json([
                'success' => false,
                'message' => $firstErrorMessage,
                'errors' => $errors
            ], 422);
        }

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
            'gender' => $request->gender ?? null,
            'weight' => $request->weight ?? null,
            'height' => $request->height ?? null,
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
            'profile_last_updated' => now(),
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

        // Process medical history if provided
        if ($request->has('medicalHistory')) {
            $medicalHistory = $request->medicalHistory;
            
            // Store medical history JSON (filtered)
            $medicalHistoryData = [
                'medical_conditions' => $medicalHistory['medical_conditions'] ?? [],
                'current_health_problems' => $medicalHistory['current_health_problems'] ?? [],
                'prescribed_medications' => $medicalHistory['prescribed_medications'] ?? [],
                'smoking' => $medicalHistory['smoking'] ?? [],
                'physical_activity' => $medicalHistory['physical_activity'] ?? [],
                'submitted_at' => $medicalHistory['submitted_at'] ?? now()->toISOString()
            ];
            
            // Update user with medical history and EMS data
            $user->update([
                'medical_history' => json_encode($medicalHistoryData),
                'ems_interest' => $medicalHistory['ems_interest'] ?? false,
                'liability_declaration_accepted' => $medicalHistory['liability_declaration_accepted'] ?? false,
                'ems_liability_accepted' => $medicalHistory['ems_liability_accepted'] ?? false,
                'ems_contraindications' => $medicalHistory['ems_contraindications'] ?? null,
                'emergency_contact' => $medicalHistory['emergency_contact']['name'] ?? null,
                'emergency_phone' => $medicalHistory['emergency_contact']['phone'] ?? null,
            ]);
            
            Log::info('Legacy registration with medical history processed', [
                'user_id' => $user->id,
                'ems_interest' => $user->ems_interest,
                'ems_liability_accepted' => $user->ems_liability_accepted
            ]);
        }

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

        // Send registration confirmation email to user
        $user->notify(new RegistrationConfirmationNotification($user));

        // Send notification to admin about new registration
        $admins = User::where('role', 'admin')->orWhere('membership_type', 'Admin')->get();
        Notification::send($admins, new NewRegistrationNotification($user));

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
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'weight' => 'nullable|numeric|between:30,300',
            'height' => 'nullable|numeric|between:100,250',
            'phone' => 'nullable|string|max:20',
            'signature' => 'required|string',
            'signedAt' => 'required|date',
            'documentType' => 'required|string',
            'documentVersion' => 'required|string',
            'medicalHistory' => 'nullable|array',
            // Medical History validation
            'medicalHistory.medical_conditions' => 'sometimes|array',
            'medicalHistory.current_health_problems' => 'sometimes|array',
            'medicalHistory.prescribed_medications' => 'sometimes|array',
            'medicalHistory.smoking' => 'sometimes|array',
            'medicalHistory.physical_activity' => 'sometimes|array',
            'medicalHistory.emergency_contact' => 'sometimes|array',
            'medicalHistory.emergency_contact.name' => 'nullable|string|max:255',
            'medicalHistory.emergency_contact.phone' => 'nullable|string|max:20',
            'medicalHistory.ems_interest' => 'sometimes|boolean',
            'medicalHistory.liability_declaration_accepted' => 'required|boolean|accepted',
            'medicalHistory.ems_liability_accepted' => 'sometimes|boolean',
            'medicalHistory.submitted_at' => 'sometimes|date'
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
        
        // Custom Greek validation messages
        $messages = [
            'firstName.required' => 'Το όνομα είναι υποχρεωτικό',
            'lastName.required' => 'Το επώνυμο είναι υποχρεωτικό',
            'email.required' => 'Το email είναι υποχρεωτικό',
            'email.email' => 'Παρακαλώ εισάγετε έγκυρο email',
            'email.unique' => 'Αυτό το email χρησιμοποιείται ήδη',
            'password.required' => 'Ο κωδικός είναι υποχρεωτικός',
            'password.min' => 'Ο κωδικός πρέπει να έχει τουλάχιστον :min χαρακτήρες',
            'birthDate.required' => 'Η ημερομηνία γέννησης είναι υποχρεωτική',
            'birthDate.before' => 'Η ημερομηνία γέννησης πρέπει να είναι στο παρελθόν',
            'signature.required' => 'Η υπογραφή είναι υποχρεωτική',
            'medicalHistory.liability_declaration_accepted.required' => 'Η αποδοχή της Υπεύθυνης Δήλωσης είναι υποχρεωτική',
            'medicalHistory.liability_declaration_accepted.accepted' => 'Η αποδοχή της Υπεύθυνης Δήλωσης είναι υποχρεωτική',
            'medicalHistory.ems_liability_accepted.accepted' => 'Πρέπει να αποδεχθείτε την Υπεύθυνη Δήλωση EMS για να συνεχίσετε',
            'parentConsent.required' => 'Απαιτείται συγκατάθεση γονέα για ανηλίκους',
            'parentConsent.parentFullName.required' => 'Το ονοματεπώνυμο γονέα είναι υποχρεωτικό',
            'parentConsent.parentIdNumber.required' => 'Ο αριθμός ταυτότητας γονέα είναι υποχρεωτικός',
            'parentConsent.parentIdNumber.unique' => 'Αυτός ο αριθμός ταυτότητας έχει ήδη χρησιμοποιηθεί',
            'parentConsent.parentPhone.required' => 'Το τηλέφωνο γονέα είναι υποχρεωτικό',
            'parentConsent.parentEmail.required' => 'Το email γονέα είναι υποχρεωτικό',
            'parentConsent.consentAccepted.accepted' => 'Πρέπει να αποδεχθείτε τη συγκατάθεση γονέα',
            'parentConsent.signature.required' => 'Η υπογραφή γονέα είναι υποχρεωτική',
        ];

        try {
            $validated = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            // Get the first error message dynamically
            $firstErrorMessage = 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα';
            if (!empty($errors)) {
                $firstErrorKey = array_key_first($errors);
                if (isset($errors[$firstErrorKey][0])) {
                    $firstErrorMessage = $errors[$firstErrorKey][0];
                }
            }

            return response()->json([
                'success' => false,
                'message' => $firstErrorMessage,
                'errors' => $errors
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $validated['firstName'] . ' ' . $validated['lastName'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $birthDate,
                'gender' => $validated['gender'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'height' => $validated['height'] ?? null,
                'is_minor' => $isMinor,
                'age_at_registration' => $age,
                'membership_type' => 'Basic',
                'role' => 'member',
                'join_date' => now(),
                'status' => 'pending_approval',
                'registration_status' => 'pending_approval',
                'remaining_sessions' => 0,
                'total_sessions' => 0,
                'medical_history' => null, // Will be processed separately below
                'profile_last_updated' => now(),
            ]);

            // Process medical history if provided
            if (isset($validated['medicalHistory'])) {
                $medicalHistory = $validated['medicalHistory'];
                

                // Store medical history JSON (filtered)
                $medicalHistoryData = [
                    'medical_conditions' => $this->convertConditionsToArray($medicalHistory['medical_conditions'] ?? []),
                    'current_health_problems' => $medicalHistory['current_health_problems'] ?? [],
                    'prescribed_medications' => $medicalHistory['prescribed_medications'] ?? [],
                    'smoking' => $medicalHistory['smoking'] ?? [],
                    'physical_activity' => $medicalHistory['physical_activity'] ?? [],
                    'submitted_at' => $medicalHistory['submitted_at'] ?? now()->toISOString()
                ];
                
                // Update user with medical history and EMS data
                $user->update([
                    'medical_history' => json_encode($medicalHistoryData),
                    'ems_interest' => $medicalHistory['ems_interest'] ?? false,
                    'liability_declaration_accepted' => $validated['medicalHistory']['liability_declaration_accepted'],
                    'ems_liability_accepted' => $medicalHistory['ems_liability_accepted'] ?? false,
                    'ems_contraindications' => $medicalHistory['ems_contraindications'] ?? null,
                    'emergency_contact' => $medicalHistory['emergency_contact']['name'] ?? null,
                    'emergency_phone' => $medicalHistory['emergency_contact']['phone'] ?? null,
                ]);
                
                Log::info('Registration with medical history processed', [
                    'user_id' => $user->id,
                    'ems_interest' => $user->ems_interest,
                    'ems_liability_accepted' => $user->ems_liability_accepted
                ]);
            }
            
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
                'ip_address' => $request->ip() ?: '127.0.0.1'
            ]);
            
            DB::commit();
            
            // Log the registration
            ActivityLogger::logRegistration($user);
            
            // Send registration confirmation email to user
            $user->notify(new RegistrationConfirmationNotification($user));

            // Send notification to admin about new registration
            $admins = User::where('role', 'admin')->orWhere('membership_type', 'Admin')->get();
            Notification::send($admins, new NewRegistrationNotification($user));
            
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

    /**
     * Convert medical conditions from object format to array format for storage
     */
    private function convertConditionsToArray($conditions)
    {
        if (!is_array($conditions)) {
            return [];
        }

        $result = [];
        foreach ($conditions as $name => $data) {
            if (is_array($data) && isset($data['has_condition']) && $data['has_condition']) {
                $result[] = [
                    'name' => $name,
                    'has_condition' => true,
                    'year_of_onset' => $data['year_of_onset'] ?? null,
                    'details' => $data['details'] ?? ''
                ];
            }
        }
        return $result;
    }
}