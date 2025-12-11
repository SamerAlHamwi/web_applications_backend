<?php
// app/Http/Controllers/FcmTokenController.php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * Register or update FCM token for the authenticated user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'FCM token registered successfully',
            'data' => [
                'user_id' => $user->id,
                'fcm_token_registered' => true,
            ]
        ], 200);
    }

    /**
     * Remove FCM token (logout from push notifications)
     */
    public function remove(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->fcm_token = null;
        $user->save();

        return response()->json([
            'message' => 'FCM token removed successfully',
        ], 200);
    }

    /**
     * Test push notification
     */
    public function test(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->fcm_token) {
            return response()->json([
                'message' => 'No FCM token registered for this user',
            ], 400);
        }

        $fcmService = app(\App\Services\FcmService::class);

        $notification = [
            'title' => 'Test Notification',
            'body' => 'This is a test push notification from Internet Programs',
        ];

        $data = [
            'type' => 'test',
            'timestamp' => now()->toIso8601String(),
        ];

        $result = $fcmService->sendToDevice(
            $user->fcm_token,
            $notification,
            $data
        );

        // Handle boolean result
        if ($result === true) {
            return response()->json([
                'message' => 'Test notification sent successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to send test notification. Check logs for details.',
            ], 500);
        }
    }
}
