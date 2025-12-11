<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class MonitorRateLimits extends Command
{
    protected $signature = 'rate-limit:monitor {key?}';
    protected $description = 'Monitor rate limit usage';

    public function handle()
    {
        $key = $this->argument('key');

        if ($key) {
            $this->showKeyStatus($key);
        } else {
            $this->showOverview();
        }
    }

    private function showOverview()
    {
        $this->info('Rate Limit Overview');
        $this->line('');

        // Common rate limit keys to check
        $limits = [
            'login' => 'Login Attempts',
            'register' => 'Registration Attempts',
            'create-complaint' => 'Complaint Creation',
            'api' => 'General API Requests',
        ];

        foreach ($limits as $key => $description) {
            $this->line("$description ($key):");
            $this->showKeyStatus($key);
            $this->line('');
        }
    }

    private function showKeyStatus(string $key)
    {
        $attempts = RateLimiter::attempts($key);
        $remaining = RateLimiter::remaining($key, 60); // Assume 60 per minute

        $this->table(
            ['Metric', 'Value'],
            [
                ['Attempts', $attempts],
                ['Remaining', $remaining],
                ['Available At', RateLimiter::availableIn($key) . ' seconds'],
            ]
        );
    }
}
