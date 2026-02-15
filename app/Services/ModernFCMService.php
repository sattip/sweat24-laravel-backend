<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ModernFCMService
{
    private $projectId;
    private $serviceAccountPath;
    
    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        $this->serviceAccountPath = config('services.firebase.service_account_path');
    }

    /**
     * Get OAuth2 Access Token for FCM API
     */
    private function getAccessToken()
    {
        try {
            $credentialsPath = storage_path('app/firebase-service-account.json');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception('Firebase service account JSON not found at: ' . $credentialsPath);
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-platform',
                json_decode(file_get_contents($credentialsPath), true)
            );

            $token = $credentials->fetchAuthToken();
            
            if (!isset($token['access_token'])) {
                throw new \Exception('Failed to get access token from service account credentials');
            }
            
            return $token['access_token'];
        } catch (\Exception $e) {
            Log::error('Failed to get FCM access token', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send FCM notification to single token (HTTP v1)
     */
    public function sendToToken($token, $title, $body, $data = [])
    {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => array_map('strval', $data), // FCM requires string values
                'android' => [
                    'notification' => [
                        'icon' => 'ic_notification',
                        'color' => '#FF6B35',
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id' => 'sweat93_notifications'
                    ],
                    'priority' => 'high'
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'alert' => [
                                'title' => $title,
                                'body' => $body
                            ]
                        ]
                    ],
                    'headers' => [
                        'apns-priority' => '10'
                    ]
                ],
                'webpush' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => '/logo-light.png',
                        'badge' => '/logo-light.png',
                        'requireInteraction' => true
                    ]
                ]
            ]
        ];

        return $this->sendFCMRequest($message);
    }

    /**
     * Send FCM notification to multiple tokens
     */
    public function sendToTokens($tokens, $title, $body, $data = [])
    {
        $results = [];
        
        foreach ($tokens as $token) {
            $result = $this->sendToToken($token, $title, $body, $data);
            $results[] = array_merge($result, ['token' => substr($token, 0, 20) . '...']);
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }

    /**
     * Send FCM notification to topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => array_map('strval', $data),
                'android' => [
                    'notification' => [
                        'icon' => 'ic_notification',
                        'color' => '#FF6B35',
                        'sound' => 'default',
                        'channel_id' => 'sweat93_notifications'
                    ],
                    'priority' => 'high'
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'alert' => [
                                'title' => $title,
                                'body' => $body
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->sendFCMRequest($message);
    }

    /**
     * Send request to FCM HTTP v1 API
     */
    private function sendFCMRequest($message)
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $message);

            $result = $response->json();

            if ($response->successful()) {
                Log::info('FCM notification sent successfully (HTTP v1)', [
                    'message_id' => $result['name'] ?? null,
                    'project_id' => $this->projectId
                ]);
                
                return [
                    'success' => true,
                    'response' => $result,
                    'message_id' => $result['name'] ?? null,
                    'platform' => 'fcm_v1'
                ];
            } else {
                Log::error('FCM notification failed (HTTP v1)', [
                    'status' => $response->status(),
                    'response' => $result,
                    'project_id' => $this->projectId
                ]);
                
                return [
                    'success' => false,
                    'error' => $result,
                    'status' => $response->status(),
                    'platform' => 'fcm_v1'
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM request exception (HTTP v1)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'platform' => 'fcm_v1'
            ];
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken($token)
    {
        try {
            // Send a dry-run message to validate token
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => 'Test',
                        'body' => 'Validation'
                    ]
                ],
                'validate_only' => true
            ];

            $result = $this->sendFCMRequest($message);
            return $result['success'];
        } catch (\Exception $e) {
            Log::warning('Token validation failed', [
                'token_preview' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Subscribe token to topic
     */
    public function subscribeToTopic($tokens, $topic)
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://iid.googleapis.com/iid/v1:batchAdd";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'to' => '/topics/' . $topic,
                    'registration_tokens' => is_array($tokens) ? $tokens : [$tokens]
                ]);

            if ($response->successful()) {
                Log::info('Successfully subscribed tokens to topic', [
                    'topic' => $topic,
                    'token_count' => is_array($tokens) ? count($tokens) : 1
                ]);
                return true;
            } else {
                Log::error('Failed to subscribe to topic', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while subscribing to topic', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe token from topic
     */
    public function unsubscribeFromTopic($tokens, $topic)
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://iid.googleapis.com/iid/v1:batchRemove";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'to' => '/topics/' . $topic,
                    'registration_tokens' => is_array($tokens) ? $tokens : [$tokens]
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from topic', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Test FCM connectivity
     */
    public function testConnectivity()
    {
        try {
            $accessToken = $this->getAccessToken();
            
            return [
                'success' => true,
                'project_id' => $this->projectId,
                'service_account_exists' => file_exists(storage_path('app/firebase-service-account.json')),
                'access_token_length' => strlen($accessToken),
                'api_version' => 'HTTP v1'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'project_id' => $this->projectId,
                'service_account_exists' => file_exists(storage_path('app/firebase-service-account.json')),
                'api_version' => 'HTTP v1'
            ];
        }
    }

    /**
     * Send notification with custom payload
     */
    public function sendCustomPayload($token, $payload)
    {
        try {
            $message = [
                'message' => array_merge([
                    'token' => $token
                ], $payload)
            ];

            return $this->sendFCMRequest($message);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'platform' => 'fcm_v1'
            ];
        }
    }

    /**
     * Get project ID
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Check if service is properly configured
     */
    public function isConfigured()
    {
        return !empty($this->projectId) && 
               file_exists(storage_path('app/firebase-service-account.json'));
    }
}
