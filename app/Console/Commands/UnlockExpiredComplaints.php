<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComplaintService;

class UnlockExpiredComplaints extends Command
{
    protected $signature = 'complaints:unlock-expired';
    protected $description = 'Unlock complaints with expired locks';

    public function __construct(
        private ComplaintService $complaintService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $count = $this->complaintService->unlockExpiredComplaints();

        $this->info("Unlocked {$count} expired complaint(s).");

        return Command::SUCCESS;
    }
}
