<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // ✅ NEW: Handle rate limit exceptions
        $this->renderable(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson()) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

                return response()->json([
                    'message' => 'Too many requests. Please slow down.',
                    'retry_after' => (int) $retryAfter,
                    'retry_after_human' => $this->formatRetryAfter($retryAfter),
                ], 429, [
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $e->getHeaders()['X-RateLimit-Limit'] ?? null,
                    'X-RateLimit-Remaining' => 0,
                ]);
            }
        });
    }

    /**
     * Format retry after time in human readable format
     */
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } else {
            $hours = ceil($seconds / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
    }
}
