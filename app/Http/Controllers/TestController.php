<?php

namespace App\Http\Controllers;

use App\Models\OwnerNotification;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function createTestOrderNotification()
    {
        $notification = OwnerNotification::create([
            'title' => 'Νέα Παραγγελία',
            'message' => 'Ο πελάτης Γιάννης Παπαδόπουλος έκανε παραγγελία για Power Bar (2x)',
            'type' => 'order',
            'priority' => 'high',
            'is_read' => false,
            'data' => [
                'order_id' => 'SW24-123456',
                'customer_name' => 'Γιάννης Παπαδόπουλος',
                'items' => [
                    ['name' => 'Power Bar', 'quantity' => 2]
                ]
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Test notification created',
            'notification' => $notification
        ]);
    }
}