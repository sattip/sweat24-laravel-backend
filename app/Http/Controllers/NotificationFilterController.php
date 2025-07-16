<?php

namespace App\Http\Controllers;

use App\Models\NotificationFilter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationFilterController extends Controller
{
    /**
     * Display a listing of notification filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationFilter::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $filters = $query->orderBy('name')->get();

        // Add recipient count to each filter
        $filters->each(function ($filter) {
            $filter->recipient_count = $filter->getMatchingUsersCount();
        });

        return response()->json($filters);
    }

    /**
     * Store a newly created filter.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:notification_filters',
            'description' => 'nullable|string',
            'criteria' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $filter = NotificationFilter::create($validated);
        $filter->recipient_count = $filter->getMatchingUsersCount();

        return response()->json([
            'message' => 'Filter created successfully',
            'filter' => $filter,
        ], 201);
    }

    /**
     * Display the specified filter.
     */
    public function show(NotificationFilter $filter): JsonResponse
    {
        $filter->recipient_count = $filter->getMatchingUsersCount();
        $filter->sample_recipients = $filter->getMatchingUsers()->take(10)->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'membership_type' => $user->membership_type,
            ];
        });

        return response()->json($filter);
    }

    /**
     * Update the specified filter.
     */
    public function update(Request $request, NotificationFilter $filter): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255|unique:notification_filters,name,' . $filter->id,
            'description' => 'nullable|string',
            'criteria' => 'array',
            'is_active' => 'boolean',
        ]);

        $filter->update($validated);
        $filter->recipient_count = $filter->getMatchingUsersCount();

        return response()->json([
            'message' => 'Filter updated successfully',
            'filter' => $filter,
        ]);
    }

    /**
     * Remove the specified filter.
     */
    public function destroy(NotificationFilter $filter): JsonResponse
    {
        $filter->delete();

        return response()->json([
            'message' => 'Filter deleted successfully',
        ]);
    }

    /**
     * Preview recipients for a filter.
     */
    public function previewRecipients(NotificationFilter $filter): JsonResponse
    {
        $recipients = $filter->getMatchingUsers();

        return response()->json([
            'count' => $recipients->count(),
            'recipients' => $recipients->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'membership_type' => $user->membership_type,
                    'status' => $user->status,
                    'last_visit' => $user->last_visit,
                ];
            }),
        ]);
    }

    /**
     * Get available filter criteria options.
     */
    public function criteriaOptions(): JsonResponse
    {
        return response()->json([
            'package_types' => [
                'personal_training',
                'group_fitness',
                'yoga_pilates',
                'nutrition_coaching',
                'online_training',
            ],
            'membership_status' => [
                'active',
                'expired',
                'trial',
            ],
            'class_attendance' => [
                'periods' => [
                    ['value' => 7, 'label' => 'Last 7 days'],
                    ['value' => 30, 'label' => 'Last 30 days'],
                    ['value' => 90, 'label' => 'Last 90 days'],
                ],
            ],
        ]);
    }
}
