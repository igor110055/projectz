<?php

namespace App\Utils;

use App\Models\Price;
use App\Models\ExchangeInfo;
use App\Models\Price\DailyPrice;
use App\Models\Price\HourlyPrice;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

class DataProvider
{

    /**
     * Download public historical data from binance exchange
     *
     * @param string $symbol
     * @param string $interval
     * @param string $date
     * @return void
     */
    public function downloadHistoricalData($symbol, $interval, $date)
    {
        // https://data.binance.vision/data/spot/daily/klines/ADABUSD/1h/ADABUSD-1h-2021-10-24.zip
        $base = "https://data.binance.vision/data/spot/daily/klines/";
        $url = $base . $symbol . '/' . $interval . '/' . $symbol . '-' . $interval . '-' . $date . '.zip';

        $pwd = exec('echo $PWD') . '/public/data/downloaded';

        exec("wget -nd -P {$pwd} -nv {$url}");
    }

    public function getHourlyData(
        $hours = 500, $token = null, $start = null, $end = null, $interval = '1h')
    {
        $started = now();
        print('Started :'.now()->toDateTimeString()."\n");
        if (is_null($token)) {
            $list = ExchangeInfo::where('symbol','like','%'.'USDT')
                 ->where('status','TRADING')->where('symbol','not like','%'.'UP'.'%')
                 ->where('symbol','not like','%'.'DOWN'.'%')->pluck('symbol')
                 ->toArray();
            // $list = ExchangeInfo::getPairList();
        } else {
            $list = $token;
        }

        $total = count($list) * $hours;
        $now = 0;
        $index = 0;
        $limit = 4320;

        $pastTime = is_null($start) ? timeMill($hours, false) : $start;
        $nowTime = is_null($end) ? timeMill($hours, false) : $end;

        foreach ($list as $l) {
            $allDay[$l] = BinanceServices::api()
                ->candlesticks($l,$interval,$hours);

            foreach ($allDay[$l] as $key => $single) {
                $data[] = [
                    'symbol'         => $l,
                    'timeframe'      => $interval,
                    'open'           => $single['open'],
                    'high'           => $single['high'],
                    'low'            => $single['low'],
                    'close'          => $single['close'],
                    'openTime'       => $single['openTime'],
                    'closeTime'      => $single['closeTime'],
                    'assetVolume'    => $single['assetVolume'],
                    'baseVolume'     => $single['baseVolume'],
                    'trades'         => $single['trades'],
                    'assetBuyVolume' => $single['assetBuyVolume'],
                    'takerBuyVolume' => $single['takerBuyVolume'],
                    'created_at'     => now(),
                ];
                $index++;
                $now++;

                if ($index % 20 == 0) {
                    print('.');
                }
            }

            if ($index >= $limit) {
                Price::insert($data);
                print("\n" . "{$total}/{$now} {$index} adet kayıt girildi." . "\n");
                $index = 0;
                unset($data);
            }
        }

        if ($index > 0) {
            Price::insert($data);
            print("\n" . "{$total}/{$now} {$index} adet kayıt girildi." . "\n");
            $index = 0;
        }
        print('Ended :'.now()->toDateTimeString());
        print("Total {$index} record saved in {$started->diff(now())->inSeconds()} seconds.");
        //Log::channel('job')->info(" DataProvider\'s getHourlyData method done by {$now} entries.");
    }

    public function getSelectedPairs(string $endsWith, string $interval = "1h", int $limit = 500)
    {
        print(\Carbon\Carbon::now());
        $total = ExchangeInfo::where('status','TRADING')->where('symbol','LIKE','%'.$endsWith)->count();

        $bar = $this->output->createProgressBar($total*$limit);

        ExchangeInfo::where('status','TRADING')->where('symbol','LIKE','%'.$endsWith)
            ->select('symbol')->each(function($item) use ($bar){
                $candles = BinanceServices::api()->candlesticks($item['symbol'], $interval, $limit);
                collect($candles)->each(function($single) use ($bar){
                    HourlyPrice::create([
                        'symbol'     => $item['symbol'],
                        'price'      => floatval($single['close']),
                        'open'       => floatval($single['open']),
                        'high'       => floatval($single['high']),
                        'low'        => floatval($single['low']),
                        'count'      => floatval($single['trades']),
                        'timestamp'  => createFromTimestamp($key),
                        'volume'     => floatval($single['volume']),
                    ]);
                    $bar->advance();
                });

            });
            $bar->finished();
            print(\Carbon\Carbon::now());
    }

    /**
     * Get historical data from exchange over rest api
     *
     * @param int $dayparam how many days
     * @param bool $daily günlük mü saatlik mi
     * @return void
     */
    public function getHistoricalDataFromApi($dayparam = null, $daily = true)
    {
        $day = is_null($dayparam) ? 24 : $dayparam;

        if ($daily) {
            $timeFrame = '1d';
        } else {
            $timeFrame = '1h';
        }

        $counter = $process = 0;
        foreach (ExchangeInfo::getPairList() as $pair) {
            //print($pair.' '.$timeFrame.' '.$day.' '.timeMill());

            $data = BinanceServices::api()->candlesticks(
                $pair,
                $timeFrame,
                $day,
                timeMill($day, $daily),
                timeMill()
            );

            foreach ($data as $key => $single) {
                $counter++;
                $process++;
                $records[] = [
                    'symbol' => $pair,
                    'open' => $single['open'],
                    'price' => $single['close'],
                    'high' => $single['high'],
                    'low' => $single['low'],
                    'count' => $single['trades'],
                    'volume' => $single['volume'],
                    'timestamp' => createFromTimestamp($key),
                    'created_at' => now(),
                ];
            }
            if ($daily) {
                \App\Models\Price\DailyPrice::insert($records);
            } else {
                \App\Models\Price\HourlyPrice::insert($records);
            }
            print($pair . ' ' . $counter . ' adet girdi ile toplam ' . $process . ' işlem yapıldı' . "\n");
            $counter = 0;
            unset($records);
        }
    }

    /**
     * Get binance historical data between given dates
     *
     * @param string $interval
     * @param array $date
     * @return string
     */
    public function getRemoteData(array $date, string $interval = '1d')
    {
        $pairs = \App\Models\Price\DailyPrice::groupBy('symbol')->get()->pluck('symbol');

        foreach ($pairs as $pair) {
            foreach ($date as $day) {
                $this->downloadHistoricalData($pair, $interval, $day);
            }
        }
        return 'success';
    }

    /**
     * Store Binance python data which downloaded
     * by their own bash script
     *
     * @return void
     */
    public function storeZipFile()
    {
        $base = public_path('data/downloaded/monthly/');
        $path = public_path('data/csv/monthly/');
        $files = getDirFiles($base);
        // remove first 2 item from array -- they comes as "." and ".."
        $files = array_slice($files, 2, count($files));

        foreach ($files as $file) {
            extractZip("{$base}{$file}", $path);
            $filename = str_replace('zip', 'csv', $file);
            print($path . $filename);

            $readed = file($path . $filename);

            foreach ($readed as $read) {
                $data[] = [
                    'symbol' => explode('-', $filename)[0],
                    'price' => number_format($read[4], 10, '.', ','),
                    'high' => number_format($read[2], 10, '.', ','),
                    'low' => number_format($read[3], 10, '.', ','),
                    'open' => number_format($read[1], 10, '.', ','),
                    'volume' => number_format($read[5], 10, '.', ','),
                    'count' => number_format($read[8], 10, '.', ','),
                    'timestamp' => createFromTimestamp($read[0]),
                    'created_at' => now(),
                ];
                print_r($data);
            }
            DailyPrice::insert($data);
            // print(count($data) . ' adet kayıt yapıldı.' . "\n");
            unset($data);
        }
        // return $data;
    }
}
