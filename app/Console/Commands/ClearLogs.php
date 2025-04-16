<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:clear-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    protected $signature = 'logs:clear';
    protected $description = 'Clear Laravel log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // File::put(storage_path('logs/laravel.log'), '');
        // $this->info('Logs cleared successfully.');

        // $logFiles = [
        //     glob(storage_path('logs/laravel*.log')),
        //     glob(storage_path('logs/chat*.log')),
        //     glob(storage_path('logs/worker.log')),
        // ];

        // foreach ($logFiles as $file) {
        //     if (file_exists($file)) {
        //         File::put($file, '');
        //     }
        // }

        $logFiles = array_merge(
            glob(storage_path('logs/laravel*.log')), // Matches laravel.log, laravel-2025-01-30.log, etc.
            glob(storage_path('logs/chat*.log')),    // Matches chat.log, chat-2025-01-30.log, etc.
            glob(storage_path('logs/worker*.log'))   // Matches worker.log, worker-2025-01-30.log, etc.
        );

        foreach ($logFiles as $file) {
            File::put($file, ''); // Clears the file content
        }

        $this->info('All logs cleared successfully.');
    }
}
