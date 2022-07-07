<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeInfo extends Model
{
    public static $timezone = 'UTC';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol',
        'status',
        'baseAsset',
        'baseAssetPrecision',
        'quoteAsset',
        'quotePrecision',
        'quoteAssetPrecision',
        'baseCommissionPrecision',
        'quoteCommissionPrecision',
        'orderTypes',
        'icebergAllowed',
        'ocoAllowed',
        'quoteOrderQtyMarketAllowed',
        'isSpotTradingAllowed',
        'isMarginTradingAllowed',
        'filters',
        'permissions',
        'exchange',
    ];

    /**
     * The attributes will type casting
     *
     * @var array
     */
    protected $casts = [
        'orderTypes' => 'array',
        'filters' => 'array',
        'permissions' => 'array',
    ];

    /**
     * The attributes actually datetime
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    /**
     * Extract pair's only base name
     *
     * @return void
     */
    public function getTokenAttribute()
    {
        return extractBaseAndQuote($this->symbol)['base'];
    }

    /**
     * Return all pairs
     *
     * @return array
     */
    public static function getPairList()
    {
        return ExchangeInfo::where('status', '<>', 'BREAK')->groupBy('symbol')
            ->orderBy('symbol')->get('symbol')->pluck('symbol');
    }

    /**
     * Return all pair ends with usdt
     * 
     * @return array
     */
    public static function getUsdtPairs()
    {
        return ExchangeInfo::where('symbol','like','%'.'USDT')
                 ->where('status','TRADING')->where('symbol','not like','%'.'UP'.'%')
                 ->where('symbol','not like','%'.'DOWN'.'%')->pluck('symbol')
                 ->toArray();
    }

    /**
     * Return all tokens
     *
     * @return array
     */
    public static function getTokenList()
    {
        $list = [];
        foreach (ExchangeInfo::getPairlist() as $symbol) {
            if (!in_array($symbol->token, $list)) {
                array_push($list, $symbol->token);
            }
        }
        return $list;
    }

    public static function getMarginList()
    {
        return ExchangeInfo::where('status', '<>', 'BREAK')->whereJsonContains('permissions', 'MARGIN')
            ->groupBy('symbol')->orderBy('symbol')->get('symbol')->pluck('symbol');
    }
    /**
     * Return all tokens with their logo
     *
     * @return array
     */
    public static function getTokensWithLogo()
    {
        $list = [];
        $base_path = '/images/cryptoLogos/';
        $base_url = env('APP_URL') . $base_path;
        $archive = scandir(public_path($base_path));

        foreach (ExchangeInfo::getTokenList() as $symbol) {
            $token = tokenForLogo($symbol);
            $endpath = array_search($token, $archive) ? $token : 'default.png';
            array_push($list, ['token' => $symbol, 'logo' => $base_url . $endpath]);
        }

        return $list;
    }
}
