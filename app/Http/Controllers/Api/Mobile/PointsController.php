<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\PointsService;
use App\Models\UserPoints;
use App\Models\PointsTransaction;
use App\Models\PointsReward;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PointsController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get user points balance
     */
    public function getUserPoints(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        
        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $userPoints = UserPoints::where('user_id', $userId)->first();
        
        if (!$userPoints) {
            // Create initial record
            $userPoints = UserPoints::create([
                'user_id' => $userId,
                'points_balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userPoints->user_id,
                'points_balance' => $userPoints->points_balance
            ]
        ]);
    }

    /**
     * Get points history
     */
    public function getPointsHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'nullable|in:earned,spent',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->query('user_id');
        $type = $request->query('type');
        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $query = PointsTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $total = $query->count();
        $transactions = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'total' => $total,
                'current_page' => floor($offset / $limit) + 1,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Get affordable rewards
     */
    public function getAffordableRewards(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->query('user_id');

        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        // Get user points balance
        $userPoints = \App\Models\UserPoints::where('user_id', $userId)->first();
        $pointsBalance = $userPoints ? $userPoints->points_balance : 0;

        $rewards = PointsReward::active()
            ->affordable($pointsBalance)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_redemptions')
                      ->orWhereColumn('current_redemptions', '<', 'max_redemptions');
            })
            ->orderBy('sort_order')
            ->orderBy('points_cost')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rewards
        ]);
    }

    /**
     * Get all rewards
     */
    public function getAllRewards(): JsonResponse
    {
        $rewards = PointsReward::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderBy('sort_order')
            ->orderBy('points_cost')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rewards
        ]);
    }

    /**
     * Redeem a reward with points
     */
    public function redeemReward(Request $request, $rewardId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['reward_id' => $rewardId]), [
            'user_id' => 'required|integer|exists:users,id',
            'reward_id' => 'required|integer|exists:points_rewards,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');

        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        try {
            $redemption = $this->pointsService->redeemReward($userId, $rewardId);

            return response()->json([
                'success' => true,
                'message' => 'Η ανταμοιβή εξαργυρώθηκε επιτυχώς!',
                'data' => [
                    'redemption_id' => $redemption->id,
                    'reward_code' => $redemption->reward_code,
                    'points_spent' => $redemption->points_spent,
                    'reward_name' => $redemption->reward->name,
                    'instructions' => $redemption->instructions,
                    'expires_at' => $redemption->expires_at,
                    'status' => $redemption->status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user redemptions history
     */
    public function getUserRedemptions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'nullable|in:pending,active,used,expired,cancelled',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->query('user_id');
        $status = $request->query('status');
        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $query = \App\Models\RewardRedemption::with('reward')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $redemptions = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $redemptions->map(function($redemption) {
                return [
                    'id' => $redemption->id,
                    'reward_code' => $redemption->reward_code,
                    'reward_name' => $redemption->reward->name,
                    'points_spent' => $redemption->points_spent,
                    'status' => $redemption->status,
                    'instructions' => $redemption->instructions,
                    'expires_at' => $redemption->expires_at,
                    'used_at' => $redemption->used_at,
                    'created_at' => $redemption->created_at,
                    'reward' => [
                        'name' => $redemption->reward->name,
                        'description' => $redemption->reward->description,
                        'reward_type' => $redemption->reward->reward_type,
                        'reward_value' => $redemption->reward->reward_value
                    ]
                ];
            }),
            'pagination' => [
                'total' => $total,
                'current_page' => floor($offset / $limit) + 1,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Get points stats for user
     */
    public function getPointsStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->query('user_id');

        // Check if user is authenticated
        $user = $request->user();
        if ($user) {
            // Validate user access for authenticated requests
            if ($user->id != $userId && !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $userPoints = \App\Models\UserPoints::where('user_id', $userId)->first();
        $totalRedemptions = \App\Models\RewardRedemption::where('user_id', $userId)->count();
        $activeRedemptions = \App\Models\RewardRedemption::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'current_balance' => $userPoints ? $userPoints->points_balance : 0,
                'total_earned' => $userPoints ? $userPoints->total_earned : 0,
                'total_spent' => $userPoints ? $userPoints->total_spent : 0,
                'lifetime_rank' => $userPoints ? $userPoints->lifetime_rank : 'Bronze',
                'total_redemptions' => $totalRedemptions,
                'active_redemptions' => $activeRedemptions,
                'available_rewards_count' => \App\Models\PointsReward::active()
                    ->affordable($userPoints ? $userPoints->points_balance : 0)
                    ->count()
            ]
        ]);
    }
}
