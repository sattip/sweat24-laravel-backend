<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MedicalHistoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get EMS contraindications list
     */
    public function getEmsContraindications()
    {
        $absoluteContraindications = config('medical.ems_contraindications.absolute', []);
        $relativeContraindications = config('medical.ems_contraindications.relative', []);

        return $this->successResponse([
            'absolute_contraindications' => $absoluteContraindications,
            'relative_contraindications' => $relativeContraindications
        ], 'EMS contraindications list retrieved successfully');
    }

    /**
     * Submit medical history including EMS data
     */
    public function submitMedicalHistory(Request $request)
    {
        try {
            $currentYear = date('Y');
            
            $validated = $request->validate([
                // Existing medical history fields
                'medical_conditions' => 'nullable|array',
                'medical_conditions.*.has_condition' => 'required|boolean',
                'medical_conditions.*.year_of_onset' => 'nullable|string',
                'medical_conditions.*.details' => 'nullable|string',
                
                'current_health_problems' => 'nullable|array',
                'current_health_problems.has_problems' => 'required|boolean',
                'current_health_problems.details' => 'nullable|string',
                
                'prescribed_medications' => 'nullable|array',
                'prescribed_medications.*.medication' => 'required|string',
                'prescribed_medications.*.reason' => 'required|string',
                
                'smoking' => 'nullable|array',
                'smoking.currently_smoking' => 'required|boolean',
                'smoking.daily_cigarettes' => 'nullable|integer',
                'smoking.ever_smoked' => 'required|boolean',
                'smoking.smoking_years' => 'nullable|string',
                'smoking.quit_years_ago' => 'nullable|string',
                
                'physical_activity' => 'nullable|array',
                'physical_activity.description' => 'nullable|string',
                'physical_activity.frequency' => 'nullable|string',
                'physical_activity.duration' => 'nullable|string',
                
                'emergency_contact' => 'nullable|array',
                'emergency_contact.name' => 'required|string',
                'emergency_contact.phone' => 'required|string',
                
                // EMS fields
                'ems_interest' => 'required|boolean',
                'ems_contraindications' => 'required_if:ems_interest,true|nullable|array',
                'ems_contraindications.*.has_condition' => 'required|boolean',
                'ems_contraindications.*.year_of_onset' => 'nullable|integer|min:1900|max:' . $currentYear,
                // Liability declaration (single checkbox used for ALL users)
                'ems_liability_accepted' => 'required|accepted',
                
                // General fields
                'submitted_at' => 'required|date'
            ]);
        } catch (ValidationException $e) {
            // Custom error message for liability declaration (single checkbox)
            $errors = $e->errors();
            if (isset($errors['ems_liability_accepted'])) {
                $errors['ems_liability_accepted'] = [
                    'Πρέπει να αποδεχθείτε την Υπεύθυνη Δήλωση για να συνεχίσετε'
                ];
            }
            return $this->validationErrorResponse($errors, 'Η αποδοχή της Υπεύθυνης Δήλωσης είναι υποχρεωτική');
        }

        $user = Auth::user();
        if (!$user) {
            return $this->unauthorizedResponse('Authentication required');
        }

        DB::beginTransaction();
        try {
            // Prepare medical history JSON
            $medicalHistory = [
                'medical_conditions' => $this->convertConditionsToArray($validated['medical_conditions'] ?? []),
                'current_health_problems' => $validated['current_health_problems'] ?? [],
                'prescribed_medications' => $validated['prescribed_medications'] ?? [],
                'smoking' => $validated['smoking'] ?? [],
                'physical_activity' => $validated['physical_activity'] ?? [],
                'submitted_at' => $validated['submitted_at']
            ];

            // Update user with medical history and EMS data
            $updateData = [
                'medical_history' => json_encode($medicalHistory),
                'ems_interest' => $validated['ems_interest'],
                'emergency_contact' => $validated['emergency_contact']['name'] ?? null,
                'emergency_phone' => $validated['emergency_contact']['phone'] ?? null,
            ];

            // Only add EMS contraindications if user has EMS interest
            if ($validated['ems_interest']) {
                $updateData['ems_contraindications'] = $validated['ems_contraindications'];
            } else {
                $updateData['ems_contraindications'] = null;
            }
            // Liability acceptance applies to all users
            $updateData['ems_liability_accepted'] = $validated['ems_liability_accepted'];

            $user->update($updateData);

            // Log the submission
            Log::info('Medical history submitted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ems_interest' => $validated['ems_interest'],
                'has_ems_contraindications' => $user->hasEmsContraindications(),
                'submitted_at' => $validated['submitted_at']
            ]);

            DB::commit();

            return $this->successResponse([
                'success' => true,
                'message' => 'Το ιατρικό ιστορικό υποβλήθηκε επιτυχώς',
                'data' => [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'ems_interest' => $user->ems_interest,
                    'has_ems_contraindications' => $user->hasEmsContraindications(),
                    'active_conditions_count' => $this->countActiveConditions($medicalHistory['medical_conditions'])
                ]
            ], 'Το ιατρικό ιστορικό υποβλήθηκε επιτυχώς');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Medical history submission failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά την υποβολή του ιατρικού ιστορικού');
        }
    }

    /**
     * Get medical history for a user
     */
    public function getMedicalHistory(Request $request, $userId = null)
    {
        // If no userId provided, get current user's history
        if (!$userId) {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedResponse('Authentication required');
            }
        } else {
            // Check permissions if trying to access another user's history
            $requestingUser = Auth::user();
            if (!$requestingUser) {
                return $this->unauthorizedResponse('Authentication required');
            }

            // Only admins and trainers can view other users' medical history
            if (!$requestingUser->isAdmin() && !$requestingUser->isTrainer()) {
                return $this->forbiddenResponse('Insufficient permissions to view medical history');
            }

            $user = User::find($userId);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }
        }

        try {
            // Parse medical history JSON if exists
            $medicalHistory = [];
            if ($user->medical_history) {
                $medicalHistory = is_string($user->medical_history) 
                    ? json_decode($user->medical_history, true) 
                    : $user->medical_history;
            }

            $response = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'medical_history' => $medicalHistory,
                'emergency_contact' => [
                    'name' => $user->emergency_contact,
                    'phone' => $user->emergency_phone
                ],
                'ems_data' => [
                    'ems_interest' => $user->ems_interest,
                    'ems_contraindications' => $user->ems_contraindications,
                    'ems_liability_accepted' => $user->ems_liability_accepted,
                    'doctor_certificate_path' => $user->doctor_certificate_path,
                    'has_ems_contraindications' => $user->hasEmsContraindications(),
                    'ems_contraindications_list' => $user->getEmsContraindicationsList()
                ]
            ];

            return $this->successResponse($response, 'Medical history retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve medical history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return $this->serverErrorResponse('Failed to retrieve medical history');
        }
    }

    /**
     * Update medical history (partial update)
     */
    public function updateMedicalHistory(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->unauthorizedResponse('Authentication required');
        }

        try {
            $currentYear = date('Y');
            
            $validated = $request->validate([
                'ems_interest' => 'sometimes|boolean',
                'ems_contraindications' => 'sometimes|nullable|array',
                'ems_contraindications.*.has_condition' => 'required|boolean',
                'ems_contraindications.*.year_of_onset' => 'nullable|integer|min:1900|max:' . $currentYear,
                'ems_liability_accepted' => 'sometimes|nullable|boolean',
                'emergency_contact' => 'sometimes|array',
                'emergency_contact.name' => 'required|string',
                'emergency_contact.phone' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        DB::beginTransaction();
        try {
            $updateData = [];

            if (isset($validated['ems_interest'])) {
                $updateData['ems_interest'] = $validated['ems_interest'];
                
                if ($validated['ems_interest']) {
                    if (isset($validated['ems_contraindications'])) {
                        $updateData['ems_contraindications'] = $validated['ems_contraindications'];
                    }
                    if (isset($validated['ems_liability_accepted'])) {
                        $updateData['ems_liability_accepted'] = $validated['ems_liability_accepted'];
                    }
                } else {
                    // If EMS interest is false, clear EMS-related fields
                    $updateData['ems_contraindications'] = null;
                    $updateData['ems_liability_accepted'] = null;
                }
            }

            if (isset($validated['emergency_contact'])) {
                $updateData['emergency_contact'] = $validated['emergency_contact']['name'];
                $updateData['emergency_phone'] = $validated['emergency_contact']['phone'];
            }

            $user->update($updateData);

            DB::commit();

            return $this->successResponse([
                'success' => true,
                'message' => 'Medical history updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'ems_interest' => $user->ems_interest,
                    'has_ems_contraindications' => $user->hasEmsContraindications()
                ]
            ], 'Medical history updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Medical history update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return $this->serverErrorResponse('Failed to update medical history');
        }
    }

    /**
     * Helper method to count active medical conditions
     */
    private function countActiveConditions($conditions)
    {
        if (!is_array($conditions)) {
            return 0;
        }

        $count = 0;
        foreach ($conditions as $condition) {
            if (isset($condition['has_condition']) && $condition['has_condition'] === true) {
                $count++;
            }
        }
        return $count;
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

    /**
     * Upload doctor certificate file
     */
    public function uploadDoctorCertificate(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->unauthorizedResponse('Authentication required');
        }

        // Log request details
        Log::info('Doctor certificate upload attempt', [
            'user_id' => $user->id,
            'has_file' => $request->hasFile('doctor_certificate'),
            'files' => $request->allFiles(),
            'all_inputs' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'doctor_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
                'user_id' => 'sometimes|exists:users,id'
            ]);

            // Check if admin/trainer is uploading for another user
            $targetUserId = $validated['user_id'] ?? $user->id;
            if ($targetUserId != $user->id) {
                // Only admins and trainers can upload for other users
                if (!$user->isAdmin() && !$user->isTrainer()) {
                    return $this->forbiddenResponse('Insufficient permissions');
                }
                $targetUser = User::find($targetUserId);
                if (!$targetUser) {
                    return $this->notFoundResponse('Target user not found');
                }
            } else {
                $targetUser = $user;
            }

            // Delete old certificate if exists
            if ($targetUser->doctor_certificate_path) {
                $oldPath = storage_path('app/public/' . $targetUser->doctor_certificate_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Store the file
            $file = $request->file('doctor_certificate');
            $fileName = 'doctor_cert_' . $targetUser->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('doctor_certificates', $fileName, 'public');

            // Update user record
            $targetUser->update([
                'doctor_certificate_path' => $filePath
            ]);

            Log::info('Doctor certificate uploaded', [
                'user_id' => $targetUser->id,
                'uploaded_by' => $user->id,
                'file_path' => $filePath
            ]);

            return $this->successResponse([
                'success' => true,
                'message' => 'Το χαρτί γιατρού μεταφορτώθηκε επιτυχώς',
                'data' => [
                    'doctor_certificate_path' => $filePath,
                    'doctor_certificate_url' => asset('storage/' . $filePath)
                ]
            ], 'Το χαρτί γιατρού μεταφορτώθηκε επιτυχώς');

        } catch (ValidationException $e) {
            Log::warning('Doctor certificate validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
                'has_file' => $request->hasFile('doctor_certificate')
            ]);
            return $this->validationErrorResponse($e->errors(), 'Μη έγκυρο αρχείο');
        } catch (\Exception $e) {
            Log::error('Doctor certificate upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά τη μεταφόρτωση του αρχείου');
        }
    }

    /**
     * Delete doctor certificate file
     */
    public function deleteDoctorCertificate(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->unauthorizedResponse('Authentication required');
        }

        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id'
            ]);

            // Check if admin/trainer is deleting for another user
            $targetUserId = $validated['user_id'] ?? $user->id;
            if ($targetUserId != $user->id) {
                // Only admins and trainers can delete for other users
                if (!$user->isAdmin() && !$user->isTrainer()) {
                    return $this->forbiddenResponse('Insufficient permissions');
                }
                $targetUser = User::find($targetUserId);
                if (!$targetUser) {
                    return $this->notFoundResponse('Target user not found');
                }
            } else {
                $targetUser = $user;
            }

            // Delete file if exists
            if ($targetUser->doctor_certificate_path) {
                $filePath = storage_path('app/public/' . $targetUser->doctor_certificate_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Clear database record
                $targetUser->update([
                    'doctor_certificate_path' => null
                ]);

                Log::info('Doctor certificate deleted', [
                    'user_id' => $targetUser->id,
                    'deleted_by' => $user->id
                ]);

                return $this->successResponse([
                    'success' => true,
                    'message' => 'Το χαρτί γιατρού διαγράφηκε επιτυχώς'
                ], 'Το χαρτί γιατρού διαγράφηκε επιτυχώς');
            }

            return $this->notFoundResponse('Δεν βρέθηκε χαρτί γιατρού');

        } catch (\Exception $e) {
            Log::error('Doctor certificate deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return $this->serverErrorResponse('Σφάλμα κατά τη διαγραφή του αρχείου');
        }
    }
}