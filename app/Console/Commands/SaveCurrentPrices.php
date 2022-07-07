<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BinanceServices;
use App\Models\Price\CurrentPrice;
use Illuminate\Support\Facades\Log;

class SaveCurrentPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currentPrices:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command must work every minute to must update local database with current prices';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();

        $prices = BinanceServices::api()->prices();
        $new = [];

        foreach($prices as $key => $value){
            $new[] = ['symbol' => $key, 'price' => $value, 'created_at' => $now ];
        }

        CurrentPrice::insert($new);

        CurrentPrice::where('created_at','<',\Carbon\Carbon::now()->subMinutes(60))->delete();

        Log::channel('job')->info('SaveCurrentPrice command has been done by cronjob. Process time:'.now()->diffInSeconds($now).'s.' );
    }
}
