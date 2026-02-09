<?php

namespace App\Console\Commands;

use App\Services\ERPInvoiceSyncService;
use Illuminate\Console\Command;

class SyncERPInvoices extends Command
{
    protected $signature = 'erp:sync-invoices';
    protected $description = 'Sync fee invoices from ERPNext to SIP for all students with erp_student_name';

    public function handle(ERPInvoiceSyncService $syncService): int
    {
        $this->info('Syncing invoices from ERPNext...');

        $result = $syncService->syncAll();

        $this->info("Synced {$result['synced']} invoices for {$result['students_processed']} students.");

        if (!empty($result['errors'])) {
            $this->warn('Errors: ' . count($result['errors']));
            foreach ($result['errors'] as $err) {
                $this->error("  {$err['student_id']}: {$err['error']}");
            }
        }

        return 0;
    }
}
