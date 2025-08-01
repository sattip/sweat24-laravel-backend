<?php

namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MedicalHistoryController extends Controller
{
    /**
     * Store medical history for authenticated user
     * POST /api/v1/medical-history
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Μη εξουσιοδοτημένη πρόσβαση'
                ], 401);
            }

            // Validate the incoming request
            $validated = $request->validate([
                'medical_conditions' => 'required|array',
                'medical_conditions.*.has_condition' => 'required|boolean',
                'medical_conditions.*.year_of_onset' => 'nullable|string|max:4',
                'medical_conditions.*.details' => 'nullable|string|max:1000',
                
                'current_health_problems' => 'required|array',
                'current_health_problems.has_problems' => 'required|boolean',
                'current_health_problems.details' => 'nullable|string|max:2000',
                
                'prescribed_medications' => 'required|array',
                'prescribed_medications.*.medication' => 'nullable|string|max:255',
                'prescribed_medications.*.reason' => 'nullable|string|max:255',
                
                'smoking' => 'required|array',
                'smoking.currently_smoking' => 'required|boolean',
                'smoking.daily_cigarettes' => 'nullable|integer|min:0|max:100',
                'smoking.ever_smoked' => 'nullable|boolean',
                'smoking.smoking_years' => 'nullable|string|max:10',
                'smoking.quit_years_ago' => 'nullable|string|max:10',
                
                'physical_activity' => 'required|array',
                'physical_activity.description' => 'nullable|string|max:1000',
                'physical_activity.frequency' => 'nullable|string|max:255',
                'physical_activity.duration' => 'nullable|string|max:255',
                
                'emergency_contact' => 'required|array',
                'emergency_contact.name' => 'required|string|max:255',
                'emergency_contact.phone' => 'required|string|max:20',
                
                'liability_declaration_accepted' => 'required|boolean|accepted',
                'submitted_at' => 'required|date'
            ]);

            DB::beginTransaction();

            try {
                // Delete any existing medical history for this user (keep only latest)
                MedicalHistory::where('user_id', $user->id)->delete();

                // Create new medical history record
                $medicalHistory = MedicalHistory::create([
                    'user_id' => $user->id,
                    'medical_conditions' => $validated['medical_conditions'],
                    'current_health_problems' => $validated['current_health_problems'],
                    'prescribed_medications' => $validated['prescribed_medications'],
                    'smoking' => $validated['smoking'],
                    'physical_activity' => $validated['physical_activity'],
                    'emergency_contact' => $validated['emergency_contact'],
                    'liability_declaration_accepted' => $validated['liability_declaration_accepted'],
                    'submitted_at' => Carbon::parse($validated['submitted_at'])
                ]);

                DB::commit();

                // Log successful submission
                Log::info('Medical history submitted successfully', [
                    'user_id' => $user->id,
                    'medical_history_id' => $medicalHistory->id,
                    'submitted_at' => $medicalHistory->submitted_at
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Το ιατρικό ιστορικό αποθηκεύτηκε επιτυχώς',
                    'data' => [
                        'id' => $medicalHistory->id,
                        'user_id' => $medicalHistory->user_id,
                        'submitted_at' => $medicalHistory->submitted_at->toISOString(),
                        'has_ems_contraindications' => $medicalHistory->hasEmsContraindications(),
                        'active_conditions_count' => count($medicalHistory->getActiveConditions())
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα επικύρωσης δεδομένων',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error storing medical history', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την αποθήκευση του ιατρικού ιστορικού'
            ], 500);
        }
    }

    /**
     * Get medical history for specific user (Admin only)
     * GET /api/admin/users/{userId}/medical-history
     */
    public function getUserMedicalHistory(Request $request, $userId): JsonResponse
    {
        try {
            // Find the user
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ο χρήστης δεν βρέθηκε'
                ], 404);
            }

            // Get the latest medical history
            $medicalHistory = MedicalHistory::getLatestForUser($userId);
            
            if (!$medicalHistory) {
                // For admin panel, return success with null data instead of 404
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Ο χρήστης δεν έχει υποβάλει ακόμα ιατρικό ιστορικό'
                ], 200);
            }

            // Prepare detailed response for admin panel
            $responseData = [
                'id' => $medicalHistory->id,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ],
                'medical_conditions' => $medicalHistory->medical_conditions,
                'current_health_problems' => $medicalHistory->current_health_problems,
                'prescribed_medications' => $medicalHistory->prescribed_medications,
                'smoking' => $medicalHistory->smoking,
                'physical_activity' => $medicalHistory->physical_activity,
                'emergency_contact' => $medicalHistory->emergency_contact,
                'liability_declaration_accepted' => $medicalHistory->liability_declaration_accepted,
                'submitted_at' => $medicalHistory->submitted_at->toISOString(),
                'created_at' => $medicalHistory->created_at->toISOString(),
                'updated_at' => $medicalHistory->updated_at->toISOString(),
                
                // Additional analysis for admin
                'analysis' => [
                    'has_ems_contraindications' => $medicalHistory->hasEmsContraindications(),
                    'active_conditions' => $medicalHistory->getActiveConditions(),
                    'total_active_conditions' => count($medicalHistory->getActiveConditions()),
                    'is_smoker' => $medicalHistory->smoking['currently_smoking'] ?? false,
                    'has_health_problems' => $medicalHistory->current_health_problems['has_problems'] ?? false,
                    'emergency_contact_available' => !empty($medicalHistory->emergency_contact['name'] ?? null)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving medical history for admin', [
                'user_id' => $userId,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση του ιατρικού ιστορικού'
            ], 500);
        }
    }

    /**
     * Get medical history for authenticated user
     * GET /api/v1/medical-history
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Μη εξουσιοδοτημένη πρόσβαση'
                ], 401);
            }

            $medicalHistory = MedicalHistory::getLatestForUser($user->id);
            
            if (!$medicalHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Δεν έχετε υποβάλει ιατρικό ιστορικό'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $medicalHistory->id,
                    'medical_conditions' => $medicalHistory->medical_conditions,
                    'current_health_problems' => $medicalHistory->current_health_problems,
                    'prescribed_medications' => $medicalHistory->prescribed_medications,
                    'smoking' => $medicalHistory->smoking,
                    'physical_activity' => $medicalHistory->physical_activity,
                    'emergency_contact' => $medicalHistory->emergency_contact,
                    'liability_declaration_accepted' => $medicalHistory->liability_declaration_accepted,
                    'submitted_at' => $medicalHistory->submitted_at->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving user medical history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση του ιατρικού ιστορικού'
            ], 500);
        }
    }
}
