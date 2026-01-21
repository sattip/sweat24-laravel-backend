<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClassTypesController extends Controller
{
    /**
     * Display a listing of class types.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassType::query();

        // Filter by active status if requested
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        // Filter by user package if user_id is provided
        if ($request->has('user_id') && $request->user_id) {
            $userId = $request->user_id;

            // Get user's active packages with their services
            $userPackages = \App\Models\UserPackage::where('user_id', $userId)
                ->where('status', 'active')
                ->where('is_frozen', false)
                ->where('remaining_sessions', '>', 0)
                ->where(function($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now()->toDateString());
                })
                ->with('package.services')
                ->get();

            if ($userPackages->isNotEmpty()) {
                // Collect all service IDs from user's packages
                $serviceIds = [];
                foreach ($userPackages as $userPackage) {
                    if ($userPackage->package && $userPackage->package->services) {
                        $serviceIds = array_merge(
                            $serviceIds,
                            $userPackage->package->services->pluck('id')->toArray()
                        );
                    }
                }
                $serviceIds = array_unique($serviceIds);

                // Get service names to determine allowed class types
                $services = \App\Models\Service::whereIn('id', $serviceIds)->get();
                $allowedClassTypeValues = $this->mapServicesToClassTypes($services);

                // Filter class types by allowed values
                if (!empty($allowedClassTypeValues)) {
                    $query->whereIn('value', $allowedClassTypeValues);
                } else {
                    // No allowed class types, return empty
                    return response()->json([
                        'success' => true,
                        'data' => []
                    ]);
                }
            } else {
                // User has no active packages, return empty
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
        }

        // Order by sort_order
        $classTypes = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $classTypes
        ]);
    }

    /**
     * Map services to allowed class types
     */
    private function mapServicesToClassTypes($services): array
    {
        $allowedClassTypes = [];

        foreach ($services as $service) {
            $serviceName = strtolower($service->name);

            // Map service names to class type values
            if (strpos($serviceName, 'semi personal') !== false) {
                $allowedClassTypes[] = 'semi-personal';
                // Semi personal might also allow some group classes
                $allowedClassTypes[] = 'group';
            } elseif (strpos($serviceName, 'personal training') !== false && strpos($serviceName, 'pilates') === false) {
                $allowedClassTypes[] = 'personal';
            } elseif (strpos($serviceName, 'pilates personal') !== false) {
                $allowedClassTypes[] = 'personal-pilates';
            } elseif (strpos($serviceName, 'pilates group') !== false) {
                $allowedClassTypes[] = 'pilates';
                $allowedClassTypes[] = 'group';
            } elseif (strpos($serviceName, 'ems') !== false) {
                $allowedClassTypes[] = 'ems';
            } elseif (strpos($serviceName, 'cardio personal') !== false) {
                $allowedClassTypes[] = 'cardio-personal';
            }
        }

        return array_unique($allowedClassTypes);
    }

    /**
     * Store a newly created class type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255|unique:class_types,value',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'locations' => 'nullable|string',
        ]);

        // Parse locations JSON
        if (isset($validated['locations'])) {
            $validated['locations'] = json_decode($validated['locations'], true) ?? [];
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'class-type-' . time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('class-types', $filename, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $classType = ClassType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Class type created successfully',
            'data' => $classType
        ], 201);
    }

    /**
     * Display the specified class type.
     */
    public function show(string $id): JsonResponse
    {
        $classType = ClassType::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $classType
        ]);
    }

    /**
     * Update the specified class type.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $classType = ClassType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'value' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('class_types', 'value')->ignore($classType->id)
            ],
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'locations' => 'nullable|string',
        ]);

        // Parse locations JSON
        if (isset($validated['locations'])) {
            $validated['locations'] = json_decode($validated['locations'], true) ?? [];
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($classType->image) {
                $oldPath = str_replace('/storage/', '', $classType->image);
                Storage::disk('public')->delete($oldPath);
            }

            $image = $request->file('image');
            $filename = 'class-type-' . time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('class-types', $filename, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image) {
            if ($classType->image) {
                $oldPath = str_replace('/storage/', '', $classType->image);
                Storage::disk('public')->delete($oldPath);
            }
            $validated['image'] = null;
        }

        $classType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Class type updated successfully',
            'data' => $classType
        ]);
    }

    /**
     * Remove the specified class type.
     */
    public function destroy(string $id): JsonResponse
    {
        $classType = ClassType::findOrFail($id);
        $classType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class type deleted successfully'
        ]);
    }

    /**
     * Reorder class types.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_types' => 'required|array',
            'class_types.*.id' => 'required|exists:class_types,id',
            'class_types.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['class_types'] as $item) {
            ClassType::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class types reordered successfully'
        ]);
    }
}
