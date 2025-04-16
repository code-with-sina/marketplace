<?php

namespace App\Console\Commands;

use App\Services\QueueObserverService;
use Illuminate\Console\Command;

class QueueObsever extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:observer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Observes and processes queued trade and customer status events.';

    /**
     * Execute the console command.
     */
    public function handle(QueueObserverService $queueObserverService)
    {
        $queueObserverService->observeTradeRejection();
        $queueObserverService->observeCreateSubAccount();
        $queueObserverService->observeReTriggerKyc();
        $queueObserverService->checkDepositAccounts();
    }
}
