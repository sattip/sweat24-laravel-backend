<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function uploadProductImage(Request $request)
    {
        \Log::info('Upload request received', ['has_file' => $request->hasFile('image')]);
        
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120' // Max 5MB
        ]);

        try {
            $image = $request->file('image');
            
            // Generate unique filename
            $filename = Str::random(40) . '.' . $image->getClientOriginalExtension();
            
            // Store in public/products directory
            $path = $image->storeAs('public/products', $filename);
            
            // Get public URL - prepend with base URL for full path
            $url = url(Storage::url($path));
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά το ανέβασμα της εικόνας'
            ], 500);
        }
    }
}