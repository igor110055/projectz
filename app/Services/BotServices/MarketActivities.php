<?php

namespace App\Services\BotServices;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketActivities
{

    public $timestamps, $tokenList, $symbol;

    /**
     * New class instance constructor
     *
     * @param string $timestamp
     */
    public function __construct()
    {
        $this->symbol = [];
        $this->timestamps = [];
        $this->tokenList = [];
        //$this->order = new \App\Services\BotServices\TradeServices\SpotOrder();
        $this->whitelist = ['USDT'];
    }

    /**
     * Handle new data by generating a new array if it is not in token
     * token list or process it if token is already in the token list
     *
     * @return void
     */
    public function handle($rawData, $anchor = null)
    {
        foreach ($rawData as $data) {

            if(!endsWithListItem($data->s, $this->whitelist)) {
                continue;
            }

            $original = $this->structure($data);

            $returned = in_array($data->s, $this->symbol) ?
                $this->processData($original) : $this->generate($original);

            $this->checkExpiredTokens($returned);
        }
    }

    /**
     * Check if the token is expired and remove it from the list if it is
     *
     * @param array $returned
     * @return void
     */
    public function checkExpiredTokens(array $returned)
    {
         // 5 dk anons edilmeyenleri takip listesinden çıkar
         if(isset($returned['publishedTime']))
         {
             if( \Carbon\Carbon::createFromTimestampMs($returned['publishedTime'])->diffInMinutes(now()) > 5)
             {
                 $position = array_search($returned['symbol'], $this->symbol);
                 array_splice($this->symbol, $position, 1);
                 unset($this->tokenList[$returned['symbol']]);
             }
         }
         // 10 dk dır hiç anons edilmeyenleri takip listesinden çıkar
         else {
             if( \Carbon\Carbon::parse($returned['created_at'])->diffInMinutes(now()) > 10 )
             {
                 $position = array_search($returned['symbol'], $this->symbol);
                 array_splice($this->symbol, $position, 1);
                 unset($this->tokenList[$returned['symbol']]);
             }
         }
    }

    /**
     * Data structure standardization
     *
     * @param object $data
     * @return array
     */
    public function structure($data)
    {
        return [
            "symbol"                    => $data->s,
            "firstOpenPrice"            => $data->o,
            "lastOpenPrice"             => 0,
            "firstClosePrice"           => $data->c,
            "lastClosePrice"            => 0,
            "highestPrice"              => $data->c,
            "firstBaseVolume"           => $data->v,
            "firstQuoteVolume"          => $data->q,
            "lastBaseVolume"            => 0,
            "lastQuoteVolume"           => 0,
            "firstTimestamp"            => $data->E,
            "lastTimestamp"             => 0,
            "created_at"                => now()->format("Y-m-d H:i:s"),
            "updated_at"                => "",
            "version"                   => 0,
            "bullOrBear"                => 0,
            "publishedDiffPricePercent" => 0,
            "totalDiffPricePercent"     => 0.00,
            "publishedTime"             => null,
            "publishedPrice"            => null,
            "changeClosePrice"          => 0,
            "changeBaseVolume"          => 0,
            "changeQuoteVolume"         => 0,
            "changeOpenPercent"         => 0,
            "changeClosePercent"        => 0,
        ];
    }

    /**
     * store token and timestamp seperately to do double check next time
     *
     * @param array $data
     * @return array
     */
    public function generate($data)
    {
        $this->tokenList[$data["symbol"]]  = $data;
        array_push($this->symbol, $data['symbol']);
        $this->timestamps[]                = createFromTimestamp($data["firstTimestamp"])->format("dmYHi");
        return $data;
    }

    /**
     * Do calculations on the listed items
     *
     * @param array $newRecord
     * @return true
     */
    public function processData($newRecord)
    {
        if(!isset($this->tokenList[$newRecord['symbol']])) return;

        $oldRecord = $this->tokenList[$newRecord["symbol"]];
        $oldRecord["lastOpenPrice"] = $newRecord["firstOpenPrice"];
        $oldRecord["lastClosePrice"] = $newRecord["firstClosePrice"];
        $oldRecord["lastBaseVolume"] = $newRecord["firstBaseVolume"];
        $oldRecord["lastQuoteVolume"] = $newRecord["firstQuoteVolume"];
        $oldRecord["lastTimestamp"] = \Carbon\Carbon::now()->timestamp * 1000;

        $oldRecord["changeOpenPrice"]  = number_format($oldRecord["lastOpenPrice"] -
            $oldRecord["firstOpenPrice"], "8", ".", "");
        $oldRecord["changeClosePrice"] = number_format($oldRecord["lastClosePrice"] -
            $oldRecord["firstClosePrice"], "8", ".", "");

        $oldRecord["changeBaseVolume"] = percentage(
            $oldRecord["lastBaseVolume"] * 1000,
            $oldRecord["firstBaseVolume"] * 1000
        );
        $oldRecord["changeQuoteVolume"] = percentage(
            $oldRecord["lastQuoteVolume"] * 1000,
            $oldRecord["firstQuoteVolume"] * 1000
        );

        if ($oldRecord["highestPrice"] < $newRecord["firstClosePrice"]) {
            $oldRecord["highestPrice"] = $newRecord["firstClosePrice"];
            // Log::channel("stderr")->info("\n"."ATH : {$newRecord["symbol"]} çifti için enyüksek fiyat: {$oldRecord["highestPrice"]} "."\n");
        }

        $oldRecord = $this->changeCalculator($oldRecord);

        $oldRecord["updated_at"] = now()->format("Y-m-d H:i:s");

        $this->tokenList[$newRecord["symbol"]] = $oldRecord;

        return $oldRecord;
    }

    /**
     * Get The Time Differences by the Different periods to define right term for measure time
     *
     * @param array $data
     * @return array
     */
    public function getTimeDiffs($data)
    {
        $time['totalDiffInMinutes'] = createFromTimestamp($data["firstTimestamp"])->diffInMinutes(createFromTimestamp($data["lastTimestamp"]));
        $time['totalDiffInSeconds'] = createFromTimestamp($data["firstTimestamp"])->diffInSeconds(createFromTimestamp($data["lastTimestamp"]));
        // get the time differences by different periods
        if (!is_null($data["publishedPrice"])) {
            $time["sinceDiffInMinutes"] = createFromTimestamp($data["firstTimestamp"])->diffInMinutes(createFromTimestamp($data["publishedTime"]));
            $time["sinceDiffInSeconds"] = createFromTimestamp($data["firstTimestamp"])->diffInSeconds(createFromTimestamp($data["publishedTime"]));
            $time["sincePeriod"] = $time["sinceDiffInMinutes"] < 1 ? "saniye" : "dakika";
            $time["sinceTime"] = $time["sinceDiffInMinutes"] < 1 ? $time["sinceDiffInSeconds"] : $time["sinceDiffInMinutes"];
        }

        else {
            $time["sincePeriod"] = 0;
            $time["sinceTime"] = 0;
            $time["sinceDiffInMinutes"] = 0;
            $time["sinceDiffInSeconds"] = 0;
            $data["sinceDiffPricePercent"] = 0;
        }

        // define measure to use while logging to time
        $changingPeriod = $time["totalDiffInMinutes"] < 1 ? $time["totalDiffInSeconds"] : $time["totalDiffInMinutes"];

        switch ($changingPeriod) {
            case $time["totalDiffInMinutes"] < 1:
                $time["totalPeriod"] = "saniye";
                $time["totalTime"] = $time["totalDiffInSeconds"];
                break;

            case $time["totalDiffInMinutes"] > 60:
                $time["totalPeriod"] = "saat";
                $time["totalTime"] = number_format(round($time["totalDiffInMinutes"] / 60), "0", ".", "");
                break;

            default:
                $time["totalPeriod"] = "dakika";
                $time["totalTime"] = $time["totalDiffInMinutes"];
                break;
        }

        return $time;
    }

    /**
     * Get the differences between first and last recorded prices
     *
     * @param array $data
     * @return array
     */
    public function getPriceDiffs($data)
    {
        $data["totalDiffPricePercent"] = number_format(
            (($data["lastClosePrice"] - $data["firstClosePrice"]) * 100) /
            $data["firstClosePrice"], "2", ".", ",");

        if (!is_null($data["publishedPrice"])) {
            $data["sinceDiffPricePercent"] = number_format(
                (($data["lastClosePrice"] - $data["publishedPrice"]) * 100) /
                $data["publishedPrice"], "2", ".", ",");
        } else {
            $data["sinceDiffPricePercent"] = 0;
        }

        return $data;
    }

    /**
     * Define conditions for a price"s bullish trend
     *
     * @param array $data
     * @param array $time
     * @return array
     */
    public function bullishCriterias($data, $time)
    {
        $minTime = $time["totalDiffInMinutes"];
        $minPercent = $data["totalDiffPricePercent"];

        if ($minPercent > ($data["publishedDiffPricePercent"] * 1.15)) {
            if (($minTime < 1 && $minPercent > 1) or
                ($minTime >= 1 && $minTime < 4 && $minPercent > 2) or
                ($minTime >= 4 && $minPercent > 3)
            ) {
                // $bot = new \App\Services\BotServices\PumpDumpBot($data['symbol']);
                // $bot->handle();
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Define conditions for a price"s bearish trend
     *
     * @param array $data
     * @param array $time
     * @return array
     */
    public function bearishCriterias($data, $time)
    {
        $minTime = $time["totalDiffInMinutes"];
        $minPercent = $data["totalDiffPricePercent"];

        if ($minPercent < ($data["publishedDiffPricePercent"] * 1.15)) {
            if (($minTime < 1 && $minPercent < -1) or
                ($minTime >= 1 && $minTime < 4 && $minPercent < -2) or
                ($minTime >= 4 && $minPercent < -3)
            ) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Define criterias which price will recognise as running prices
     *
     * @param array $data
     * @param array $time
     * @return array
     */
    public function progressionCriterias($data, $time)
    {

        if ((($data["changeBaseVolume"] > 1 || $data["changeQuoteVolume"] > 1) && $time["sinceTime"] < 2) ||
            ($data["changeBaseVolume"] > 3 || $data["changeQuoteVolume"] > 3 && $time["sinceTime"] < 5)
        ) {
            if ($data['bullOrBear'] > 2) {
                $title = "Level IV 💣";
            } else {
                $title = "Level III 🚀";
            }
            $volumes = "{$data["changeBaseVolume"]} ";
        } else {
            if ($data['bullOrBear'] > 1) {
                $title = "Level I 🐥";
             } else {
                $title = "Level II 🌟";
                // print_r($data);
            }

            $volumes = "{$data["changeBaseVolume"]}";
        }

        return [$title, $volumes];
    }

    /**
     * Defines regression criterias
     *
     * @param array $data
     * @param array $time
     * @return array
     */
    public function regressionCriterias($data, $time)
    {
        if ((($data["changeBaseVolume"] > 1 || $data["changeQuoteVolume"] > 1) && $time["sinceTime"] < 2) ||
            ($data["changeBaseVolume"] > 3 || $data["changeQuoteVolume"] > 3 && $time["sinceTime"] < 5)
        ) {
            if ($data['bullOrBear'] > -3) {
                $title = "Level IV 💣";
            } else {
                $title = "Level III 🚀";
            }
            $volumes = "{$data['changeBaseVolume']}";
        } else {
            if ($data['bullOrBear'] > -3) {
                $title = "Level I 🐥";
             } else {
                $title = "Level II 🌟";
             }

            $volumes = "{$data['changeBaseVolume']}";
        }

        return [$title, $volumes];
    }

    /**
     * Defines criterias which price will recognised as comeback price, according to this
     * criteria it must already have a published price and new occurred price must at least
     * %20 higher than that price.
     * ! But if difference of new occured and old prices is bigger than new occured price
     * ! OR new occured price bigger then -1% the pair will be delisted from tracking list.
     *
     * @param array $data
     * @return boolean
     */
    public function pullbackCriterias($data)
    {
        if (!is_null($data['publishedPrice'])) {
            if (
                $data["publishedDiffPricePercent"] > 0 &&
                $data["totalDiffPricePercent"] < ($data["publishedDiffPricePercent"] * 0.80)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Defines criterias which price will recognised as comeback price, according to this
     * criteria it must already have a published price and new occurred price must at least
     * %20 higher than that price.
     * ! But if difference of new occured and old prices is smaller than new occured price
     * ! OR new occured price bigger then -1% the pair will be delisted from tracking list.
     *
     * @param array $data
     * @return boolean
     */
    public function comebackCriterias($data)
    {
        if (!is_null($data['publishedPrice'])) {
            if ($data["totalDiffPricePercent"] > ($data["publishedDiffPricePercent"] * 0.80)) {
                return true;
            }
        }
        return false;
    }


    /**
     * try to store current price position and if can not store then check
     * the reason and try to fix it, if can not, then tell the problem
     *
     * @param array $data
     * @param string $title
     * @return void
     */
    public function storeTicker($data, $title = null)
    {
        // try {
        //     $this->saveData($data, $title);
        // } catch (\Exception $e) {
        //     if ($e->getMessage() === 'Attempt to read property "id" on null') {
        //         \App\Models\Pair::create(['name' => $data['symbol']]);
        //         $this->saveData($data, $title);
        //     } else {
        //         echo ($e->getMessage());
        //     }
        // }
    }

    /**
     * save data to db in a static way
     *
     * @param array $data
     * @param string $title
     * @return void
     */
    public function saveData($data, $title = null)
    {
        // Log::channel('websocket')->info("{$title} : {$data['symbol']} için" .
        //     " {$data['totalDiffPricePercent']} fiyat {$data['changeBaseVolume']}" .
        //     " volume değişimi. Güncel fiyat: {$data['lastClosePrice']}. Tarih: " .
        //     createFromTimestamp($data['firstTimestamp']));
        //     // \App\Models\Price\TickerHighlights::create([
            //     'symbol'              => $data['symbol'],
            //     'price'               => $data['lastClosePrice'],
            //     'eventType'           => $title,
            //     'eventTime'           => $data['firstTimestamp'],
            //     'priceChanging'       => $data['totalDiffPricePercent'],
            //     'baseVolumeChanging'  => $data['changeBaseVolume'],
            //     'quoteVolumeChanging' => $data['changeQuoteVolume'],
            //     //'pair_id'             => \App\Models\Pair::whereName($data['symbol'])->first()->id,
            // ]);
    }

    /**
     * last step of entire process. track, calculate, tag and notify pairs
     * according to given criterias
     *
     * @param array $data
     * @return array
     */
    public function changeCalculator(array $data)
    {
        $since = now()->timestamp * 1000;
        // (currentEvent closePrice (c) - (5mOldEvent closePrice (c))*100.0/(5mOldEvent closePrice (c))
        $data = $this->getPriceDiffs($data);
        $time = $this->getTimeDiffs($data);

        if ($data["totalDiffPricePercent"] > 0) { // pozitif gelişme için
            [$title, $volumes] = $this->progressionCriterias($data, $time); // rally tanımlamaları
            if ($this->bullishCriterias($data, $time)) {
                $data["publishedDiffPricePercent"] = $data["totalDiffPricePercent"];
                $data['publishedTime'] = $since;
                $data["publishedPrice"] = $data["lastClosePrice"];
                $data['bullOrBear'] += 1;
                print("\n"); // temiz bir çıktı istiyorum mk nolmuş
                Log::channel("stderr")->info("\033[35;1;2;46m {$title} : \033[0m {$data["symbol"]} çifti için \033[1;7m{$time["totalTime"]} {$time["totalPeriod"]}\033[0m  içinde" .
                    " Toplam \033[1;7m {$data["totalDiffPricePercent"]}% Fiyat\033[0m, \033[1;7m {$volumes} Hacim\033[0m değişimi gerçekleşti!. Güncel fiyat: {$data['lastClosePrice']}");
                    // open order

                // $this->storeTicker($data, $title);
            }
            if ($this->pullbackCriterias($data)) {
                $diff = $data["publishedDiffPricePercent"] - $data["totalDiffPricePercent"];
                print("\n");
                $title = "Pullback";

                // $this->storeTicker($data, $title);

                $data["publishedDiffPricePercent"] = $data["totalDiffPricePercent"];
                $data['publishedTime'] = $since;
                $data["publishedPrice"] = $data["lastClosePrice"];
                $data['bullOrBear'] -= 1;

                Log::channel("stderr")->info("\033[35;7;46m Pullback : \033[0m {$data["symbol"]} çifti için {$time["sinceTime"]}  {$time["sincePeriod"]} içinde " .
                    "{$data["publishedDiffPricePercent"]}% büyümeden {$diff}% geri çekildi, toplam \033[1;7m{$time["totalTime"]} {$time["totalPeriod"]}\033[0m içerisinde" .
                    " \033[1;7m {$data["totalDiffPricePercent"]}% Fiyat\033[0m  ve \033[1;7m {$volumes} Hacim\033[0m değişimi gerçekleşti.");
            }
        } elseif ($data["totalDiffPricePercent"] < 0) {
            // ? negatif gelişmeler için
            // [$title, $volumes] = $this->regressionCriterias($data, $time);
            // if ($this->bearishCriterias($data, $time)) {
            //     $data["publishedDiffPricePercent"] = $data["totalDiffPricePercent"];
            //     $data["publishedTime"] = $since;
            //     $data["publishedPrice"] = $data["lastClosePrice"];
            //     $data["bullOrBear"] -= 1;
            //     print("\n");
            //     Log::channel("stderr")->info("\033[36;1;2;45m {$title} : \033[0m {$data["symbol"]} çifti için \033[1;7m {$time['totalTime']} {$time['totalPeriod']} \033[0m içinde " .
            //          "\033[1;7m{$data['totalDiffPricePercent']}% Fiyat \033[0mve \033[1;7m {$volumes} Hacim \033[0mdeğişimi gerçekleşti!");
            //     $this->storeTicker($data, $title);
            // } elseif ($this->comebackCriterias($data)) {
            //     $diff = $data["publishedDiffPricePercent"] - $data["totalDiffPricePercent"];
            //     $data["publishedDiffPricePercent"] = $data["totalDiffPricePercent"];
            //     $data["publishedTime"] =  $since;
            //     $data["publishedPrice"] = $data["lastClosePrice"];
            //     $data["bullOrBear"] += 1;
            //     print("\n");
            //     Log::channel("stderr")->info("\033[36;7;45m Comeback : \033[0m {$data["symbol"]} çifti için {$time["sinceTime"]} " .
            //         "{$time["sincePeriod"]} içinde {$data["publishedDiffPricePercent"]}% küçülmeden {$diff}% geri çekildi, toplam " .
            //         "\033[1;7m {$time["totalTime"]} {$time["totalPeriod"]}\033[0m içerisinde" .
            //         "\033[1;7m {$data["totalDiffPricePercent"]}% Fiyat\033[0m  ve \033[1;7m {$volumes} Hacim\033[0m değişimi gerçekleşti.");

            //     $this->storeTicker($data, "Comeback");
            // }
        }

        return $data;
    }
}
