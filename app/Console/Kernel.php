<?php

namespace App\Console;

use App\Models\Rate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
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

        // $schedule->call(function () {
        //     $properties = Http::get('https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=ngn')->json();
        //     $postData = new Rate();
        //     $postData->rate_normal      =   (int)$properties['tether']['ngn'];
        //     $postData->rate_decimal     =   $properties['tether']['ngn'];
        //     $postData->assets_id_from   =   'tether';
        //     $postData->assets_id_to     =   'ngn';
        //     $postData->compare          =   null;
        //     $postData->status           =   2;

        //     $postData->save();
        // })->hourly();
        

         $schedule->call(function () {
            try{
                $response = Http::get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => 'tether',
                    'vs_currencies' => 'ngn'
                ]);

                if ($response->failed()) {
                    Log::error('CoinGecko request failed', ['status' => $response->status()]);
                    return;
                }

                $data = $response->json();

                if (!isset($data['tether']['ngn'])) {
                    Log::warning('Unexpected CoinGecko response structure', ['data' => $data]);
                    return;
                }

                $rateValue = $data['tether']['ngn'];

                Rate::create([
                    'rate_normal'    => (int) $rateValue,
                    'rate_decimal'   => $rateValue,
                    'assets_id_from' => 'tether',
                    'assets_id_to'   => 'ngn',
                    'compare'        => null,
                    'status'         => 2,
                ]);

                Log::info('Rate updated successfully', ['tether_to_ngn' => $rateValue]);

            }catch(\Exception $e){
                Log::error('Error fetching CoinGecko rate', ['error' => $e->getMessage()]);
            }
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
