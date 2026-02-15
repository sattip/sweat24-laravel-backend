<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeManual;
use Illuminate\Http\Request;

class EmployeeManualController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EmployeeManual::query();

        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status (only show active for non-admins)
        if (!$request->user() || $request->user()->role !== 'admin') {
            $query->where('is_active', true);
        }

        $items = $query->orderBy('order')->orderBy('created_at')->get();

        // Add category label to each item
        $items->each(function ($item) {
            $item->category_label = $item->category_label;
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get items by category
     */
    public function getByCategory($category)
    {
        $query = EmployeeManual::where('category', $category);

        // Only show active items for non-admins
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            $query->where('is_active', true);
        }

        $items = $query->orderBy('order')->orderBy('created_at')->get();

        $items->each(function ($item) {
            $item->category_label = $item->category_label;
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:' . implode(',', array_keys(EmployeeManual::CATEGORIES)),
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $item = EmployeeManual::create($validated);
        $item->category_label = $item->category_label;

        return response()->json([
            'success' => true,
            'message' => 'Το στοιχείο δημιουργήθηκε επιτυχώς',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = EmployeeManual::findOrFail($id);
        $item->category_label = $item->category_label;

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = EmployeeManual::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category' => 'sometimes|required|in:' . implode(',', array_keys(EmployeeManual::CATEGORIES)),
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $item->update($validated);
        $item->category_label = $item->category_label;

        return response()->json([
            'success' => true,
            'message' => 'Το στοιχείο ενημερώθηκε επιτυχώς',
            'data' => $item,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = EmployeeManual::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το στοιχείο διαγράφηκε επιτυχώς',
        ]);
    }
}
