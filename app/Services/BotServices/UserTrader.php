<?php

namespace App\Services\BotServices;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\ExchangeInfo;
use App\Services\BotServices\TechnicalBot\TechnicalFacade as Technical;
use App\Services\BotServices\TechnicalBot\AroonIndicator;
use Illuminate\Support\Facades\Auth;
use App\Services\BotServices\TechnicalBot\SupResTrader;
use App\Services\BotServices\TechnicalBot\GolDeaCrosser;
use \App\Services\BotServices\TechnicalBot\VolumePower;

class UserTrader
{
    /**
     * a New User Trader class
     * classes at that service;
     * goldenCrosses, overs, breakouts, accumilations,
     * patterns, ema, aroon, selectList
     */
    public function __construct()
    {
        $this->user = app()->runningInConsole() ?
            User::first() : Auth::user();
        $this->whiteList     = $this->exchangePairs();
        $this->selectedList  = [];
        $this->goldenCrosses = [];
        $this->os            = [];  // oversold
        $this->au            = [];  // aroon up
        $this->br            = [];  // resistance breaker
        $this->bs            = [];  // support break
        $this->ad            = [];  // aroon down
        $this->ob            = [];  // overbough
        $this->accumilations = [];
        $this->overs         = [];
        $this->breakouts     = [];
        $this->patterns      = [];
        $this->ema           = [];
        $this->strongVolumes = [];
    }

    /**
     * Return Exchange's and user's trade allowed pair's intersect
     *
     * @return array
     */
    public function exchangePairs()
    {

        $walletTokens = $this->walletTokens();

        foreach (ExchangeInfo::getPairList() as $pair) {
            if (Str::endsWith($pair, array_keys($walletTokens))) {
                $whiteList[] = $pair;
            }
        }

        return $whiteList;
    }

    /**
     * Initialize all classes
     *
     * @return array
     */
    public function init()
    {
        $this->goldenCrosses = $this->goldenCrosses();
        $this->accumilations = $this->accumilations();
        $this->overs         = $this->overs();
        $this->breakouts     = $this->breakouts();
        $this->patterns      = $this->patterns();
        $this->ema           = $this->ema();
        $this->aroon         = $this->aroon();
        $this->strongVolumes = $this->volumePower();
    }

    /**
     * Bullish indicators: os, br, au, ema, strongVolumes
     * Bearish indicators: ob, bs, ad, -ema
     *
     * @return array
     */
    public function selectPairs()
    {
        $this->init();

        $this->selectedList = array_intersect(
            array_keys($this->au), array_keys(array_flip($this->os)),
            array_keys($this->br), array_keys($this->ema),
            array_keys($this->strongVolumes)
        );

        if(empty($this->selectedList)){
            $this->selectedList = array_intersect(
                array_keys($this->au), array_keys($this->br),
                array_keys($this->ema),array_keys($this->strongVolumes)
            );
        }

        if(empty($this->selectedList)){
            $this->selectedList = array_intersect(
                array_keys($this->au), array_keys($this->ema),
                array_keys($this->strongVolumes),
            );
        }
        if(empty($this->selectedList)){
            $this->selectedList = array_intersect(
                array_keys($this->ema),
                array_keys($this->strongVolumes)
            );
        }
        if(empty($this->selectedList)){
            $this->selectedList = array_intersect(
                array_keys($this->br),array_keys($this->os),
            );
        }

        if(empty($this->selectedList)){

            sleep(180);
            $nt = new UserTrader;
            // $nt->init();
        }

        return $this->selectedList;
        //if(is_null($tradeLst));
    }

    /**
     * Return golden crosses pairs
     *
     * @return array
     */
    public function goldenCrosses($token = null)
    {
        $gc = new GolDeaCrosser();
        if(is_null($token)){

            $this->goldenCrosses = $gc->isAnyGoldenCross($this->whiteList);
        } else {

            $this->goldenCrosses = $gc->checkGoldenCross($token);
        }

        return $this->goldenCrosses;

    }

    /**
     * Return oversold or over boughtpairs
     *
     * @return array
     */
    public function overs($token = null)
    {
        if(!is_null($token)){
            $overs = Technical::over()->isAny($token);
            if(!$overs) return false;
        }

        $overs = Technical::over()->isAny($this->whiteList);

        $this->overs = [];

        foreach($overs as $key => $over){
            if(isset($overs[$key]['overBought'])){
                $this->ob[] = $key;
            }
            if(isset($overs[$key]['overSold'])){
                $this->os[] = $key;
            }
        }

        return $overs;
    }

    /**
     * Return breaking resistance or support pairs
     *
     * @return array
     */
    public function breakouts($tokens = null)
    {
        $list = is_null($tokens) ? $this->whiteList : $tokens;

        $this->breakouts = SupResTrader::runForList($list);

        if(!$this->breakouts) return false;

        $this->br = $this->bs = [];

        foreach($this->breakouts as $key => $breakout){
            if(isset($this->breakouts[$key]['breakingResistance'])){
                $this->br[$key] = true;
            }
            if(isset($this->breakouts[$key]['breakingSupport'])){
                $this->bs[$key] = true;
            }
        }

        return $this->breakouts;
    }

    /**
     * Return Accumilation Pairs
     *
     * @param string $period
     * @param integer $percent
     * @param null $list
     * @return array
     */
    public function accumilations($period = 'h', $percent = 5, $list = null)
    {

        $this->accumilations = Technical::consolidating($period, $percent, $this->whiteList);

        return $this->accumilations;
    }

    /**
     * populate whitelist with wallet balances
     *
     * @return array
     */
    public function walletTokens()
    {
        // !  fake data

        $this->walletTokens = ['BUSD' => 1000,'BTC' => 0.001];

        return $this->walletTokens;
    }

    /**
     * Check any candlestick pattern for given token
     *
     * @return array
     */
    public function patterns($token = null)
    {
        $list = empty($token) ? $this->whiteList : $token;

        foreach($list as $key => $item)
        {
            $collection = \App\Models\Price\HourlyPrice::where('symbol', $item)
                                        ->orderByDesc('timestamp')->take(16)->get();
            $pr = new \App\Services\BotServices\TechnicalBot\PatternRecogniser($collection);

            $this->patterns[$item] = $pr->__recognise($collection);
        }

        return is_null($this->patterns) ? false : $this->patterns;
    }

    /**
     * Return Exponential Moving Average for given timeframe and periods
     *
     * @param null|array $tokens
     * @param string $timeframe 'hours','days','current'
     * @param integer $slow     3, 10, 20 etc..
     * @param integer $fast     10, 20, 50 etc
     * @return array
     */
    public function ema($tokens = null, $timeframe = 'hours', $slow = 4, $fast = 23)
    {
        $list = empty($tokens) ? $this->whiteList : $tokens ;

        foreach($list as $pair)
        {
            $ma = new \App\Services\BotServices\TechnicalBot\MovingAvarages($pair, $timeframe);
            if($ma->EMovingAvarages($slow,$fast)) $this->ema[$pair] = true;
        }

        return is_null($this->ema) ? false : $this->ema;
    }

    /**
     * Run Aroon indicator calculations
     *
     * @param string $token
     * @param string $timeframe
     * @param integer $period
     * @return boolean
     */
    public function aroon($token = null, $timeframe = 'hours', $period = 25)
    {
        $list = empty($token) ? $this->whiteList : $token;

        foreach($list as $item){
            $ai = new AroonIndicator($item, $timeframe, $period);
            $data = $ai->run();
            if(in_array('bullish', $data)){
                $this->au[$item] = $data;
            }
            if(in_array('bearish', $data)){
                $this->ad[$item] = $data;
            }
            $this->aroon = $data;
        }

        return $this->aroon;
    }

    /**
     * Check volume series if it is strong
     *
     * @param string $token
     * @return int
     */
    public function volumePower($token = null)
    {
        $list = is_null($token) ? $this->whiteList : $token;

        foreach($list as $item){
            $vol = VolumePower::run($item);
            if($vol){
                $this->strongVolumes[$item] = $vol;
            }
        }

        return $this->strongVolumes;
    }
}
