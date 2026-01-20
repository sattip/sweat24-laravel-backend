<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Sort by registration date (created_at) in descending order by default
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy('created_at', $sortOrder);

        // Check if no_pagination is requested (for frontend dropdown lists, etc)
        if ($request->has('no_pagination') && $request->get('no_pagination') === 'true') {
            $users = $query->with('userPackages', 'activityLogs')
                ->addSelect([
                    'users.*',
                    'doctor_certificate_path',
                    'medical_history'
                ])
                ->get();

            // Add has_medical_history flag
            $users->each(function ($user) {
                $user->has_medical_history = !empty($user->medical_history);
            });

            return response()->json($users);
        }

        $users = $query->with('userPackages', 'activityLogs')
            ->select([
                'users.*',
                'doctor_certificate_path',
                'medical_history'
            ])
            ->paginate(20);

        // Add has_medical_history flag to paginated results
        $users->getCollection()->each(function ($user) {
            $user->has_medical_history = !empty($user->medical_history);
        });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:8',
            'membership_type' => 'nullable|string',
            'medical_history' => 'nullable|string',
            // Referral system fields
            'referral_phone' => 'nullable|string|max:20',
            'referrer_id' => 'nullable|integer|exists:users,id',
            'found_us_via' => 'nullable|string|max:50',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['join_date'] = now();
        
        // Initialize referral points
        $validated['referral_points'] = 0;

        $user = User::create($validated);
        
        // Award points to referrer if there is one
        if (!empty($validated['referrer_id'])) {
            $referrer = User::find($validated['referrer_id']);
            if ($referrer) {
                // Award 10 points to referrer (configurable)
                $referrer->increment('referral_points', 10);
                
                // Log referral activity
                ActivityLogger::log(
                    'referral_system',
                    'user referred a new member',
                    $referrer,
                    [
                        'referred_user_id' => $user->id,
                        'referred_user_name' => $user->name,
                        'points_awarded' => 10
                    ]
                );
            }
        }
        
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $userData = $user->load([
            'userPackages' => function ($query) {
                $query->with(['package:id,name,price,sessions,duration,service_id']);
            },
            'bookings',
            'activityLogs',
            'parentConsent'
        ])->toArray();

        // Ensure medical_history is decoded as JSON object, not string
        if (isset($userData['medical_history']) && is_string($userData['medical_history'])) {
            $userData['medical_history'] = json_decode($userData['medical_history'], true);
        }

        // Add original package price to each user package
        if (isset($userData['user_packages'])) {
            foreach ($userData['user_packages'] as &$userPackage) {
                if (isset($userPackage['package'])) {
                    $userPackage['original_package_price'] = $userPackage['package']['price'];
                    $userPackage['original_package_sessions'] = $userPackage['package']['sessions'];
                    $userPackage['original_package_duration'] = $userPackage['package']['duration'];
                }
            }
        }

        // Debug: Log if discontinuation_notes exists
        \Log::info('User show response', [
            'user_id' => $user->id,
            'has_discontinuation_notes' => array_key_exists('discontinuation_notes', $userData),
            'discontinuation_notes_value' => $userData['discontinuation_notes'] ?? 'NOT_SET'
        ]);

        return response()->json($userData);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'membership_type' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,expired',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'weight' => 'nullable|numeric|between:30,300',
            'height' => 'nullable|numeric|between:100,250',
            'medical_history' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'emergency_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'notes' => 'nullable|string',
            // Priority Booking fields
            'has_priority_booking' => 'nullable|boolean',
            'priority_booking_expires_at' => 'nullable|date',
        ]);

        // Handle password update if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        // Add profile update timestamp
        $validated['profile_last_updated'] = now();
        
        $user->update($validated);
        
        // Log the update activity
        ActivityLogger::log(
            'user_management',
            'updated user profile',
            $user,
            ['changes' => $validated]
        );
        
        return response()->json($user->load('userPackages', 'bookings'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Search for user by phone number (for referral system)
     * Public endpoint - no authentication required
     */
    public function searchByPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:10'
        ]);

        $phone = $request->phone;
        
        // Search for user by phone
        $user = User::where('phone', $phone)->first();
        
        if ($user) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]
            ]);
        }
        
        return response()->json([
            'user' => null
        ]);
    }

    /**
     * Get all users with phone-based referral data (Admin & Trainer)
     */
    public function getPhoneBasedReferrals(Request $request)
    {
        try {
            \Log::info('getPhoneBasedReferrals called by user', [
                'user_id' => $request->user()->id,
                'role' => $request->user()->role
            ]);

            $query = User::query()
                ->where(function($q) {
                    $q->whereNotNull('referral_phone')
                      ->orWhereNotNull('referrer_id');
                })
                ->with(['packages:id,user_id,package_id', 'activityLogs' => function($q) {
                    $q->where('activity_type', 'referral_system')->latest()->limit(5);
                }]);

            // Add search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('referral_phone', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(20);

            \Log::info('getPhoneBasedReferrals found users', ['count' => $users->count()]);

            // Add referrer information
            foreach ($users as $user) {
                if ($user->referrer_id) {
                    $user->referrer = User::select('id', 'name', 'email', 'phone')
                        ->find($user->referrer_id);
                }
            }

            return response()->json($users);
        } catch (\Exception $e) {
            \Log::error('getPhoneBasedReferrals error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get detailed referral information for a specific user (Admin only)
     */
    public function getUserReferralInfo(Request $request, $id)
    {
        $user = User::with([
            'packages:id,user_id,package_id,status,created_at',
            'activityLogs' => function($q) {
                $q->where('activity_type', 'referral_system')->latest();
            }
        ])->findOrFail($id);

        $referralInfo = [
            'user' => $user,
            'referrer' => null,
            'referred_users' => [],
            'total_referrals' => 0,
            'total_points_earned' => $user->referral_points ?? 0,
            'registration_details' => [
                'used_referral_phone' => $user->referral_phone,
                'found_us_via' => $user->found_us_via,
                'registration_date' => $user->created_at,
            ]
        ];

        // Get referrer information if this user was referred
        if ($user->referrer_id) {
            $referralInfo['referrer'] = User::select('id', 'name', 'email', 'phone', 'referral_points')
                ->find($user->referrer_id);
        }

        // Get users this person has referred
        $referredUsers = User::where('referrer_id', $user->id)
            ->select('id', 'name', 'email', 'phone', 'created_at', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        $referralInfo['referred_users'] = $referredUsers;
        $referralInfo['total_referrals'] = $referredUsers->count();

        return response()->json($referralInfo);
    }

    /**
     * Update trainer notes for a user
     */
    public function updateTrainerNotes(Request $request, $userId)
    {
        $validated = $request->validate([
            'trainer_notes' => 'nullable|string|max:5000',
        ]);

        $user = User::findOrFail($userId);
        $user->trainer_notes = $validated['trainer_notes'];
        $user->save();

        \Log::info('Trainer notes updated', [
            'user_id' => $userId,
            'updated_by' => $request->user()->id ?? 'unknown',
            'notes_length' => strlen($validated['trainer_notes'] ?? ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Οι σημειώσεις ενημερώθηκαν επιτυχώς.',
        ]);
    }

    /**
     * Update discontinuation notes for a user
     */
    public function updateDiscontinuationNotes(Request $request, $userId)
    {
        $validated = $request->validate([
            'discontinuation_notes' => 'nullable|string|max:5000',
        ]);

        $user = User::findOrFail($userId);
        $user->discontinuation_notes = $validated['discontinuation_notes'];
        $user->save();

        \Log::info('Discontinuation notes updated', [
            'user_id' => $userId,
            'updated_by' => $request->user()->id ?? 'unknown',
            'notes_length' => strlen($validated['discontinuation_notes'] ?? ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Οι σημειώσεις διακοπής ενημερώθηκαν επιτυχώς.',
        ]);
    }
}