<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriorityBookingSetting;
use Illuminate\Http\Request;

class PriorityBookingSettingsController extends Controller
{
    /**
     * Get priority booking settings
     */
    public function index()
    {
        $settings = PriorityBookingSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update priority booking settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'priority_booking_window_days' => 'required|integer|min:1|max:365',
            'regular_booking_window_days' => 'required|integer|min:1|max:365',
            'priority_seats_release_hours' => 'required|integer|min:0|max:168',
            'default_priority_seats' => 'nullable|integer|min:0|max:50',
            'priority_system_enabled' => 'nullable|boolean',
            'auto_release_enabled' => 'nullable|boolean',
        ]);

        $settings = PriorityBookingSetting::getSettings();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Οι ρυθμίσεις Priority Booking ενημερώθηκαν επιτυχώς.',
            'data' => $settings->fresh(),
        ]);
    }
}
