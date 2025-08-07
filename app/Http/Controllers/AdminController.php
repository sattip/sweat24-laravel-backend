<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Signature;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Get full user profile with all related data for admin panel
     */
    public function getUserFullProfile(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::with([
                'parentConsent',
                'signatures',
                'referrer'
            ])->findOrFail($userId);

            // Build the response structure
            $response = [
                'id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'is_minor' => $user->is_minor,
                'registration_date' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                'signature_url' => null,
                'guardian_details' => null,
                'medical_history' => null,
                'found_us_via' => null,
            ];

            // Add user's signature URL if exists
            $userSignature = $user->signatures()
                ->where('document_type', 'terms_and_conditions')
                ->latest()
                ->first();
            
            if ($userSignature) {
                $response['signature_url'] = $this->processSignature($userSignature, $user->id, 'user');
            }

            // Add guardian details if user is a minor
            if ($user->is_minor && $user->parentConsent) {
                $parentConsent = $user->parentConsent;
                $guardianSignatureUrl = null;
                
                // Process parent signature if exists
                if ($parentConsent->signature) {
                    $guardianSignatureUrl = $this->processSignature(
                        (object)['signature_data' => $parentConsent->signature],
                        $user->id,
                        'guardian'
                    );
                }

                $response['guardian_details'] = [
                    'full_name' => $parentConsent->parent_full_name,
                    'father_name' => trim(($parentConsent->father_first_name ?? '') . ' ' . ($parentConsent->father_last_name ?? '')),
                    'mother_name' => trim(($parentConsent->mother_first_name ?? '') . ' ' . ($parentConsent->mother_last_name ?? '')),
                    'birth_date' => $parentConsent->parent_birth_date ? $parentConsent->parent_birth_date->format('Y-m-d') : null,
                    'id_number' => $parentConsent->parent_id_number,
                    'phone' => $parentConsent->parent_phone,
                    'address' => trim(($parentConsent->parent_street ?? '') . ' ' . ($parentConsent->parent_street_number ?? '')),
                    'city' => $parentConsent->parent_location,
                    'zip_code' => $parentConsent->parent_postal_code,
                    'email' => $parentConsent->parent_email,
                    'consent_date' => $parentConsent->server_timestamp ? $parentConsent->server_timestamp->toIso8601String() : null,
                    'signature_url' => $guardianSignatureUrl,
                ];
            }

            // Add medical history (EMS) if user has interest
            if ($user->ems_interest) {
                $response['medical_history'] = [
                    'has_ems_interest' => true,
                    'ems_contraindications' => $user->ems_contraindications ?? [],
                    'ems_liability_accepted' => $user->ems_liability_accepted ?? false,
                    'other_medical_data' => [
                        'medical_conditions' => [
                            'medical_history' => $user->medical_history,
                            'emergency_contact' => $user->emergency_contact,
                            'emergency_phone' => $user->emergency_phone,
                        ],
                        'emergency_contact' => [
                            'name' => $user->emergency_contact,
                            'phone' => $user->emergency_phone,
                        ]
                    ]
                ];
            }

            // Add referral/how found us information
            if ($user->found_us_via) {
                $foundUsData = [
                    'source' => $user->found_us_via,
                    'referrer_info' => null,
                    'sub_source' => null,
                ];

                // If referred by someone
                if (in_array($user->found_us_via, ['friend', 'member', 'Σύσταση']) || $user->referrer_id) {
                    $referrer = $user->referrer;
                    if ($referrer) {
                        $foundUsData['referrer_info'] = [
                            'referrer_id' => $referrer->id,
                            'referrer_name' => $referrer->name,
                            'code_or_name_used' => $user->referral_code_or_name,
                        ];
                    } else if ($user->referral_code_or_name) {
                        $foundUsData['referrer_info'] = [
                            'referrer_id' => null,
                            'referrer_name' => $user->referral_code_or_name,
                            'code_or_name_used' => $user->referral_code_or_name,
                        ];
                    }
                }

                // If from social media
                if (in_array($user->found_us_via, ['facebook', 'instagram', 'Social'])) {
                    $foundUsData['sub_source'] = $user->social_platform ?? $user->found_us_via;
                }

                $response['found_us_via'] = $foundUsData;
            }

            return $this->successResponse($response);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Ο χρήστης δεν βρέθηκε');
        } catch (\Exception $e) {
            Log::error('Error fetching user full profile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverErrorResponse('Παρουσιάστηκε σφάλμα κατά την ανάκτηση του προφίλ χρήστη');
        }
    }

    /**
     * Process signature data (convert Base64 to file URL)
     */
    private function processSignature($signatureObject, $userId, $type = 'user')
    {
        try {
            $signatureData = $signatureObject->signature_data ?? $signatureObject->signature ?? null;
            
            if (!$signatureData) {
                return null;
            }

            // Check if it's already a URL
            if (filter_var($signatureData, FILTER_VALIDATE_URL)) {
                return $signatureData;
            }

            // Check if it's Base64 data
            if (strpos($signatureData, 'data:image') === 0) {
                // Extract the base64 content
                $parts = explode(',', $signatureData);
                if (count($parts) !== 2) {
                    return null;
                }

                $base64Data = $parts[1];
                $imageData = base64_decode($base64Data);
                
                // Determine file extension from mime type
                $mimeType = null;
                if (preg_match('/^data:image\/(\w+);base64/', $signatureData, $matches)) {
                    $mimeType = $matches[1];
                }
                
                $extension = $mimeType === 'jpeg' ? 'jpg' : ($mimeType ?? 'png');
                
                // Generate filename
                $filename = sprintf('signatures/user_%d_%s.%s', $userId, $type, $extension);
                
                // Store the file
                Storage::disk('public')->put($filename, $imageData);
                
                // Return the full URL
                return url('storage/' . $filename);
            }

            // If it's plain base64 without data URI prefix
            if (base64_encode(base64_decode($signatureData, true)) === $signatureData) {
                $imageData = base64_decode($signatureData);
                $filename = sprintf('signatures/user_%d_%s.png', $userId, $type);
                Storage::disk('public')->put($filename, $imageData);
                return url('storage/' . $filename);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error processing signature', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}