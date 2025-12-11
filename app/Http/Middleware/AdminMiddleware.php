<?php
// app/Http/Middleware/AdminMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Checks JWT token and admin role
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get user from JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Check if user exists
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated. Please login as admin.'
                ], 401);
            }

            // Check if user is admin
            if ($user->role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden. Admin access required.'
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
