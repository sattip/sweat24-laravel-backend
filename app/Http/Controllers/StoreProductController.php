<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use Illuminate\Http\Request;

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::active()->ordered();
        
        if ($request->has('category') && $request->category !== 'all') {
            $query->category($request->category);
        }
        
        $products = $query->get();
        
        return response()->json($products);
    }

    public function show($slug)
    {
        $product = StoreProduct::where('slug', $slug)
                               ->where('is_active', true)
                               ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }
    
    public function showById($id)
    {
        $product = StoreProduct::where('id', $id)
                               ->where('is_active', true)
                               ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'image_url' => 'nullable|string',
            'category' => 'required|in:supplements,apparel,accessories,equipment',
            'stock_quantity' => 'nullable|integer|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer',
        ]);

        $product = StoreProduct::create($validated);
        
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $storeProduct = StoreProduct::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'image_url' => 'nullable|string',
            'category' => 'sometimes|in:supplements,apparel,accessories,equipment',
            'stock_quantity' => 'nullable|integer|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $storeProduct->update($validated);
        
        return response()->json($storeProduct);
    }

    public function destroy($id)
    {
        $storeProduct = StoreProduct::findOrFail($id);
        $storeProduct->delete();
        
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function adminIndex(Request $request)
    {
        $query = StoreProduct::ordered();
        
        if ($request->has('category') && $request->category !== 'all') {
            $query->category($request->category);
        }
        
        // Admin sees all products, including inactive
        $products = $query->get();
        
        return response()->json($products);
    }
}
