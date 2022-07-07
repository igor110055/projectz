<?php

namespace App\Services\BotServices;

class UserMarket {

    public function __construct()
    {
        $this->tokenList = [];
    }

    /**
     * Handle a given pair
     *
     * @param array $pair
     * @return array
     */
    public function handle($pair)
    {
        $token = $this->format($pair);

        if(!in_array($token['symbol'], array_keys($this->tokenList))){
            $this->tokenList[$token['symbol']] = $token;
        } else {
            $token = $this->calculate($token);
            $this->declareOrRemove($token);
            // print_r('bok');
        }
    }

    /**
     * notify tokens if suits the criterias or remove if times up
     *
     * @param string $token
     * @return void
     */
    public function declareOrRemove($token)
    {

        if($this->pumpCriterias($token)){
            $this->tokenList[$token['symbol']]   = $token;
        }
        // 10 dakikadır bildirimi geçmeyen tokenın sayacı sıfırlansın.
        if($this->tokenList[$token['symbol']]['publishedTimestamp'] > 1200){
            unset($this->tokenList[$token['symbol']]);
            print("{$token['symbol']} delisted.");
        }
    }

    /**
     * Do some simple math over attributes
     *
     * @param array $pair
     * @return array
     */
    public function calculate($pair)
    {
        $copy                  = $this->tokenList[$pair['symbol']];
        $copy['lastPrice']       = $pair['firstPrice'];
        $copy['diffPrice']       = number_format(($copy['lastPrice'] - $copy['firstPrice']) * 100 / $copy['firstPrice'] , '2','.',',');
        $copy['lastVolume']      = $pair['firstVolume'];
        $copy['diffVolume']      = number_format(($copy['lastVolume'] - $copy['firstVolume']) * 100 / $copy['firstVolume'] , '2','.',',');
        $copy['lastQuoteVolume'] = $pair['firstQuoteVolume'];
        $copy['diffQuoteVolume'] = $copy['lastQuoteVolume'] - $copy['firstQuoteVolume'];
        $copy['lastTimestamp']   = $pair['firstTimestamp'];
        $copy['diffTimestamp']   = \Carbon\Carbon::parse($copy['lastTimestamp'])->diff(\Carbon\Carbon::parse($copy['firstTimestamp']));
        $this->tokenList[$pair['symbol']] = $copy;
        return $copy;
    }

    /**
     * Format input array as a standart
     *
     * @param array $pair
     * @return array
     */
    public function format($pair)
    {
        return [
            'symbol'             => $pair['s'],
            'firstPrice'         => $pair['o'],
            'lastPrice'          => 0,
            'diffPrice'          => 0,
            'closePrice'         => $pair['c'],
            'highPrice'          => $pair['c'],
            'lowPrice'           => $pair['l'],
            'firstVolume'        => $pair['v'],
            'lastVolume'         => 0,
            'diffVolume'         => 0,
            'firstQuoteVolume'    => $pair['q'],
            'lastQuoteVolume'    => 0,
            'diffQuoteVolume'    => 0,
            'firstTimestamp'     => $pair['E'],
            'lastTimestamp'      => 0,
            'diffTime'          => 0,
            'published'          => false,
            'publishedPrice'     => 0,
            'publishedTimestamp' => 0,
        ];
    }

    /**
     * Declared pump criterias
     *
     * @param array $token
     * @return boolean
     */
    public function pumpCriterias($token)
    {
        if(
            ($token['lastPrice'] > $token['publishedPrice'] * 1.1)
            // ($token['diffTimestamp']->s < 60 && $token['diffPrice'] > 1.2 && $token['diffVolume'] > 1) ||
            // ($token['diffTimestamp']->s < 120 && $token['diffPrice'] > 2.4 && $token['diffVolume'] > 2) ||
            // ($token['diffTimestamp']->s < 180 && $token['diffPrice'] > 3.5 && $token['diffVolume'] > 3) ||
            // ($token['diffTimestamp']->s > 180 && $token['diffPrice'] > 4.5 && $token['diffVolume'] > 4)
        )
        {
            print(".\n"."{$token['symbol']} {$this->tokenList[$token['diffTimestamp']]->s} saniye içinde {$this->tokenList[$token['diffPrice']]} fiyat ve ".
            "{$this->tokenList[$token['diffVolume']]} hacim değişimi gerçekleştirdi."."\n");
            return true;
        } else {
            // print(".\n"."{$token['symbol']} {$token['diffTimestamp']->s} saniye içinde {$token['diffPrice']} fiyat ve ".
            // "{$token['diffVolume']} hacim değişimi gerçekleştirdi."."\n");
            return false;
        }
    }
}
