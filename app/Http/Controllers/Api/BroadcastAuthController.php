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

        try {
            // Use Laravel's broadcasting system to authorize the channel
            $broadcastAuth = Broadcast::auth($request);
            return $broadcastAuth;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authorization failed'], 403);
        }
    }
}