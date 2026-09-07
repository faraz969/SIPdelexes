<?php

namespace App\Console\Commands;

use App\Services\PaystackReconciliationService;
use Illuminate\Console\Command;

class ReconcilePaystackPayments extends Command
{
    protected $signature = 'paystack:reconcile-payments
                            {--limit=50 : Max payments to check}
                            {--min-age=5 : Only payments older than this many minutes}';

    protected $description = 'Mark abandoned/failed Paystack attempts as failed and finalize successful stuck payments';

    public function handle(PaystackReconciliationService $service): int
    {
        $this->info('Reconciling pending Paystack payments...');

        $result = $service->reconcilePending(
            null,
            (int) $this->option('limit'),
            (int) $this->option('min-age')
        );

        $this->info(sprintf(
            'Checked: %d | Failed: %d | Completed: %d | Skipped: %d | Errors: %d',
            $result['checked'],
            $result['failed'],
            $result['completed'],
            $result['skipped'],
            $result['errors']
        ));

        return 0;
    }
}
