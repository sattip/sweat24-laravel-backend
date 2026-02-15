<?php

namespace App\Services;

use App\Models\UserPushToken;
use App\Models\NotificationLog;
use App\Services\ModernFCMService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $fcmServerKey;
    private $expoAccessToken;
    private $modernFCMService;

    public function __construct(ModernFCMService $modernFCMService = null)
    {
        $this->fcmServerKey = config('services.fcm.server_key');
        $this->expoAccessToken = config('services.expo.access_token');
        $this->modernFCMService = $modernFCMService ?? new ModernFCMService();
    }

    /**
     * Send notification to specific token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        try {
            if (str_starts_with($token, 'ExponentPushToken')) {
                return $this->sendExpoNotification($token, $title, $body, $data);
            } else {
                // Use modern FCM HTTP v1 API
                if ($this->modernFCMService->isConfigured()) {
                    return $this->modernFCMService->sendToToken($token, $title, $body, $data);
                } else {
                    // Fallback to legacy FCM if modern is not configured
                    return $this->sendFCMNotification($token, $title, $body, $data);
                }
            }
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'platform' => str_starts_with($token, 'ExponentPushToken') ? 'expo' : 'fcm'
            ];
        }
    }

    /**
     * Send notification to user (all devices)
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = UserPushToken::getActiveTokensForUser($userId);

        if ($tokens->isEmpty()) {
            Log::info('No active push tokens found for user', ['user_id' => $userId]);
            return [];
        }

        $results = [];
        foreach ($tokens as $tokenModel) {
            $result = $this->sendToToken(
                $tokenModel->token,
                $title,
                $body,
                $data
            );

            // Log result
            NotificationLog::logAttempt(
                $data['notification_id'] ?? 'manual',
                $userId,
                $tokenModel->token,
                $tokenModel->platform,
                $result['success'] ? 'success' : 'failed',
                $result['response'] ?? null,
                $result['success'] ? null : $result['message']
            );

            // Deactivate invalid tokens
            if (!$result['success'] && preg_match('/invalid|not ?registered/i', $result['message'])) {
                $tokenModel->deactivate();
                Log::info('Deactivated invalid push token', [
                    'user_id' => $userId,
                    'platform' => $tokenModel->platform
                ]);
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Send Expo notification
     */
    private function sendExpoNotification(string $token, string $title, string $body, array $data = []): array
    {
        $payload = [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'badge' => 1,
            'priority' => 'high'
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json'
            ])
            ->post('https://exp.host/--/api/v2/push/send', $payload);

        if ($response->successful()) {
            $responseData = $response->json();
            
            // Check for Expo-specific errors
            if (isset($responseData['data']) && isset($responseData['data'][0]['status']) && $responseData['data'][0]['status'] === 'error') {
                $error = $responseData['data'][0]['message'] ?? 'Unknown Expo error';
                throw new \Exception('Expo error: ' . $error);
            }

            return [
                'success' => true,
                'message' => 'Expo notification sent successfully',
                'response' => $responseData,
                'platform' => 'expo'
            ];
        }

        throw new \Exception('Expo API error: HTTP ' . $response->status() . ' - ' . $response->body());
    }

    /**
     * Send FCM notification
     */
    private function sendFCMNotification(string $token, string $title, string $body, array $data = []): array
    {
        if (empty($this->fcmServerKey)) {
            throw new \Exception('FCM Server Key not configured');
        }

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'priority' => 'high'
            ],
            'data' => $data,
            'priority' => 'high'
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json'
            ])
            ->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->successful()) {
            $responseData = $response->json();
            
            // Check for FCM-specific errors
            if (isset($responseData['failure']) && $responseData['failure'] > 0) {
                $error = $responseData['results'][0]['error'] ?? 'Unknown FCM error';
                throw new \Exception('FCM error: ' . $error);
            }

            return [
                'success' => true,
                'message' => 'FCM notification sent successfully',
                'response' => $responseData,
                'platform' => 'fcm'
            ];
        }

        throw new \Exception('FCM API error: HTTP ' . $response->status() . ' - ' . $response->body());
    }

    /**
     * Test notification service connectivity
     */
    public function testConnectivity(): array
    {
        $results = [];

        // Test Expo connectivity
        try {
            $response = Http::timeout(10)->get('https://exp.host/--/api/v2/push/getReceipts');
            $results['expo'] = [
                'available' => $response->successful(),
                'status' => $response->status()
            ];
        } catch (\Exception $e) {
            $results['expo'] = [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }

        // Test Modern FCM connectivity (HTTP v1)
        try {
            $results['fcm_modern'] = $this->modernFCMService->testConnectivity();
        } catch (\Exception $e) {
            $results['fcm_modern'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        // Test Legacy FCM connectivity (for fallback)
        try {
            $results['fcm_legacy'] = [
                'configured' => !empty($this->fcmServerKey),
                'server_key_length' => $this->fcmServerKey ? strlen($this->fcmServerKey) : 0
            ];
        } catch (\Exception $e) {
            $results['fcm_legacy'] = [
                'configured' => false,
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }

    /**
     * Send bulk notifications efficiently
     */
    public function sendBulkNotifications(array $notifications): array
    {
        $results = [
            'total' => count($notifications),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($notifications as $notification) {
            try {
                $result = $this->sendToUser(
                    $notification['user_id'],
                    $notification['title'],
                    $notification['body'],
                    $notification['data'] ?? []
                );

                $success = collect($result)->contains('success', true);
                if ($success) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'user_id' => $notification['user_id'],
                        'error' => 'Failed to send to all devices'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $notification['user_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}

