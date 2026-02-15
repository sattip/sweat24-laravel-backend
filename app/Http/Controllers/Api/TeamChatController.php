<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TeamChatController extends Controller
{
    public function getMessages(Request $request): JsonResponse
    {
        // Check if user has permission (admin or trainer only)
        if (!$this->canAccessTeamChat($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update user's last seen
        $this->updateLastSeen($request->user());

        // Get recent messages (last 100)
        $messages = TeamChatMessage::orderBy('created_at', 'desc')
                                   ->limit(100)
                                   ->get()
                                   ->reverse()
                                   ->values();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        if (!$this->canAccessTeamChat($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $request->validate([
                'message' => 'required|string|max:1000'
            ]);

            $user = $request->user();
            
            $message = TeamChatMessage::create([
                'message' => trim($request->message),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role
            ]);

            // Update user's last seen
            $this->updateLastSeen($user);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function getOnlineUsers(Request $request): JsonResponse
    {
        if (!$this->canAccessTeamChat($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update current user's last seen
        $this->updateLastSeen($request->user());

        // Get admin and trainer users with last seen
        // Consider users online if they were active in the last 5 minutes
        $users = User::whereIn('role', ['admin', 'trainer'])
                     ->where('status', 'active')
                     ->select('id', 'name', 'role', 'last_seen')
                     ->orderBy('last_seen', 'desc')
                     ->get()
                     ->map(function ($user) {
                         $user->is_online = $user->last_seen && $user->last_seen->diffInMinutes(now()) <= 5;
                         return $user;
                     });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function deleteMessage(Request $request, TeamChatMessage $message): JsonResponse
    {
        if (!$this->canAccessTeamChat($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow user to delete their own message or admin can delete any
        if ($message->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Cannot delete message'], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        if (!$this->canAccessTeamChat($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_messages' => TeamChatMessage::count(),
            'messages_today' => TeamChatMessage::whereDate('created_at', today())->count(),
            'active_users' => User::whereIn('role', ['admin', 'trainer'])
                                  ->where('status', 'active')
                                  ->where('last_seen', '>=', now()->subMinutes(5))
                                  ->count(),
            'recent_activity' => TeamChatMessage::where('created_at', '>=', now()->subHours(24))
                                                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    private function canAccessTeamChat(User $user): bool
    {
        return $user &&
               ($user->role === 'admin' || $user->role === 'trainer') &&
               $user->status === 'active';
    }

    private function updateLastSeen(User $user): void
    {
        $user->update(['last_seen' => now()]);
    }
}