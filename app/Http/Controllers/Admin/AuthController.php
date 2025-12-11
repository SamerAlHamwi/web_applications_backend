<?php
// app/Http/Controllers/Admin/AuthController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AdminAuthService $adminAuthService
    ) {}

    /**
     * Admin login (returns JWT tokens, NOT session)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->adminAuthService->login($request->only('email', 'password'));

            return response()->json([
                'message' => 'Login successful',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Admin logout (revoke JWT tokens)
     */
    public function logout(): JsonResponse
    {
        $admin = $this->adminAuthService->getAuthenticatedAdmin();

        if (!$admin) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $this->adminAuthService->logout($admin->id);

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Get authenticated admin
     */
    public function me(): JsonResponse
    {
        $admin = $this->adminAuthService->getAuthenticatedAdmin();

        if (!$admin) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'full_name' => $admin->full_name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ]
            ]
        ], 200);
    }
}
