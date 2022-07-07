<?php

namespace App\Models\Price;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class TickerHighlights extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol',
        'price',
        'eventType',
        'eventTime',
        'priceChanging',
        'baseVolumeChanging',
        'quoteVolumeChanging',
        'pair_id',
        'created_at',
        'updated_at'
    ];

    protected $cast = [
        'eventTime'          => 'timestamp',
        'price'              => 'float',
        'priceChanging'      => 'float',
        'baseVolumeChangin'  => 'float',
        'quoteVolumeChangin' => 'float',
    ];

     /**
     * The datetime attributes
     *
     * @var array
     */
    protected $date = [
        'created_at','deleted_at'
    ];

    /**
     * The attributes that will prepend every model instances
     *
     * @var array
     */
    protected $appends = [
        'count'
    ];

    /**
     * Get pair the tickers belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pair()
    {
        return $this->belongsTo(Pair::class, 'pair_id', 'id');
    }

    /**
     * returns pair's total trade counter
     *
     * @return \App\Models\Price\TickerHighlights
     */
    public function getCountAttribute()
    {
        $arr = DB::table('ticker_highlights')->select(DB::raw('count(*) as count'))->groupBy('symbol')
            ->whereSymbol($this->symbol)->get();

        return collect($arr->first())->toArray()['count'];
    }

    /**
     * Returns top gainers or loser prices between optional or default time frame limits which in minutes.
     *
     * @param string $side
     * @param int $timeLimit*
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function bestPrices($side, $timeLimit = null)
    {
        $order = $side === 'desc' ? 'orderByDesc' : 'orderBy';
        $time = isset($timeLimit) ? \Carbon\Carbon::now()->subMinutes($timeLimit) :
                                    \Carbon\Carbon::now()->subDay();

        return TickerHighlights::$order('priceChanging')->where('created_at','>', $time)
            ->$order('created_at')->groupBy('symbol');
    }

    /**
     * Returns top gainers or loser of the volume between optional or default time frame limits which in minutes.
     *
     * @param string $side
     * @param int $timeLimit
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function bestVolumers($side, $timeLimit = null)
    {
        $order = $side === 'desc' ? 'orderByDesc' : 'orderBy';
        $time = isset($timeLimit) ? \Carbon\Carbon::now()->subMinutes($timeLimit) :
                                    \Carbon\Carbon::now()->subDay();

        return TickerHighlights::$order('baseVolumeChanging')->where('created_at','>', $time)
            ->$order('created_at')->groupBy('symbol');
    }

    /**
     * Returns most used pairs between optional or default time frame limits which in minutes.
     *
     * @param string $side
     * @param int $timeLimit
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function bestTrades($side, $timeLimit = null)
    {
        $order = $side === 'desc' ? 'orderByDesc' : 'orderBy';
        $time = isset($timeLimit) ? \Carbon\Carbon::now()->subMinutes($timeLimit) :
                                    \Carbon\Carbon::now()->subDay();

        return DB::table('ticker_highlights')->select(DB::raw('count(*) as pair_count, symbol, price,
            priceChanging,baseVolumeChanging, created_at'))->where('created_at','>', $time)
                ->groupBy('symbol')->$order('pair_count');
    }

    /**
     * returns intersection of besties by date range and element count
     * limits. optional param could use to the specify returning  fields.
     *
     * @param string $time
     * @param int $count
     * @param string $field
     * @return array
     */
    public static function intersectOfWinners($time, $count, $field = 'symbol')
    {
        $prices = TickerHighlights::bestPrices('desc',$time)->groupBy('symbol')
            ->pluck($field)->take($count)->toArray();

        $volumes = TickerHighlights::bestVolumers('desc',$time)->groupBy('symbol')
            ->pluck($field)->take($count)->toArray();

        $trades = TickerHighlights::bestTrades('desc',$time)->groupBy('symbol')
            ->pluck($field)->take($count)->toArray();

        $return = array_intersect($prices, $volumes, $trades );

        return collect($return)->sortByDesc('priceChanging');
    }

    public static function topGainers($time, $count)
    {
        $trades = TickerHighlights::bestTrades('desc',$time)->take($count)->get()->pluck('symbol')->toArray();
        $prices = TickerHighlights::bestPrices('desc',$time)->take($count)->get()->toArray();
        $volumers = TickerHighlights::bestVolumers('desc',$time)->take($count)->get()->toArray();

        $intersects = array_intersect($prices, $trades, $volumers);

        $data[] = TickerHighlights::whereIn('symbol',$intersects)->latest()->first();

        return collect($data)->sortBy('priceChanging');
    }

    // /**
    //  * Return top gainers Or Losers
    //  *
    //  * @param integer $time
    //  * @param integer $count
    //  * @return \Illuminate\Database\Eloquent\Collection
    //  */
    // public static function topGainers($time,$count)
    // {
    //     return TickerHighlights::whereIn('symbol',TickerHighlights::intersectOfWinners($time, $count))->groupBy('symbol')->get();
    // }

    /**
     * İşlem yapmaya karar verilen tokenlar kullanıcı tarafından yasaklanmışsa true döner.
     * false dönerse blacklisted item yok, işleme devam edilebilir demektir
     *
     * @param string $candidate
     * @return boolean
     */
    public static function isTokenCanTrade(string $candidate)
    {
        $tokens = extractBaseAndQuote($candidate);

        $list = app()->runningInConsole() ? \App\Models\User::first()->blacklist->listItem      :
                                \Illuminate\Support\Facades\Auth::user()->blacklist->listItem   ;

        $intersect = array_intersect(array_values($tokens), $list);

        throw_if(count($intersect) > 0,'tokenler yasak listesinde, işlem yapılamaz',$intersect);

        return true;
    }
    /**
     * Check candidate is a original resource or a "türev" of original one
     *
     * @param string $candidate
     * @return boolean
     */
    public function isOriginalCandidate(string $token)
    {
        if(str_contains($token, "%"."UP"."%") ||
            str_contains($token, "%"."DOWN"."%")
        ){
            return false;
        }
    }

}
