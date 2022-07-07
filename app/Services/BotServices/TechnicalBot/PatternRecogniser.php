<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Str;

class PatternRecogniser
{

    public function __construct($token)
    {
        $this->token = $token;
        $this->priceSeries = priceSeries($this->token, true);
        // $this->__init($this->priceSeries);
    }

    /**
     * let the class do to its job
     *
     * @return integer
     */
    public function __init()
    {
        return $this->__count($this->__recognise());
    }

    /**
     * Run all methods to recognise candlestick patterns on the chart
     *
     * @return array
     */
    public function __recognise()
    {
        foreach (get_class_methods($this) as $method)
        {
            if (!\Illuminate\Support\Str::startsWith($method, '__')) {
                if (gettype($this->$method()) == 'array') {
                    $return[$method] = array_filter($this->$method(), function ($value) {
                        return $value > 0 || $value < 0;
                    });
                }
            }
        }

        foreach ($return as $key => $data)
        {
            if (!empty($data)) {
                $result[$key] = $data;
            }
        }

        return isset($result) ?  $result : false ;
    }

    /**
     * Count recognised patterns integer values
     *
     * @param array $result
     * @return integer
     */
    public function __count($result)
    {
        $count = count($this->priceSeries['close']) - 2;
        $base = 0;

        foreach ($result as $key => $value)
        {
            if (isset($result[$key][$count]))
            {
                if (Str::endsWith($key, 'Bearish'))
                {
                    $base = $base - 1;
                }
                elseif (Str::endsWith($key, 'Bullish'))
                {
                    $base = $base + 1;
                }
            }
        }

        return $base;
    }

    //  bearish reversal
    public function twocrowsBearish()
    {
        return trader_cdl2crows(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish reversal
    public function threeblackcrowsBearish()
    {
        return trader_cdl3blackcrows(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // potential trend reversal
    public function threeinsideTrendReversal()
    {
        return trader_cdl3inside(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish continuation pattern
    public function threelinestrikeBearish()
    {
        return trader_cdl3linestrike(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    //  reversal patterns
    public function threeoutsideTrendReversel()
    {
        return trader_cdl3outside(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish reversal pattern
    public function threestarsinsouthBullish()
    {
        return trader_cdl3starsinsouth(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish candlestick pattern
    public function threewhitesoldiersBullish()
    {
        return trader_cdl3whitesoldiers(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish candlestick pattern
    public function abandonedbabyBullish()
    {
        return trader_cdlabandonedbaby(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish reversal pattern.
    public function advanceblockBearish()
    {
        return trader_cdladvanceblock(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // considered a minor trend reversal pattern
    public function beltholdTrendReversal()
    {
        return trader_cdlbelthold(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // Reversal of the trend in the market.
    public function breakawayTrendReversal()
    {
        return trader_cdlbreakaway(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // continuation candlestick pattern
    public function closingmarubozuContinuation()
    {
        return trader_cdlclosingmarubozu(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish reversal pattern
    public function concealbabyswallBullish()
    {
        return trader_cdlconcealbabyswall(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // reversal of market sentiment
    public function counterattackTrendReversal()
    {
        return trader_cdlcounterattack(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish reversal candlestick
    public function darkcloudcoverBearish()
    {
        return trader_cdldarkcloudcover(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // kararsız bir mumdur.
    public function dojiIndecision()
    {
        return trader_cdldoji(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // Düşüş trendi sırasında ortaya çıkar ve trendin değişeceğini haber verir
    public function dojistarBearish()
    {
        return trader_cdldojistar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    //Potential reversal in price to the downside or upside *
    public function dragonflydojiTrendReversal()
    {
        return trader_cdldragonflydoji(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // possible trend reversal.
    public function engulfingTrendReversal()
    {
        return trader_cdlengulfing(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // Bearish.
    public function eveningdojistarBearish()
    {
        return trader_cdleveningdojistar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish candlestick
    public function eveningstarBearish()
    {
        return trader_cdleveningstar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // continuation pattern
    public function gapsidesidewhiteContinuation()
    {
        return trader_cdlgapsidesidewhite(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish reversal candlestick pattern
    public function gravestonedojiBearish()
    {
        return trader_cdlgravestonedoji(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish reversal candlestick pattern
    public function hammerBullish()
    {
        return trader_cdlhammer(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish candlestick pattern that forms at the end of an uptrend and warns of lower prices to come
    public function hangingmanBearish()
    {
        return trader_cdlhangingman(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // Bullish & Bearish Harami Pattern
    public function haramiBoth()
    {
        return trader_cdlharami(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish reversal  The pattern should be confirmed on the nearest following candles.
    public function haramicrossBullish()
    {
        return trader_cdlharamicross(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // market indecisions.
    public function highwave()
    {
        return trader_cdlhighwave(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    public function hikkake()
    {
        return trader_cdlhikkake(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish
    public function hikkakemodBearish()
    {
        return trader_cdlhikkakemod(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish
    public function homingpigeonBullish()
    {
        return trader_cdlhomingpigeon(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish reversal pattern
    public function identical3crowsBearish()
    {
        return trader_cdlidentical3crows(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // continuation of the current trend in the market.
    public function inneckContinuation()
    {
        return trader_cdlinneck(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // trend-reversal signal.
    public function invertedhammerTrendReversal()
    {
        return trader_cdlinvertedhammer(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // new trend opposite to the trend previous.
    public function kickingTrendReversal()
    {
        return trader_cdlkicking(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // most reliable reversal patterns and usually signifies a dramatic change in the fundamental.
    public function kickingbylengthBoth()
    {
        return trader_cdlkickingbylength(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // price reversal
    public function ladderbottomTrendReversal()
    {
        return trader_cdlladderbottom(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // indecision
    public function longleggeddojiIndecision()
    {
        return trader_cdllongleggeddoji(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // represents a bearish force in the market.
    public function longlineBearish()
    {
        return trader_cdllongline(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish
    public function marubozuBearish()
    {
        return trader_cdlmarubozu(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish reversal pattern
    public function matchinglowBullish()
    {
        return trader_cdlmatchinglow(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // indicates the continuation of a prior move
    public function matholdContinuation()
    {
        return trader_cdlmathold(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish three-candlestick reversal pattern.
    public function morningdojistarBullish()
    {
        return trader_cdlmorningdojistar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish candlestick pattern
    public function morningstarBullish()
    {
        return trader_cdlmorningstar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // continuation pattern that is also bearish.
    public function onneckBearish()
    {
        return trader_cdlonneck(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // potential short-term reversal
    public function piercingTrendReversal()
    {
        return trader_cdlpiercing(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    //  indecision in the marketplace.
    public function rickshawmanIndecision()
    {
        return trader_cdlrickshawman(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish continuation candlestick pattern
    public function risefall3methodsBearish()
    {
        return trader_cdlrisefall3methods(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish
    public function separatinglinesBullish()
    {
        return trader_cdlseparatinglines(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // price could start falling.
    public function shootingstarBearish()
    {
        return trader_cdlshootingstar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    public function shortline()
    {
        return trader_cdlshortline(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    public function spinningtop()
    {
        return trader_cdlspinningtop(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // indicates a likely bearish reversal.
    public function stalledpatternBearish()
    {
        return trader_cdlstalledpattern(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // trend reversal
    public function sticksandwichTrendReversal()
    {
        return trader_cdlsticksandwich(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    public function takuri()
    {
        return trader_cdltakuri(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // continuation of the current downtrend.
    public function tasukigapContinuation()
    {
        return trader_cdltasukigap(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bearish
    public function thrustingBearish()
    {
        return trader_cdlthrusting(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // possible reversal in the current trend
    public function tristarTrendReversal()
    {
        return trader_cdltristar(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // a bullish reversal
    public function unique3riverBullish()
    {
        return trader_cdlunique3river(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    // bullish trend ends
    public function upsidegap2crowsBearish()
    {
        return trader_cdlupsidegap2crows(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }

    public function xsidegap3methods()
    {
        return trader_cdlxsidegap3methods(
            $this->priceSeries['open'],
            $this->priceSeries['high'],
            $this->priceSeries['low'],
            $this->priceSeries['close']
        );
    }
}
