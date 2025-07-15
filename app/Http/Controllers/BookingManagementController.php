<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
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
            $query->where('instructor', 'LIKE', '%' . $request->instructor . '%');
        }
        
        $bookings = $query->orderBy('date')->orderBy('time')->paginate(20);
        
        return view('admin.bookings.index', compact('bookings'));
    }
}