<?php

namespace App\Services\BotServices;

use Illuminate\Support\Str;
use App\Services\BinanceServices;
use App\Services\WebsocketServices\Client;

class MarketAnalyze
{
    /**
     * MarketActivities sınıfını temize çekmek ve düzenlemek için
     * baştan yazdığım sınıf. araya giren işler yüzünden tamamlayamadım.
     * Atıldır.
     */
    public function __construct()
    {
        $this->api = BinanceServices::api();
        $this->print = new \League\CLImate\CLImate;
        $this->whitelist = ['USDT'];
        $this->symbols = [];
        $this->data = [];
        $this->level1 = 0.1;
        $this->level2 = 0.3;
        $this->level3 = 0.5;
    }

    /**
     * Handle websocket event
     *
     * @param array $symbol
     * @return array
     */
    public function handle($json)
    {
        foreach($json as $single){

            $record = collect($this->structure($single));

            // ! deneme için whitelist kontrolü kapatıldı
            // if(endsWithWhiteList($record['symbol'])){
            //     return false;
            // }

            if(!$this->isListed($record['symbol'])){
                $this->makeItListed($record);
            }

            if($this->processeIt($record)){
                $this->calculate($record);
            }

            $this->isPumpOrDump($record['symbol']);

            if($this->isExceeded($record['symbol'])){
                // print('exceeded');
                $this->removeIt($record['symbol']);
            }

            //$backup = $this->data[$record['symbol']];

            // print_r($backup);
        }
    }

    /**
     * Check if symbol is listed
     *
     * @param string $symbol
     * @return boolean
     */
    public function isExceeded(string $symbol)
    {
        $pair = $this->data[$symbol];

        if($this->checkIfPublished($symbol)){
            $sincePublished = parseForDiffInMin($pair['firstSeen'], $pair['publishedSeen']);
            if($sincePublished > 15){
                return true;
            }
        }
        else {
            $sincePublished = parseForDiffInMin($pair['firstSeen'], $pair['lastSeen']);
            if($sincePublished > 10){
                return true;
            }
        }

        return false;
    }

    /**
     * Remove pair from list
     *
     * @param string $symbol
     * @return void
     */
    public function removeIt($symbol)
    {
        unset($this->data[$symbol]);
        $position = array_search( $symbol, $this->symbols );
        array_splice($this->symbols, $position, 1);
    }

    /**
     * check if symbol is pump or dump
     *
     * @param string $symbol
     * @return bool
     */
    public function isPumpOrDump($symbol)
    {
        $price  = $this->data[$symbol]['priceChange'];
        $time   = $this->data[$symbol]['timeChange'];
        $volume = $this->data[$symbol]['volumeChange'];

        $publishedPrice = $this->checkIfPublished($symbol) ?
            $this->data[$symbol]['publishedPrice'] : 0 ;

        // en son anons edildiğinden beri %0.5 büyümemişse boş sonuç döndür
        $this->hasEnoughProgression($symbol, $price);

        if( $this->pumpCriterias($price,$time,$volume) && $price > $publishedPrice * 1.1  ){
            $this->publishIt('PUMP',$symbol,$price,$volume,$this->data[$symbol]['lastSeen']);
        }
        elseif ( $this->dumpCriterias($price,$time,$volume) && $price < $publishedPrice * -1.1){
            $this->publishIt('DUMP',$symbol,$price,$volume,$this->data[$symbol]['lastSeen']);
        }

        return true;
    }

    /**
     * işlemin Pump olarak tanımlanabilmesi için hangi zaman
     * aralığında ne oranda fiyat ve hacim değişikliği gerektiğini
     * tanımlar, bu koşulu sağlayan işlemler bildirimi çıkarlar
     *
     * @param float $price
     * @param int $time
     * @param float $volume
     * @return bool
     */
    public function pumpCriterias($price, $time, $volume)
    {
        return $price > $this->level1 && $time < 1 && $volume > $this->level1 ||
            $price > $this->level2 && $time < 3 && $volume > $this->level2 ||
            $price > $this->level3 && $time > 3 && $volume > $this->level3;
    }

    /**
     * işlemin Dump olarak tanımlanabilmesi için hangi zaman
     * aralığında ne oranda fiyat ve hacim değişikliği gerektiğini
     * tanımlar, bu koşulu sağlayan işlemler bildirimi çıkarlar
     *
     * @param float $price
     * @param int $time
     * @param float $volume
     * @return bool
     */
    public function dumpCriterias($price, $time, $volume)
    {
        return $price < -1*$this->level1 && $time < 1 && $volume > $this->level1 ||
            $price < -1*$this->level2 && $time < 3 && $volume > $this->level2 ||
            $price < -1*$this->level3 && $time < 5 && $volume > $this->level3;
    }


    public function hasEnoughProgression($symbol, $price)
    {
        if($this->checkIfPublished($symbol)){
            $pair = $this->data[$symbol];
            // print_r($pair);
            $diff = percen($pair['publishedPrice'], $pair['firstPrice']);
            if( ($diff > $pair['priceChange'] * 1.01) ||
                ($diff < $pair['priceChange'] * 0.99))
            {
                // print("{$symbol} için yeterli büyüme sağlanamadı"."\n");
                return false;
            } else {
                print('bok');
                return true;
            }
        }
    }


    /**
     * Notify about pair
     *
     * @param string $side
     * @param string $symbol
     * @param string $price
     * @param string $volume
     * @param string $time
     * @return void
     */
    public function publishIt($side, $symbol, $price, $volume, $time)
    {
        $this->savePublished($side, $symbol, $price, $volume, $time);

        [ $time, $period ] = $this->defineTime($symbol);

        if($side === 'PUMP' || $side === 'DUMP'){
            $bg = $side === 'PUMP' ? 'background_blue' : 'background_magenta';
            $this->print->out("\n"."<".$bg."> {$side} </".$bg."> ".
                "<bold><underline><cyan>{$symbol}</cyan></underline></bold> <bold>{$time}</bold> {$period} içinde".
                " <bold>{$price}</bold> fiyat ve <bold>{$volume}</bold> hacim değişimi kayıt etti.");
        }
    }

    public function defineTime($symbol)
    {
        $pair = $this->data[$symbol];
        if(parseForDiffInMin($pair['firstSeen'], $pair['lastSeen']) > 0){
            $time = parseForDiffInMin($pair['firstSeen'], $pair['lastSeen']);
            $period = 'dk';
        } else {
            $time = parseForDiffInMin($pair['firstSeen'], $pair['lastSeen'], true);
            $period = 'sn';
        }

        return [ $time, $period ];
    }

    /**
     * Save published data to the list
     *
     * @param string $side
     * @param string $symbol
     * @param string $price
     * @param string $volume
     * @param string $time
     */
    public function savePublished($side, $symbol, $price, $volume, $time)
    {
        $backup = $this->data[$symbol];

        $backup['publishedPrice'] = $price;
        $backup['publishedVolume'] = $volume;
        $backup['publishedSeen'] = $time;

        $this->data[$symbol] = $backup;
    }

    /**
     * Check if given item has been published before or not
     *
     * @param array $record
     * @return bool
     */
    public function checkIfPublished(string $symbol)
    {
        return isset($this->data[$symbol]['publishedSeen']);
    }

    /**
     * Calculate diffirences of the new and old orders
     *
     * @param array $record
     * @return void
     */
    public function calculate($record)
    {
        $old = $this->data[$record['symbol']];

        $old['priceChange'] = percen($old['lastPrice'], $old['firstPrice']);
        $old['volumeChange'] = percen($old['lastVolume'], $old['firstVolume']);
        $old['timeChange'] = parseForDiffInMin($old['firstSeen'], $old['lastSeen']);

        $this->data[$record['symbol']] = $old;
    }

    /**
     * Process the record and save the new callculations
     *
     * @param array $record
     * @return bool
     */
    public function processeIt($record)
    {
        $old = $this->data[$record['symbol']];

        $old['lastPrice'] = $record['firstPrice'];
        $old['lastVolume'] = $record['firstVolume'];
        $old['lastSeen'] = $record['firstSeen'];

        $this->data[$record['symbol']] = $old;

        return true;
    }

    /**
     * Structure of the any record
     *
     * @param array $record
     * @return array
     */
    public function structure($raw)
    {
        $original['symbol'] = $raw->s;
        $original['firstPrice'] = $raw->c;
        $original['firstVolume'] = $raw->v;
        $original['firstSeen'] = now()->toDateTimeString();
        $original['lastPrice'] = null;
        $original['lastVolume'] = null;
        $original['lastSeen'] = null;
        $original['publishedPrice'] = null;
        $original['publishedVolume'] = null;
        $original['publishedSeen'] = null;

        return $original;
    }

    /**
     * add the given symbol to the list
     *
     * @param array $raw
     * @return bool
     */
    public function makeItListed($raw)
    {
        $this->data[$raw['symbol']] = $raw;
        array_push($this->symbols, $raw['symbol']);

        return true;
    }

    /**
     * Check if the given symbol is listed or not
     *
     * @param string $symbol
     * @return bool
     */
    public function isListed(string $symbol)
    {
        return in_array($symbol, $this->symbols);
    }

    /**
     * Establish a websocket connection
     *
     * @return array
     */
    public function socketConnection()
    {
        // $socket = new \WebSocket\Client("wss://stream.binance.com:9443/ws/!miniTicker@arr"); // stop
        $socket = new \WebSocket\Client("wss://fstream.binance.com/ws/!miniTicker@arr"); // future
        while(true){
            try
            {
                print('.');
                $message = json_decode($socket->receive());
                $this->handle($message);
            }
            catch(\Websocket\ConnectionException $e){
                print($e->getMessage());
            }
        }
    }
}
