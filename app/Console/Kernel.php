<?php

namespace App\Console;

use App\Models\Rate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $properties = Http::withHeaders(['X-CoinAPI-Key' => '9B554A1B-EBF1-4B3A-B7C0-B2346614E136'])->get('https://rest.coinapi.io/v1/exchangerate/USDT/NGN')->json();
            $postData = new Rate();
            $postData->rate_normal      =   (int)$properties['rate'];
            $postData->rate_decimal     =   $properties['rate'];
            $postData->assets_id_from   =   $properties['asset_id_base'];
            $postData->assets_id_to     =   $properties['asset_id_quote'];
            $postData->compare          =   null;
            $postData->status           =   2;

            $postData->save();
        })->hourly();


        

        // $schedule->command('inspire')->hourly();
        $schedule->command('logs:clear')->daily();
        $schedule->command('tasks:observer')->everyThreeHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
