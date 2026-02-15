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
     * Decline the waitlist spot notification
     */
    public function decline(Request $request, GymClass $class)
    {
        $user = auth()->user();

        // Validate that user has a notified waitlist entry
        $waitlistEntry = ClassWaitlist::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->where('status', 'notified')
            ->first();

        if (!$waitlistEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν βρέθηκε ενεργή ειδοποίηση για αυτό το μάθημα'
            ], 404);
        }

        // Check if request includes preference to stay in waitlist
        $stayInWaitlist = $request->input('stay_in_waitlist', false);

        DB::beginTransaction();
        try {
            if ($stayInWaitlist) {
                // Move to back of waitlist
                $lastPosition = ClassWaitlist::where('class_id', $class->id)
                    ->max('position') ?? 0;

                $waitlistEntry->update([
                    'status' => 'waiting',
                    'position' => $lastPosition + 1,
                    'notified_at' => null,
                    'expires_at' => null
                ]);

                \Log::info('User declined waitlist spot but stayed in waitlist', [
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                    'new_position' => $lastPosition + 1
                ]);

                $message = 'Η θέση σας επιστράφηκε στη λίστα αναμονής';
            } else {
                // Remove from waitlist entirely
                $position = $waitlistEntry->position;
                $waitlistEntry->delete();

                // Update positions for users after this one
                ClassWaitlist::where('class_id', $class->id)
                    ->where('position', '>', $position)
                    ->decrement('position');

                \Log::info('User declined waitlist spot and left waitlist', [
                    'user_id' => $user->id,
                    'class_id' => $class->id
                ]);

                $message = 'Αφαιρεθήκατε από τη λίστα αναμονής';
            }

            // Cancel the associated waitlist booking if it exists
            $waitlistBooking = Booking::where('class_id', $class->id)
                ->where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->first();

            if ($waitlistBooking) {
                $waitlistBooking->update(['status' => 'cancelled']);
            }

            DB::commit();

            // Process next person in line (outside transaction)
            if ($class->hasAvailableSpots()) {
                $this->processNextInLine($class);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'stayed_in_waitlist' => $stayInWaitlist
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error declining waitlist spot', [
                'user_id' => $user->id,
                'class_id' => $class->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την επεξεργασία του αιτήματος'
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
     * Get all waitlists for the authenticated user
     */
    public function myWaitlists(Request $request)
    {
        $user = auth()->user();

        $waitlists = ClassWaitlist::where('user_id', $user->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->with(['gymClass' => function($query) {
                $query->select('id', 'name', 'date', 'time', 'instructor', 'location', 'max_participants', 'current_participants');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($entry) {
                return [
                    'waitlist_id' => $entry->id,
                    'position' => $entry->position,
                    'status' => $entry->status,
                    'notified_at' => $entry->notified_at,
                    'expires_at' => $entry->expires_at,
                    'created_at' => $entry->created_at,
                    'class' => [
                        'id' => $entry->gymClass->id,
                        'name' => $entry->gymClass->name,
                        'date' => $entry->gymClass->date->format('Y-m-d'),
                        'time' => $entry->gymClass->time,
                        'instructor' => $entry->gymClass->instructor,
                        'location' => $entry->gymClass->location,
                        'available_spots' => $entry->gymClass->max_participants - $entry->gymClass->current_participants,
                        'is_full' => $entry->gymClass->isFull(),
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $waitlists,
            'total' => $waitlists->count()
        ]);
    }

    /**
     * Get full waitlist for a class (admin and trainer)
     */
    public function index(Request $request, $classId)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isTrainer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find class, return empty response if not found
        $class = GymClass::find($classId);

        if (!$class) {
            return response()->json([
                'class' => null,
                'waitlist' => [],
                'total' => 0
            ]);
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

    /**
     * Get waitlist summary for all classes (admin/trainer batch endpoint)
     */
    public function summary(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isTrainer()) {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        $waitlistSummary = GymClass::whereHas("waitlist")
            ->withCount(["waitlist as waitlist_count"])
            ->with(["waitlist" => function($query) {
                $query->with("user:id,name,email,phone")->orderBy("position");
            }])
            ->get()
            ->map(function($class) {
                return [
                    "classId" => $class->id,
                    "className" => $class->name,
                    "classDate" => $class->date ? $class->date->format("Y-m-d") : null,
                    "classTime" => $class->time,
                    "instructor" => $class->instructor,
                    "location" => $class->location,
                    "waitlist" => $class->waitlist->toArray(),
                ];
            });

        return response()->json([
            "success" => true,
            "data" => $waitlistSummary,
        ]);
    }
}