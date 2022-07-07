<?php

namespace App\Jobs\ScheduleJobs;

use App\Models\ExchangeInfo;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

/**
 * Save Remote Exchange trade currencies
 */
class SaveExchangeInfo
{
    /**
    * TODO: we can check so often to see if there is a new listed token
    *  and use it before everybody else
    *  and even we can extend this strategy to all other markets (like coinbase)
    *  to catch new listed tokens
    **/
    public static function run()
    {
        $remote = BinanceServices::api()->exchangeInfo()['symbols'];

        $climate = new \League\CLImate\CLImate;
        $progress = $climate->progress()->total(count($remote));
        $start = 0;

        foreach ($remote as $r) {
            $local = ExchangeInfo::where('symbol', $r['symbol'])->first();

            if (is_null($local)) {
                ExchangeInfo::create($r);
                Log::channel('job')->info('New Listing Coin found and added!',$r);
            } elseif ($r['status'] !== $local->status) {
                $local->update(['status' => $r['status']]);
                Log::channel('job')->info('Coin Current status changed on exchange!',$r);
            }
            $start++;
            $progress->current($start);
            Log::channel('job')->info('ExchangeInfo synronized with local database.');
        }

        return true;
    }
}
