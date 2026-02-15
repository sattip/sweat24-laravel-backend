<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewMemberInfo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class NewMemberInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = NewMemberInfo::query();

        // Filter by category if provided
        if ($request->has('category')) {
            $query->category($request->get('category'));
        }

        // Filter by active status if provided
        if ($request->has('active')) {
            $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
            if ($active) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Get ordered results
        $items = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => ['required', Rule::in(array_keys(NewMemberInfo::CATEGORIES))],
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        $item = NewMemberInfo::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'New member information created successfully',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $item = NewMemberInfo::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $item = NewMemberInfo::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category' => ['sometimes', 'required', Rule::in(array_keys(NewMemberInfo::CATEGORIES))],
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0'
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Information updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $item = NewMemberInfo::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Information deleted successfully'
        ]);
    }

    /**
     * Get information by category
     *
     * @param string $category
     * @return JsonResponse
     */
    public function getByCategory($category): JsonResponse
    {
        // Validate category
        if (!in_array($category, array_keys(NewMemberInfo::CATEGORIES))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category',
                'valid_categories' => array_keys(NewMemberInfo::CATEGORIES)
            ], 400);
        }

        $items = NewMemberInfo::category($category)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'category' => $category,
            'category_label' => NewMemberInfo::CATEGORIES[$category],
            'data' => $items
        ]);
    }
}