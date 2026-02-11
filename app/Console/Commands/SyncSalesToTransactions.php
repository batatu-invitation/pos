<?php

namespace App\Console\Commands;

use App\Services\FinancialSyncService;
use Illuminate\Console\Command;

class SyncSalesToTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:sync-sales {--dry-run : Preview changes without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all existing sales to transactions table for unified reporting';

    /**
     * Execute the console command.
     */
    public function handle(FinancialSyncService $syncService)
    {
        $this->info('Starting sales to transactions sync...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $result = $syncService->syncAllExistingSales();
        
        $this->info("Sync completed successfully!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $result['total_processed']],
                ['Successfully Synced', $result['synced']],
                ['Failed', $result['failed']],
            ]
        );
        
        if ($result['failed'] > 0) {
            $this->error("{$result['failed']} transactions failed to sync. Check logs for details.");
            return 1;
        }
        
        $this->info('All sales have been successfully synced to transactions table!');
        return 0;
    }
}