<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FcmService
{
    private string $projectId;
    private string $credentialsPath;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->credentialsPath = storage_path('app/' . config('services.fcm.credentials'));
    }

    /**
     * Get OAuth2 access token from service account
     */
    /**
     * Get OAuth2 access token from service account
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            if (!file_exists($this->credentialsPath)) {
                Log::error('Firebase credentials file not found', [
                    'path' => $this->credentialsPath
                ]);
                return null;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                json_decode(file_get_contents($this->credentialsPath), true)
            );

            // TEMPORARY - Disable SSL verification for Guzzle
            $httpHandler = \Google\Auth\HttpHandler\HttpHandlerFactory::build(
                new \GuzzleHttp\Client([
                    'verify' => false, // Disable SSL verification
                ])
            );

            $token = $credentials->fetchAuthToken($httpHandler);
            $this->accessToken = $token['access_token'] ?? null;

            return $this->accessToken;

        } catch (\Exception $e) {
            Log::error('Failed to get FCM access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send notification to a single device
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::warning('FCM access token not available');
            return false;
        }

        try {
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $notification['title'] ?? '',
                        'body' => $notification['body'] ?? '',
                    ],
                    'data' => $this->convertDataToStrings($data),
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'default',
                            'color' => '#2196F3',
                            'channel_id' => 'complaints',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                                'alert' => [
                                    'title' => $notification['title'] ?? '',
                                    'body' => $notification['body'] ?? '',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ' . $accessToken,
//                'Content-Type' => 'application/json',
//            ])->post($url, $message);
            $response = Http::withoutVerifying() // TEMPORARY - DISABLE SSL
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'device_token' => substr($deviceToken, 0, 20) . '...',
                    'message_name' => $response->json()['name'] ?? null,
                ]);
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'device_token' => substr($deviceToken, 0, 20) . '...',
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 20) . '...',
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple devices
     * Note: V1 API doesn't support sending to multiple tokens in one request
     * We need to send individual requests
     */
    public function sendToMultipleDevices(array $deviceTokens, array $notification, array $data = []): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'results' => [],
        ];

        foreach ($deviceTokens as $token) {
            $success = $this->sendToDevice($token, $notification, $data);

            if ($success) {
                $results['success']++;
            } else {
                $results['failure']++;
            }

            $results['results'][] = [
                'token' => substr($token, 0, 20) . '...',
                'success' => $success,
            ];
        }

        Log::info('FCM bulk notification completed', [
            'total' => count($deviceTokens),
            'success' => $results['success'],
            'failure' => $results['failure'],
        ]);

        return $results;
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::warning('FCM access token not available');
            return false;
        }

        try {
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $notification['title'] ?? '',
                        'body' => $notification['body'] ?? '',
                    ],
                    'data' => $this->convertDataToStrings($data),
                    'android' => [
                        'priority' => 'HIGH',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ];

//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ' . $accessToken,
//                'Content-Type' => 'application/json',
//            ])->post($url, $message);
            $response = Http::withoutVerifying() // TEMPORARY - DISABLE SSL
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if ($response->successful()) {
                Log::info('FCM topic notification sent', [
                    'topic' => $topic,
                    'response' => $response->json(),
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('FCM topic notification exception', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    /**
     * Convert all data values to strings (FCM V1 requirement)
     */
    private function convertDataToStrings(array $data): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $converted[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $converted[$key] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $converted[$key] = '';
            } else {
                $converted[$key] = (string) $value;
            }
        }

        return $converted;
    }

    /**
     * Validate if FCM is properly configured
     */
    public function isConfigured(): bool
    {
        if (empty($this->projectId)) {
            Log::warning('FCM Project ID not configured');
            return false;
        }

        if (!file_exists($this->credentialsPath)) {
            Log::warning('FCM credentials file not found', [
                'path' => $this->credentialsPath
            ]);
            return false;
        }

        return true;
    }
}
