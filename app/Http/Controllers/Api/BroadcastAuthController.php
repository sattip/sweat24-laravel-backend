<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        // The user should already be authenticated via Sanctum middleware
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        if (!$channelName || !$socketId) {
            return response()->json(['error' => 'Missing channel_name or socket_id'], 400);
        }

        // Manually authorize the channel since Broadcast::auth() has issues with Sanctum
        $isAuthorized = $this->authorizeChannel($user, $channelName);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return the auth response that broadcasting expects
        return response()->json([
            'auth' => $this->generateAuthResponse($channelName, $socketId)
        ]);
    }

    private function authorizeChannel($user, $channelName)
    {
        // Parse channel name to extract parameters
        if (preg_match('/^private-chat\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        // Order channels
        if (preg_match('/^private-order\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        // User channels
        if (preg_match('/^private-user\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        // Booking request channels for users
        if (preg_match('/^private-booking-request\.user\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        // Add other channel types as needed
        if (preg_match('/^chat\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        if (preg_match('/^user\.(\d+)$/', $channelName, $matches)) {
            $userId = $matches[1];
            return (int) $user->id === (int) $userId;
        }

        return false;
    }

    private function generateAuthResponse($channelName, $socketId)
    {
        // For Pusher or similar services, you'd generate a signature here
        // For now, return a basic auth response
        return hash_hmac('sha256', $channelName . ':' . $socketId, config('app.key'));
    }
}