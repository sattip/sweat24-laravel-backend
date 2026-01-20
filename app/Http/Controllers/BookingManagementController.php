<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Traits\SearchableTrait;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
    use SearchableTrait;
    public function index(Request $request)
    {
        $query = Booking::with('user');
        
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to today
            $query->whereDate('date', now()->format('Y-m-d'));
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('instructor') && $request->instructor) {
            $this->addSafeLikeWhere($query, 'instructor', $request->instructor);
        }
        
        $bookings = $query->orderBy('date')->orderBy('time')->paginate(20);
        
        return view('admin.bookings.index', compact('bookings'));
    }
}