<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgressPhoto;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProgressPhotoController extends Controller
{
    /**
     * Get all progress photos for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $photos = ProgressPhoto::where('user_id', $user->id)
            ->orderBy('uploaded_at', 'desc')
            ->get()
            ->map(function ($photo) {
                return $photo->toApiArray();
            });

        return response()->json($photos);
    }

    /**
     * Upload progress photos
     */
    public function store(Request $request)
    {
        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
            'caption' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $uploadedPhotos = [];
        $caption = $request->input('caption');
        $uploadTime = now();

        try {
            foreach ($request->file('photos') as $photo) {
                // Generate unique filename
                $filename = 'user_' . $user->id . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                
                // Store the file
                $path = $photo->storeAs('progress', $filename, 'public');
                
                // Create database record
                $progressPhoto = ProgressPhoto::create([
                    'user_id' => $user->id,
                    'image_path' => $path,
                    'caption' => $caption,
                    'uploaded_at' => $uploadTime,
                ]);
                
                $uploadedPhotos[] = $progressPhoto->toApiArray();
            }

            // Log activity
            ActivityLogger::log(
                'progress_photo',
                'uploaded progress photos',
                $user,
                ['count' => count($uploadedPhotos)]
            );

            return response()->json([
                'message' => 'Οι φωτογραφίες ανέβηκαν επιτυχώς',
                'photos' => $uploadedPhotos,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Progress photo upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up any uploaded files if something went wrong
            foreach ($uploadedPhotos as $photo) {
                Storage::disk('public')->delete($photo['image_path']);
            }

            return response()->json([
                'message' => 'Αποτυχία μεταφόρτωσης φωτογραφιών',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a progress photo
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $photo = ProgressPhoto::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$photo) {
            return response()->json([
                'message' => 'Η φωτογραφία δεν βρέθηκε',
            ], 404);
        }

        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($photo->image_path)) {
                Storage::disk('public')->delete($photo->image_path);
            }

            // Delete database record
            $photo->delete();

            // Log activity
            ActivityLogger::log(
                'progress_photo',
                'deleted progress photo',
                $user,
                ['photo_id' => $id]
            );

            return response()->json([
                'message' => 'Η φωτογραφία διαγράφηκε επιτυχώς',
            ]);

        } catch (\Exception $e) {
            Log::error('Progress photo deletion failed', [
                'user_id' => $user->id,
                'photo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Αποτυχία διαγραφής φωτογραφίας',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get progress photos for a specific user
     */
    public function getUserPhotos(Request $request, $userId)
    {
        // Check if requester is admin
        if ($request->user()->membership_type !== 'Admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $photos = ProgressPhoto::where('user_id', $userId)
            ->orderBy('uploaded_at', 'desc')
            ->get()
            ->map(function ($photo) {
                return $photo->toApiArray();
            });

        return response()->json($photos);
    }

    /**
     * Admin: Delete any progress photo
     */
    public function adminDestroy(Request $request, $id)
    {
        // Check if requester is admin
        if ($request->user()->membership_type !== 'Admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $photo = ProgressPhoto::find($id);

        if (!$photo) {
            return response()->json([
                'message' => 'Η φωτογραφία δεν βρέθηκε',
            ], 404);
        }

        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($photo->image_path)) {
                Storage::disk('public')->delete($photo->image_path);
            }

            $userId = $photo->user_id;

            // Delete database record
            $photo->delete();

            // Log activity
            ActivityLogger::log(
                'progress_photo',
                'admin deleted progress photo',
                $request->user(),
                ['photo_id' => $id, 'user_id' => $userId]
            );

            return response()->json([
                'message' => 'Η φωτογραφία διαγράφηκε επιτυχώς',
            ]);

        } catch (\Exception $e) {
            Log::error('Admin progress photo deletion failed', [
                'admin_id' => $request->user()->id,
                'photo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Αποτυχία διαγραφής φωτογραφίας',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}