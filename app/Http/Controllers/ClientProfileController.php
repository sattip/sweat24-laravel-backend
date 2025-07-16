<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ClientProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'packages' => function($query) {
                $query->where('status', 'active')->latest();
            },
            'bookings' => function($query) {
                $query->with(['gymClass.instructor'])
                      ->whereDate('scheduled_at', '>=', now())
                      ->orderBy('scheduled_at', 'asc');
            }
        ]);

        return response()->json([
            'user' => $user,
            'statistics' => [
                'total_bookings' => $user->bookings()->count(),
                'upcoming_bookings' => $user->bookings()->whereDate('scheduled_at', '>=', now())->count(),
                'completed_sessions' => $user->bookings()->where('status', 'completed')->count(),
                'active_packages' => $user->packages()->where('status', 'active')->count(),
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date|before:today',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'medical_history' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
        ]);

        $user->update($validated);

        // Log the update activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['changes' => $validated])
            ->log('updated own profile');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        // Log the password change
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('changed password');

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Delete old avatar if exists
        if ($user->avatar && Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);

        // Log the avatar update
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('updated profile picture');

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => Storage::url($path)
        ]);
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(Request $request)
    {
        $user = $request->user();

        // Default preferences
        $defaultPreferences = [
            'email_notifications' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
            'booking_reminders' => true,
            'package_expiry_alerts' => true,
            'promotional_emails' => false,
        ];

        $preferences = $user->notification_preferences ?? $defaultPreferences;

        return response()->json($preferences);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'booking_reminders' => 'boolean',
            'package_expiry_alerts' => 'boolean',
            'promotional_emails' => 'boolean',
        ]);

        // Store preferences in user model
        $user->notification_preferences = $validated;
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'preferences' => $validated
        ]);
    }

    /**
     * Get user's booking history
     */
    public function bookingHistory(Request $request)
    {
        $user = $request->user();

        $bookings = $user->bookings()
            ->with(['gymClass.instructor'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Update booking notes
     */
    public function updateBookingNotes(Request $request, $bookingId)
    {
        $user = $request->user();
        
        $booking = $user->bookings()->findOrFail($bookingId);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking notes updated successfully',
            'booking' => $booking
        ]);
    }

    /**
     * Request account deactivation
     */
    public function requestDeactivation(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'feedback' => 'nullable|string|max:1000'
        ]);

        // Log deactivation request
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'reason' => $validated['reason'],
                'feedback' => $validated['feedback'] ?? null
            ])
            ->log('requested account deactivation');

        // Create notification for admin
        \App\Models\Notification::create([
            'title' => 'Account Deactivation Request',
            'message' => "User {$user->name} has requested account deactivation. Reason: {$validated['reason']}",
            'type' => 'account_request',
            'target_type' => 'user',
            'target_id' => $user->id,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Account deactivation request submitted successfully. An administrator will contact you soon.'
        ]);
    }

    /**
     * Get privacy settings
     */
    public function getPrivacySettings(Request $request)
    {
        $user = $request->user();

        // Default privacy settings
        $defaultSettings = [
            'show_profile_to_trainers' => true,
            'show_attendance_history' => true,
            'allow_photo_in_gym' => true,
            'share_progress_reports' => true,
        ];

        $settings = $user->privacy_settings ?? [];
        $settings = array_merge($defaultSettings, $settings);

        return response()->json($settings);
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacySettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'show_profile_to_trainers' => 'boolean',
            'show_attendance_history' => 'boolean',
            'allow_photo_in_gym' => 'boolean',
            'share_progress_reports' => 'boolean',
        ]);

        $user->privacy_settings = $validated;
        $user->save();

        return response()->json([
            'message' => 'Privacy settings updated successfully',
            'settings' => $validated
        ]);
    }
}