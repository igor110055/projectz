<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\ExchangeInfo;
use Illuminate\Console\Command;
use App\Models\Price\HourlyPrice;
use App\Services\BinanceServices;

class GetRemoteData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getRemote:db {--endsWith=USDT} {--interval=1h} {--limit=500}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get remote data and save it on the local storage';

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
        $endsWith = $this->option('endsWith');
        $interval = $this->option('interval');
        $limit = $this->option('limit');

        $start = \Carbon\Carbon::now();
        print("Started :{$start}"."\n");

        $total = ExchangeInfo::where('status','TRADING')->where('symbol','LIKE','%'.$endsWith)->count();

        ExchangeInfo::where('status','TRADING')->where('symbol','LIKE','%'.$endsWith)
            ->select('symbol')->each(function($item) use ($interval, $limit, $bar){

                $candles = BinanceServices::api()->candlesticks($item['symbol'], $interval, $limit);

                collect($candles)->each(function($single) use ($item, $bar){

                    HourlyPrice::create([
                        'symbol'     => $item['symbol'],
                        'price'      => floatval($single['close']),
                        'open'       => floatval($single['open']),
                        'high'       => floatval($single['high']),
                        'low'        => floatval($single['low']),
                        'count'      => floatval($single['trades']),
                        'timestamp'  => Carbon::createFromTimestampMs($item['openTime']),
                        'volume'     => floatval($single['volume']),
                    ]);

                });

            });

            print('Finished :'.\Carbon\Carbon::now());
            print(Carbon::now()->diffInSeconds($start)."seconds");
    }
}
