<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Price\HourlyPrice;
use Illuminate\Support\Facades\Log;

class SaveHourlyPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hourlyPrice:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $provider = new \App\Utils\DataProvider;
        $now = explode('.',\Carbon\Carbon::now()->getPreciseTimestamp(3))[0];
        $then = explode('.',\Carbon\Carbon::now()->subHours(1)->getPreciseTimestamp(3))[0];

        $provider->getHourlyData(1,NULL,$now, $then);
        
        HourlyPrice::where('created_at','<',now()->subHours(216))->delete();

        Log::channel('job')->info('Hourly Prices updated');

    }
}
