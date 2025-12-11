<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     * Checks JWT token, employee role, entity assignment, and active status
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get user from JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Check if user exists
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated. Please login as employee.'
                ], 401);
            }

            // Check if user is employee
            if ($user->role !== 'employee') {
                return response()->json([
                    'message' => 'Forbidden. Employee access required.'
                ], 403);
            }

            // Check if employee is active
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact your administrator.'
                ], 403);
            }

            // ✅ NEW: Check if employee is assigned to an entity
            if (!$user->entity_id) {
                return response()->json([
                    'message' => 'You must be assigned to an entity to access this resource.'
                ], 403);
            }

            return $next($request);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has expired'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token is invalid'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => 'Token not provided'
            ], 401);
        }
    }
}
