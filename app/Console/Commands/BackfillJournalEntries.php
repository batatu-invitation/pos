<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Transaction;
use App\Services\JournalEntryService;
use Illuminate\Console\Command;

class BackfillJournalEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:backfill 
                            {--sales : Only backfill sales transactions}
                            {--transactions : Only backfill manual transactions}
                            {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill journal entries for existing sales and transactions';

    protected JournalEntryService $journalService;

    /**
     * Execute the console command.
     */
    public function handle(JournalEntryService $journalService): int
    {
        $this->journalService = $journalService;
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting journal entry backfill...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No entries will be created');
        }

        $salesCount = 0;
        $transactionCount = 0;

        // Backfill sales
        if (!$this->option('transactions')) {
            $salesCount = $this->backfillSales($isDryRun);
        }

        // Backfill manual transactions
        if (!$this->option('sales')) {
            $transactionCount = $this->backfillTransactions($isDryRun);
        }

        $this->newLine();
        $this->info('Backfill completed!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Sales', $salesCount],
                ['Transactions', $transactionCount],
                ['Total', $salesCount + $transactionCount],
            ]
        );

        return Command::SUCCESS;
    }

    protected function backfillSales(bool $isDryRun): int
    {
        $this->info('Processing sales...');
        
        $sales = Sale::where('status', 'completed')
            ->whereDoesntHave('journalEntries')
            ->get();

        $count = 0;
        $bar = $this->output->createProgressBar($sales->count());

        foreach ($sales as $sale) {
            if (!$isDryRun) {
                $this->journalService->createFromSale($sale);
            }
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $count;
    }

    protected function backfillTransactions(bool $isDryRun): int
    {
        $this->info('Processing manual transactions...');
        
        $transactions = Transaction::whereNull('source_type')
            ->where('status', 'completed')
            ->whereDoesntHave('journalEntries')
            ->get();

        $count = 0;
        $bar = $this->output->createProgressBar($transactions->count());

        foreach ($transactions as $transaction) {
            if (!$isDryRun) {
                $this->journalService->createFromTransaction($transaction);
            }
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $count;
    }
}
