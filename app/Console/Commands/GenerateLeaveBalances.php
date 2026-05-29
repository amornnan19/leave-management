<?php

namespace App\Console\Commands;

use App\Services\LeaveBalanceGenerator;
use Illuminate\Console\Command;

class GenerateLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:generate-balances
                            {year? : The calendar year to generate balances for; defaults to the current year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate yearly leave balances for all users and active paid leave types.';

    /**
     * Execute the console command.
     */
    public function handle(LeaveBalanceGenerator $generator): int
    {
        $year = (int) ($this->argument('year') ?? now()->year);

        $stats = $generator->generateForYear($year);

        $this->info(
            sprintf(
                'Generated %d balance(s) for %d user(s) × %d paid leave type(s) for %d (%d already existed, skipped).',
                $stats['created'],
                $stats['users'],
                $stats['leave_types'],
                $year,
                $stats['skipped'],
            )
        );

        return Command::SUCCESS;
    }
}
