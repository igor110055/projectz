<?php

namespace App\Jobs\ScheduleJobs;

use App\Models\Price\DailyPrice;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

class DailyPriceJobs
{
    public static function run()
    {

        $now = now();

        $prices = BinanceServices::api()->prevDay();
        $new = [];

        foreach($prices as $key => $value){
            $new[] = [
                'symbol'             => $value['symbol'],
                'price'              => $value['lastPrice'],
                'open'               => $value['openPrice'],
                'high'               => $value['highPrice'],
                'low'                => $value['lowPrice'],
                'timestamp' 		=> createFromTimestamp($value['closeTime']),
                'priceChange'        => $value['priceChange'],
                'priceChangePercent' => $value['priceChangePercent'],
                'weightedAvgPrice'   => $value['weightedAvgPrice'],
                'volume'             => $value['volume'],
                'quoteVolume'        => $value['quoteVolume'],
                'count'              => $value['count'],
                'lastQty'            => $value['lastQty'],
                'created_at'         => $now
            ];
        }

        DailyPrice::insert($new);

        Log::channel('job')->info('SaveDailyPrice command has been done by cronjob.' );
    }
}
