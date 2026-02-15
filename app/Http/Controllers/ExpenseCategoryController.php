<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Traits\SearchableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    use SearchableTrait;
    /**
     * Display a listing of expense categories.
     */
    public function index(Request $request)
    {
        $query = ExpenseCategory::with('parent', 'subcategories');

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['main', 'subcategory'])) {
            $query->where('category_type', $request->type);
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Search by name
        if ($request->has('search')) {
            $this->addSafeLikeWhere($query, 'name', $request->search);
        }

        $categories = $query->orderBy('sort_order')
                           ->orderBy('name')
                           ->paginate($request->get('per_page', 15));

        return response()->json($categories);
    }

    /**
     * Store a newly created expense category.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_type' => 'required|in:main,subcategory',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If it's a subcategory, ensure parent_id is provided and parent exists
        if ($request->category_type === 'subcategory') {
            if (!$request->parent_id) {
                return response()->json([
                    'message' => 'Parent category is required for subcategories'
                ], 422);
            }

            $parent = ExpenseCategory::find($request->parent_id);
            if (!$parent || $parent->category_type !== 'main') {
                return response()->json([
                    'message' => 'Invalid parent category'
                ], 422);
            }
        }

        $category = ExpenseCategory::create($request->all());

        return response()->json($category->load('parent', 'subcategories'), 201);
    }

    /**
     * Display the specified expense category.
     */
    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory->load('parent', 'subcategories'));
    }

    /**
     * Update the specified expense category.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_type' => 'sometimes|required|in:main,subcategory',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If changing to subcategory, ensure parent_id is provided
        if ($request->has('category_type') && $request->category_type === 'subcategory') {
            if (!$request->parent_id && !$expenseCategory->parent_id) {
                return response()->json([
                    'message' => 'Parent category is required for subcategories'
                ], 422);
            }

            $parentId = $request->parent_id ?? $expenseCategory->parent_id;
            $parent = ExpenseCategory::find($parentId);
            if (!$parent || $parent->category_type !== 'main') {
                return response()->json([
                    'message' => 'Invalid parent category'
                ], 422);
            }
        }

        $expenseCategory->update($request->all());

        return response()->json($expenseCategory->load('parent', 'subcategories'));
    }

    /**
     * Remove the specified expense category.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Check if category has subcategories
        if ($expenseCategory->subcategories()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Delete subcategories first.'
            ], 422);
        }

        // Check if category is used in business expenses
        if ($expenseCategory->businessExpenses()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category that is used in business expenses.'
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json(['message' => 'Expense category deleted successfully']);
    }

    /**
     * Get all main categories with their subcategories.
     */
    public function getCategoriesTree(Request $request)
    {
        $mainCategories = ExpenseCategory::mainCategories()
            ->active()
            ->with(['subcategories' => function($query) {
                $query->active()->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => $mainCategories
        ]);
    }

    /**
     * Toggle active status of a category.
     */
    public function toggleStatus(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update(['is_active' => !$expenseCategory->is_active]);

        return response()->json([
            'message' => 'Category status updated successfully',
            'category' => $expenseCategory->load('parent', 'subcategories')
        ]);
    }

    /**
     * Get categories for dropdown/select options.
     */
    public function getOptions(Request $request)
    {
        $query = ExpenseCategory::active();

        if ($request->has('type')) {
            $query->where('category_type', $request->type);
        }

        $categories = $query->orderBy('sort_order')
                           ->orderBy('name')
                           ->get(['id', 'name', 'category_type', 'parent_id']);

        return response()->json([
            'options' => $categories->map(function($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->full_name,
                    'type' => $category->category_type,
                ];
            })
        ]);
    }
}






