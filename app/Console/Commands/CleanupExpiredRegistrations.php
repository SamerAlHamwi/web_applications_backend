<?php
// app/Console/Commands/CleanupExpiredRegistrations.php

namespace App\Console\Commands;

use App\Repositories\PendingRegistrationRepository;
use Illuminate\Console\Command;

class CleanupExpiredRegistrations extends Command
{
    protected $signature = 'registrations:cleanup';
    protected $description = 'Delete expired pending registrations';

    public function __construct(
        private PendingRegistrationRepository $repository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $deleted = $this->repository->deleteExpired();

        $this->info("Deleted {$deleted} expired pending registration(s).");

        return Command::SUCCESS;
    }
}
