<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Signature;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Phase 1: Initial Registration - Create user with pending_terms status
     */
    public function initialRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'date_of_birth' => 'nullable|date|before:today',
                'address' => 'nullable|string|max:500',
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20',
                'medical_history' => 'nullable|string',
                'membership_type' => 'sometimes|string|in:Basic,Premium,VIP',
                'role' => 'sometimes|string|in:member,trainer,admin',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        DB::beginTransaction();
        try {
            // Create user with pending_approval status
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
                'medical_history' => $validated['medical_history'] ?? null,
                'membership_type' => $validated['membership_type'] ?? 'Basic',
                'role' => $validated['role'] ?? 'member',
                'join_date' => now()->toDateString(),
                'status' => 'inactive', // Will be activated after signature
                'registration_status' => 'pending_approval', // Changed to require admin approval first
                'remaining_sessions' => 0,
                'total_sessions' => 0,
                'notification_preferences' => [
                    'email' => true,
                    'sms' => false,
                    'in_app' => true,
                ],
                'privacy_settings' => [
                    'share_progress' => false,
                    'show_in_leaderboard' => false,
                ],
            ]);

            DB::commit();

            Log::info('Initial registration completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'registration_status' => $user->registration_status,
            ]);

            return $this->createdResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'registration_status' => $user->registration_status,
                    'next_step' => 'Waiting for admin approval',
                ],
                'registration_token' => $user->createToken('registration')->plainTextToken,
            ], 'Αρχική εγγραφή ολοκληρώθηκε επιτυχώς. Περιμένετε την έγκριση από τον διαχειριστή.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Initial registration failed', [
                'email' => $validated['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.');
        }
    }

    /**
     * Phase 1.5: Accept Terms and Conditions
     */
    public function acceptTerms(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'terms_version' => 'sometimes|string|max:10',
                'privacy_policy_version' => 'sometimes|string|max:10',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        $user = User::find($validated['user_id']);

        // Check if user is in correct status
        if ($user->registration_status !== 'pending_terms') {
            return $this->errorResponse('User is not in pending_terms status', 400);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'registration_status' => 'pending_signature',
                'terms_accepted_at' => now(),
            ]);

            DB::commit();

            Log::info('Terms accepted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'terms_version' => $validated['terms_version'] ?? '1.0',
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'registration_status' => $user->registration_status,
                    'terms_accepted_at' => $user->terms_accepted_at,
                    'next_step' => 'Provide digital signature to complete registration',
                ],
            ], 'Όροι αποδέχθηκαν επιτυχώς. Παρακαλώ προσθέστε την ψηφιακή σας υπογραφή.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Terms acceptance failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την αποδοχή των όρων.');
        }
    }

    /**
     * Phase 2: Final Activation - Save digital signature and activate user
     */
    public function completeRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'signature_data' => 'required|string', // Base64 encoded signature
                'document_type' => 'sometimes|string|max:100',
                'document_version' => 'sometimes|string|max:10',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        $user = User::find($validated['user_id']);

        // Check if user is in correct status
        if ($user->registration_status !== 'pending_signature') {
            return $this->errorResponse('User is not in pending_signature status', 400);
        }

        // Validate signature data format
        if (!$this->isValidBase64Image($validated['signature_data'])) {
            return $this->errorResponse('Invalid signature data format', 400);
        }

        DB::beginTransaction();
        try {
            // Save digital signature
            $signature = Signature::create([
                'user_id' => $user->id,
                'signature_data' => $validated['signature_data'],
                'ip_address' => $request->ip() ?: '127.0.0.1', // Fallback for testing
                'signed_at' => now(),
                'document_type' => $validated['document_type'] ?? 'terms_and_conditions',
                'document_version' => $validated['document_version'] ?? '1.0',
            ]);

            // Complete registration and activate user
            $user->update([
                'registration_status' => 'completed',
                'registration_completed_at' => now(),
                'status' => 'active',
                'email_verified_at' => now(), // Auto-verify email on registration completion
            ]);

            DB::commit();

            Log::info('Registration completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'signature_id' => $signature->id,
                'ip_address' => $request->ip(),
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'registration_status' => $user->registration_status,
                    'registration_completed_at' => $user->registration_completed_at,
                    'membership_type' => $user->membership_type,
                    'role' => $user->role,
                ],
                'signature' => [
                    'id' => $signature->id,
                    'signed_at' => $signature->signed_at,
                    'document_type' => $signature->document_type,
                    'document_version' => $signature->document_version,
                ],
                'api_token' => $user->createToken('api-access')->plainTextToken,
            ], 'Εγγραφή ολοκληρώθηκε επιτυχώς! Καλώς ήρθατε στο Sweat93!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Registration completion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την ολοκλήρωση της εγγραφής.');
        }
    }

    /**
     * Admin approval of user registration (Admin only)
     */
    public function approveUser(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'approval_notes' => 'sometimes|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        // Check if the requesting user is an admin
        $adminUser = $request->user();
        if (!$adminUser || !$adminUser->isAdmin()) {
            return $this->forbiddenResponse('Only administrators can approve user registrations');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        // Check if user is in correct status (check main status field for approval workflow)
        if ($user->status !== 'pending_approval') {
            return $this->errorResponse('User is not in pending_approval status', 400);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'status' => 'active',  // Change main status to active
                'registration_status' => 'pending_terms',
                'approved_at' => now(),
                'approved_by' => $adminUser->id,
            ]);

            DB::commit();

            Log::info('User registration approved', [
                'user_id' => $user->id,
                'email' => $user->email,
                'approved_by' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'approval_notes' => $validated['approval_notes'] ?? null,
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'registration_status' => $user->registration_status,
                    'approved_at' => $user->approved_at,
                    'approved_by' => [
                        'id' => $adminUser->id,
                        'name' => $adminUser->name,
                        'email' => $adminUser->email,
                    ],
                    'next_step' => 'User can now accept terms and conditions',
                ],
            ], 'Η εγγραφή χρήστη εγκρίθηκε επιτυχώς. Ο χρήστης μπορεί τώρα να προχωρήσει στην αποδοχή των όρων.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User approval failed', [
                'user_id' => $user->id,
                'admin_id' => $adminUser->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την έγκριση χρήστη.');
        }
    }

    /**
     * Reject user registration (Admin only)
     */
    public function rejectUser(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        // Check if the requesting user is an admin
        $adminUser = $request->user();
        if (!$adminUser || !$adminUser->isAdmin()) {
            return $this->forbiddenResponse('Only administrators can reject user registrations');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        // Check if user is in correct status (check main status field for rejection workflow)
        if ($user->status !== 'pending_approval') {
            return $this->errorResponse('User is not in pending_approval status', 400);
        }

        DB::beginTransaction();
        try {
            // Instead of deleting, we mark as rejected
            $user->update([
                'status' => 'inactive',
                'registration_status' => 'pending_approval', // Keep status but log rejection
                'notes' => ($user->notes ? $user->notes . "\n\n" : '') . 
                          "[REJECTED " . now()->format('Y-m-d H:i:s') . " by {$adminUser->name}]: " . 
                          $validated['rejection_reason'],
            ]);

            DB::commit();

            Log::info('User registration rejected', [
                'user_id' => $user->id,
                'email' => $user->email,
                'rejected_by' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'registration_status' => $user->registration_status,
                    'rejection_reason' => $validated['rejection_reason'],
                ],
            ], 'Η εγγραφή χρήστη απορρίφθηκε.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User rejection failed', [
                'user_id' => $user->id,
                'admin_id' => $adminUser->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την απόρριψη χρήστη.');
        }
    }

    /**
     * Get registration status for a user
     */
    public function getRegistrationStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'email' => 'sometimes|email|exists:users,email',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        if (isset($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        } elseif (isset($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        } else {
            return $this->errorResponse('Either user_id or email is required', 400);
        }

        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'registration_status' => $user->registration_status,
                'terms_accepted_at' => $user->terms_accepted_at,
                'registration_completed_at' => $user->registration_completed_at,
            ],
        ];

        // Add next step information
        switch ($user->registration_status) {
            case 'pending_approval':
                $response['next_step'] = 'Waiting for admin approval';
                if ($user->approvedBy) {
                    $response['approved_by'] = [
                        'id' => $user->approvedBy->id,
                        'name' => $user->approvedBy->name,
                        'approved_at' => $user->approved_at,
                    ];
                }
                break;
            case 'pending_terms':
                $response['next_step'] = 'Accept terms and conditions';
                break;
            case 'pending_signature':
                $response['next_step'] = 'Provide digital signature';
                break;
            case 'completed':
                $response['next_step'] = 'Registration is complete';
                // Include signature info if completed
                $signature = $user->signatures()->latest()->first();
                if ($signature) {
                    $response['signature'] = [
                        'signed_at' => $signature->signed_at,
                        'document_type' => $signature->document_type,
                        'document_version' => $signature->document_version,
                    ];
                }
                break;
        }

        return $this->successResponse($response, 'Registration status retrieved successfully');
    }

    /**
     * Validate if string is a valid base64 image
     */
    private function isValidBase64Image($data): bool
    {
        // Check if it's a valid base64 string
        if (!preg_match('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,/', $data)) {
            return false;
        }

        // Extract the base64 part
        $base64 = preg_replace('/^data:image\/[a-zA-Z+]+;base64,/', '', $data);
        
        // Validate base64
        $decoded = base64_decode($base64, true);
        return $decoded !== false && base64_encode($decoded) === $base64;
    }
}
