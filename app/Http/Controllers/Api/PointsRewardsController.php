<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointsReward;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PointsRewardsController extends Controller
{
    public function index(): JsonResponse
    {
        $rewards = PointsReward::orderBy('sort_order')->orderBy('points_cost')->get();
        return response()->json(['success' => true, 'data' => $rewards]);
    }

    public function show($id): JsonResponse
    {
        $pointsReward = PointsReward::findOrFail($id);
        return response()->json(['success' => true, 'data' => $pointsReward]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_cost' => 'required|integer|min:1',
            'reward_type' => ['required', Rule::in(['gift_card', 'free_session', 'product', 'discount', 'premium', 'merchandise'])],
            'reward_value' => 'required|string|max:100',
            'image_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'max_redemptions' => 'nullable|integer|min:0',
            'sort_order' => 'integer',
            'terms_conditions' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);

        $reward = PointsReward::create($validated);
        return response()->json(['success' => true, 'message' => 'Reward created successfully', 'data' => $reward], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $pointsReward = PointsReward::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'points_cost' => 'sometimes|required|integer|min:1',
            'reward_type' => ['sometimes', 'required', Rule::in(['gift_card', 'free_session', 'product', 'discount', 'premium', 'merchandise'])],
            'reward_value' => 'sometimes|required|string|max:100',
            'image_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'max_redemptions' => 'nullable|integer|min:0',
            'sort_order' => 'integer',
            'terms_conditions' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);

        $pointsReward->update($validated);
        $pointsReward->refresh();
        return response()->json(['success' => true, 'message' => 'Reward updated successfully', 'data' => $pointsReward]);
    }

    public function destroy($id): JsonResponse
    {
        $pointsReward = PointsReward::findOrFail($id);
        $pointsReward->delete();
        return response()->json(['success' => true, 'message' => 'Reward deleted successfully']);
    }
}
