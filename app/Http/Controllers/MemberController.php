<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('membership_type', '!=', 'Admin')
                    ->orWhereNull('membership_type')
                    ->with(['packages' => function($q) {
                        $q->where('status', 'active');
                    }]);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $members = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.members.index', compact('members'));
    }
}