<?php

namespace App\Http\Controllers;

use App\Models\GymClass;
use App\Models\ClassWaitlist;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitlistController extends Controller
{
    /**
     * Join the waitlist for a class
     */
    public function join(Request $request, GymClass $class)
    {
        $user = auth()->user();
        
        // Check if user already has a booking
        $existingBooking = Booking::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->first();
            
        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Έχετε ήδη κράτηση για αυτό το μάθημα'
            ], 400);
        }
        
        // Check if user is already in waitlist
        $existingWaitlist = ClassWaitlist::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->first();
            
        if ($existingWaitlist) {
            return response()->json([
                'success' => false,
                'message' => 'Είστε ήδη στη λίστα αναμονής',
                'position' => $existingWaitlist->position
            ], 400);
        }
        
        // Check if class is actually full
        if (!$class->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Το μάθημα έχει διαθέσιμες θέσεις. Παρακαλώ κάντε κανονική κράτηση.'
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            // Get next position in waitlist
            $lastPosition = ClassWaitlist::where('class_id', $class->id)
                ->max('position') ?? 0;
            
            $waitlistEntry = ClassWaitlist::create([
                'class_id' => $class->id,
                'user_id' => $user->id,
                'position' => $lastPosition + 1,
                'status' => 'waiting'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Προστεθήκατε επιτυχώς στη λίστα αναμονής',
                'position' => $waitlistEntry->position,
                'waitlist_id' => $waitlistEntry->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την προσθήκη στη λίστα αναμονής'
            ], 500);
        }
    }
    
    /**
     * Leave the waitlist
     */
    public function leave(Request $request, GymClass $class)
    {
        $user = auth()->user();
        
        $waitlistEntry = ClassWaitlist::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->first();
            
        if (!$waitlistEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν βρέθηκε εγγραφή στη λίστα αναμονής'
            ], 404);
        }
        
        DB::beginTransaction();
        try {
            $position = $waitlistEntry->position;
            $waitlistEntry->delete();
            
            // Update positions for users after this one
            ClassWaitlist::where('class_id', $class->id)
                ->where('position', '>', $position)
                ->decrement('position');
                
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Αφαιρεθήκατε από τη λίστα αναμονής'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την αφαίρεση από τη λίστα αναμονής'
            ], 500);
        }
    }
    
    /**
     * Get waitlist status for a user
     */
    public function status(Request $request, GymClass $class)
    {
        $user = auth()->user();
        
        $waitlistEntry = ClassWaitlist::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->first();
            
        if (!$waitlistEntry) {
            return response()->json([
                'in_waitlist' => false
            ]);
        }
        
        return response()->json([
            'in_waitlist' => true,
            'position' => $waitlistEntry->position,
            'status' => $waitlistEntry->status,
            'notified_at' => $waitlistEntry->notified_at,
            'expires_at' => $waitlistEntry->expires_at
        ]);
    }
    
    /**
     * Get full waitlist for a class (admin only)
     */
    public function index(Request $request, GymClass $class)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $waitlist = $class->waitlist()
            ->with('user:id,name,email,phone')
            ->get();
            
        return response()->json([
            'class' => $class->only(['id', 'name', 'date', 'time']),
            'waitlist' => $waitlist,
            'total' => $waitlist->count()
        ]);
    }
    
    /**
     * Process waitlist when a spot opens up
     */
    public function processNextInLine(GymClass $class)
    {
        if (!$class->hasAvailableSpots()) {
            return null;
        }
        
        $nextUser = ClassWaitlist::where('class_id', $class->id)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->first();
            
        if (!$nextUser) {
            return null;
        }
        
        // Store data before transaction for event dispatch
        $eventData = null;
        
        DB::beginTransaction();
        try {
            // Update waitlist entry
            $nextUser->update([
                'status' => 'notified',
                'notified_at' => now(),
                'expires_at' => now()->addHours(2) // 2 hours to confirm
            ]);
            
            // ΔΙΟΡΘΩΣΗ: Ενημερώνουμε και το waitlist booking στον bookings πίνακα
            $waitlistBooking = \App\Models\Booking::where('class_id', $class->id)
                ->where('user_id', $nextUser->user_id)
                ->where('status', 'waitlist')
                ->first();
                
            if ($waitlistBooking) {
                // Μετατρέπουμε το waitlist booking σε confirmed
                $waitlistBooking->update(['status' => 'confirmed']);
                
                // Ενημερώνουμε τους συμμετέχοντες της τάξης
                $confirmedCount = \App\Models\Booking::where('class_id', $class->id)
                    ->whereNotIn('status', ['cancelled', 'waitlist'])
                    ->count();
                $class->update(['current_participants' => $confirmedCount]);
                
                // Αφαιρούμε από το waitlist
                $nextUser->delete();
                
                // Ενημερώνουμε τις θέσεις των υπολοίπων
                ClassWaitlist::where('class_id', $class->id)
                    ->where('position', '>', $nextUser->position)
                    ->decrement('position');
                
                // Prepare event data for dispatch after transaction
                $eventData = [
                    'user' => $nextUser->user,
                    'class' => $class,
                    'booking' => $waitlistBooking->fresh(), // Fresh copy after update
                    'expires_at' => now()->addHours(2)
                ];
            }
            
            DB::commit();
            
            // ΔΙΟΡΘΩΣΗ: Εκπέμπουμε event ΕΚΤΟΣ transaction για να αποφύγουμε duplicates
            if ($eventData) {
                \App\Events\WaitlistSpotAvailable::dispatch(
                    $eventData['user'], 
                    $eventData['class'], 
                    $eventData['booking'], 
                    $eventData['expires_at']
                );
            }
            
            return $nextUser;
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error processing waitlist: ' . $e->getMessage());
            return null;
        }
    }
}