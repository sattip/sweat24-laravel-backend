<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityStreamController extends Controller
{
    /**
     * Stream activity updates using Server-Sent Events.
     */
    public function stream(Request $request)
    {
        $response = new StreamedResponse(function () {
            $lastId = 0;
            
            while (true) {
                // Get new activities since last check
                $activities = ActivityLog::with(['user', 'subject'])
                    ->where('id', '>', $lastId)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                if ($activities->count() > 0) {
                    $data = $activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'user' => [
                                'name' => $activity->user->name ?? 'System',
                                'email' => $activity->user->email ?? '',
                            ],
                            'activity_type' => $activity->activity_type,
                            'activity_type_label' => $activity->activity_type_label,
                            'activity_icon' => $activity->activity_icon,
                            'activity_color' => $activity->activity_color,
                            'action' => $activity->action,
                            'properties' => $activity->properties,
                            'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                            'created_at_human' => $activity->created_at->diffForHumans(),
                        ];
                    });

                    // Send data as SSE
                    echo "data: " . json_encode($data) . "\n\n";
                    
                    // Update last ID
                    $lastId = $activities->first()->id;
                    
                    // Flush the output
                    ob_flush();
                    flush();
                }

                // Sleep for 2 seconds before checking again
                sleep(2);
                
                // Check if connection is still alive
                if (connection_aborted()) {
                    break;
                }
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');

        return $response;
    }
}