<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Price\DailyPrice;
use Illuminate\Support\Facades\Log;

/**
 * Get Message as an Array from Exceptions
 *
 * @param object $e
 * @param array $data
 * @return array
 */
if(!function_exists('getMessageFromException')){
    function getMessageFromException($e , $details){
        $arr = explode(":",$e->getMessage());
        $returned['code'] = explode(",",$arr[2])[0];
        $message = $arr[3];
        $returned['title'] = str_replace("}","",str_replace(":","",str_replace('"','',$message)));
        if(count($arr) > 4){
            $message = $arr[4];
        }
        $returned['context'] = str_replace("}","",str_replace('"','',$message));
        $returned['data'] = $details;

        return $returned;
    }
}

if(!function_exists('blacklist')){
    function blacklist(){
        return ['YOYO','UP','DOWN','NCASH','SC','CKB','STMX'];
    }
}
/**
 * Extract Base And Quote v2 function returns 'base' and
 * 'quote' aside 'symbol' according to given symbol
 *
 * @param string $pair
 * @return
 */
if(!function_exists('ebaq')){
    function ebaq($pair){
        $cprint = cprint();
        foreach(fiatTokenList() as $key => $token){
            if(Str::endsWith($pair, $token)){
                $quoteLen = strlen($token);
                $pairLen = strlen($pair);
                $baseLen = $pairLen - $quoteLen;
                $base = substr($pair,0,$baseLen);
                $quote = substr($pair,$baseLen,$pairLen);

                return ['symbol' => $pair,  'base' => $base, 'quote' => $quote ];
            }
            elseif(Str::startsWith($pair, $token)){
                $baseLen = strlen($token);
                $pairLen = strlen($pair);
                $quoteLen = $pairLen - $baseLen;
                $base = substr($pair,$quoteLen,$baseLen);
                $quote = substr($pair,0,$quoteLen);

                return ['symbol' => $pair,  'base' => $base, 'quote' => $quote ];
            }
        }
    }
}


if(!function_exists('cprint')){
    function cprint(){
        return new \League\CLImate\CLImate;
    }
}

if(!function_exists('in_blacklist')){
    function in_blacklist($token){
        $blacklist = blacklist();
        $quote = extractBaseAndQuote($token)['quote'];
        $base = extractBaseAndQuote($token)['base'];
        return in_array($base,$blacklist) || in_array($quote,$blacklist);
    }
}
/**
 * Create datetime from unix timestamp
 *
 * @param integer $data
 * @return DateTime
 *
 */
if(!function_exists('createFromTimestamp')){
    function createFromTimestamp($data){
        $carbon = new \Carbon\Carbon();
        return  $carbon->createFromTimestamp($data/1000);
    }
}

if(!function_exists('cftMs')){
    function cftMs($data){
        return \Carbon\Carbon::createFromTimestampMs($data)->format('Y-m-d H:i:s');
    }
}
if(!function_exists('cft')){
    function cft($data){
        return \Carbon\Carbon::createFromTimestampMs($data)->format('Y-m-d H:i:s');
    }
}
/**
 * Add currency to token to be pair
 *
 * @param string $token
 * @param string $currency
 * @return string
 */
if(!function_exists('addCurrency')){
    function addCurrency($token, $currency = 'USDT'){
        return "{$token}{$currency}";
    }
}

/**
 * Calculate a percentage
 *
 * @param string $new
 * @param string $old
 * @return mixed
 */
if(!function_exists('percentage')){
    function percentage($new, $old)
    { 
        return number_format(($new - $old) * 100 / $old , '2','.',',').'%';
    }
}

/**
 * Calculate a percentage
 *
 * @param string $new
 * @param string $old
 * @return mixed
 */
if(!function_exists('percen')){
    function percen($new, $old)
    { 
        if($old === 0) return 0;
        return number_format(($new - $old) * 100 / $old , '2','.',',');
    }
}

if(!function_exists('countdown')){
    function countdown($seconds){
        $now = \Carbon\Carbon::now();
        $now->diffInSeconds(\Carbon\Carbon::now()->subSeconds($seconds));
    }
}

if(!function_exists('percent'))
{
    function percent(array $data, $new, $old )
    {
        $since = now();
        // (currentEvent closePrice (c) - (5mOldEvent closePrice (c))*100.0/(5mOldEvent closePrice (c))
        $totalDiffPricePercent = number_format((($data[$new] - $data[$old]) * 100) / $data[$old] , '2','.',',');
        $totalDiffInMinutes = createFromTimestamp($data['firstTimestamp'])->diffInMinutes(createFromTimestamp($data['lastTimestamp']));
        $totalDiffInSeconds = createFromTimestamp($data['firstTimestamp'])->diffInSeconds(createFromTimestamp($data['lastTimestamp']));

        $period = $totalDiffInMinutes < 1 ? 'saniye' : 'dakika';
        $time = $totalDiffInMinutes < 1 ? $totalDiffInMinutes : $totalDiffInSeconds ;

        if( $totalDiffPricePercent > $data['published_change'] * 1.15 )
        {
            if(
                $totalDiffInMinutes < 1 && $totalDiffPricePercent > 1 ||    // 0 dakika içinde %1'den fazla büyüme yada
                $totalDiffInMinutes >= 1 && $totalDiffInMinutes < 4 && $totalDiffPricePercent > 2 || // 1 ila 4 dakika arasında ve %2den fazlaysa
                $totalDiffInMinutes >= 4 && $totalDiffPricePercent > 3  // 4 dakikadan fazla ve %3den fazlaysa büyüdüyse bildiri geç
            ){
                Log::channel("stderr")->info("{$data['symbol']} çifti için {$time} {$period} içinde ".
                "{$totalDiffPricePercent}% değişim gerçekleşti!");

                $data['published_change'] = $totalDiffPricePercent;
                $data['published_time'] = $since;
            }
        }
        elseif( $totalDiffPricePercent < $data['published_change'] * 1.15 && $totalDiffPricePercent < 0 )
        {
            if(
                $totalDiffInMinutes < 1 && $totalDiffPricePercent < -1 ||    // 0 dakika içinde %1'den fazla küçülme yada
                $totalDiffInMinutes >= 1 && $totalDiffInMinutes < 4 && $totalDiffPricePercent < -2 || // 1 ila 4 dakika arasında ve %2den fazlaysa
                $totalDiffInMinutes >= 4 && $totalDiffPricePercent < -3  // 4 dakikadan fazla ve %3den fazlaysa küçüldüyse bildiri geç
            ){
                Log::channel("stderr")->info("{$data['symbol']} çifti için {$time} {$period} içinde ".
                "{$totalDiffPricePercent}% değişim gerçekleşti!");

                $data['published_change'] = $totalDiffPricePercent;
                $data['published_time'] = $since;
            }
        }

        $data['changeClosePercent'] = $totalDiffPricePercent."%";

        return $data;
    }
}

if(!function_exists('saveOrder')){
    function saveOrder($order){
        $controller = new \App\Http\Controllers\OrderController;

        $record = $controller->store($order);

        return $record ? true : false;
    }
}


/**
 * Return fiat token currencies
 *
 * @return array
 */
if(!function_exists('fiatTokenList')){
    function fiatTokenList()
    {
        return [
            'USDT','BUSD','TUSD','USDC','USDP',"SUSD",'AUD','BIDR',
            'BRL','EUR','GBP','RUB','TRY','IDRT','UAH','NGN','VAI',
            'BVND',"PAX","BTC","PAXG","WBTC","BETH","DAI","BNB","ETH",
            'BNB','XRP'
        ];
    }
}

if(!function_exists('stableTokenList')){
    function stableTokenList()
    {
        return [
            "RENBTC","PAXG","WBTC","BETH","DAI"
        ];
    }
}

/**
 * Return if pair is a stable coin pair or not
 */
if(!function_exists('isStablePair')){
    function isStablePair($string){
        $arr = extractBaseAndQuote($string);
        return in_array(
            $arr['base'], fiatTokenList()) ||
            in_array($arr['quote'], stableTokenList()
        );
    }
}


/**
 * Return fiat token currencies
 *
 * @return array
 */
if(!function_exists('quoteTokenList')){
    function quoteTokenList()
    {
        return [
            'BTC','BNB','XRP','ETH'
        ];
    }
}

/**
 * extract base and quote tokens from a string
 *
 * @param string $symbol
 * @return array
 */
if(!function_exists('extractBaseAndQuote')){
    function extractBaseAndQuote( $symbol )
    {
        $len = strlen($symbol);
        if(in_array(substr($symbol,$len-4,$len), fiatTokenList())) {
            $quote = substr($symbol,$len-4,$len);
            $base = substr($symbol,0, $len-4);
        }
        else if(in_array(substr($symbol,$len-3,$len), fiatTokenList()) ){
            $quote = substr($symbol, $len-3, $len);
            $base = substr($symbol,0, $len-3);
        }
        else if(in_array(substr($symbol,0,3), fiatTokenList()) ){
            $quote = substr($symbol, 3, $len);
            $base = substr($symbol,0, 3);
        }
        else if(in_array(substr($symbol,0,4), fiatTokenList()) ){
            $quote = substr($symbol, 4, $len);
            $base = substr($symbol,0, 4);
        }
        else {
            $quote = null; $base = null;
        }

        try {
            return ['symbol' => $symbol, 'quote' => $quote, 'base' => $base ];
        } catch (\Exception $e) {
            $this->print->redBackground("{$symbol} has not price on the price list");
        }

    }
}

/**
 * Get Directory Contents
 *
 * @param string $content
 * @return array
 */
if(!function_exists('getDirFiles')){
    function getDirFiles($directory)
    {
        return scandir($directory);
    }
}

/**
 * Open zip file at given location and extract it requested location
 *
 * @param string $input
 * @param string $output
 * @return void
 */
if(!function_exists('extractZip')){
    function extractZip($input, $output){
        $zip = new ZipArchive;
        if($zip->open($input) === TRUE){
            $zip->extractTo($output);
            $zip->close();
        }
    }
}

/**
 * Read Csv file's content
 *
 * @param string $file
 * @return array
 */
if(!function_exists('readCsv')){
    function readCsv($file){
        $data = file($file);
        foreach($data as $key => $line){
            $lineArr = explode(',',$line);
            $return[] = [
                'price' => $lineArr['4'],
                'timestamp' => $lineArr['6']
            ];
        }
        return $return;
    }
}

/**
 * return token name as logo resource format
 *
 * @param $token
 * @return string
 */
if(!function_exists('tokenForLogo')){
    function tokenForLogo($token){
        if(str_contains( $token, 'UP') || str_contains($token, 'DOWN'))
        {
            $token = str_replace(['UP','DOWN'],['',''],$token);
        }

        return Str::upper($token).'.png';
    }
}

/**
 *  return timestamp in milliseconds
 *
 * @param $day string|null
 * @param $daily bool
 *
 * @return string
  */
if(!function_exists('timeMill')){
    function timeMill($day = null, $daily = true){
        if($daily){
            return is_null($day) ? now()->timestamp * 1000 : now()->subDays($day)->timestamp * 1000;
        }
        else {
            return is_null($day) ? now()->timestamp * 1000 : now()->subHours($day)->timestamp * 1000;
        }
    }
}

/**
 * return a price seris array from given collection
 * @param $collection
 * @param $all boolean true for all price fields
 *
 * @return array
 */
if(!function_exists('priceSeries')){
    function priceSeries($collection, $all = false){
        $data = [];
        foreach($collection as $single){

            if(!isset($single->closedTime)){
                $single->timestamp = $single->closedTime;
            }

            if(gettype($single->timestamp) == 'string'){
                $timestamp = \Carbon\Carbon::create($single->timestamp)->timestamp;
            } else {
                $timestamp = $single->timestamp->timestamp;
            }

            $data['close'][$timestamp]  = $single->close;

            if($all){
                $data['open'][$timestamp]   = $single->open;
                $data['high'][$timestamp]   = $single->high;
                $data['low'][$timestamp]    = $single->low;
                $data['volume'][$timestamp] = $single->volume;
            }

        }
        return $data;
    }
}

if(!function_exists('series')){
    function series($data, $array = true){
        $series = [];
        foreach($data as $key => $value){
            $series['open'][]   = $value['open'];
            $series['high'][]   = $value['high'];
            $series['low'][]    = $value['low'];
            $series['close'][]  = $value['close'];
            $series['volume'][] = $value['baseVolume'];
        }
        return $series;
    }
}

if(!function_exists('defineModelAndOrder')){
    function defineModelAndOrder($timeframe)
    {
        switch ($timeframe) {
            case 'days':
                $model = '\App\Models\Price\DailyPrice';
                $order = 'timestamp';
                break;

            case 'hours':
                $model = '\App\Models\Price\HourlyPrice';
                $order = 'timestamp';
                break;

            case 'current':
                $model = '\App\Models\Price\CurrentPrice';
                $order = 'created_at';
                break;

            default:
                $model = '\App\Models\Price\HourlyPrice';
                $order = 'timestamp';
                break;
        }

        return [$model, $order];
    }
}

if(!function_exists('arrayAvarage')){
    function arrayAvarage($series, $period)
    {
        return array_sum($series) / $period;
    }
}

if(!function_exists('loader')){
    function loader($seconds){
        $cprint = new \League\CLImate\CLImate;
        for($i = 0; $i < $seconds; $i++){
            $cprint->inline(".");
            sleep(1);
        }
    }
}

/**
    * işlem çifti için exchange info'da tanımlanan kurallar
    * doğrultusunda işlem lotu hesaplanır
    *
    * @param string $key
    * @param array $data
    * @return array
*/
if(!function_exists('quantity_fix')){
    function quantity_fix($symbol, $quantity)
    {
        $step_size = \App\Models\ExchangeInfo::whereSymbol($symbol)->first()->filters[2]['stepSize'];

        $cmd = buildCmd($quantity, $step_size);

        $output =  shell_exec('python '.$cmd);

        return $output;
    }
}

if(!function_exists('buildCmd')){
    function buildCmd($q ,$s)
    {
        return app_path().'/Services/ChartServices/round.py -q '.$q.' -s '.$s;
    }
}

if(!function_exists('nb')){
    function nb($number, $limit = 8)
    {
        return number_format($number, $limit, '.', '');
    }
}

if(!function_exists('quantityFormatter')){
    function quantityFormatter($symbol, $quantity)
    {
        $stepSize = \App\Models\ExchangeInfo::where('symbol',$symbol)
            ->first()->filters[2]['stepSize'];

        return nb($quantity - fmod($quantity, $stepSize));
    }
}

if(!function_exists('parseForDiffInMin')){
    function parseForDiffInMin($new ,$old, $seconds = false)
    {
        return $seconds ? 
            \Carbon\Carbon::parse($new)->diffInSeconds(\Carbon\Carbon::parse($old)):
            \Carbon\Carbon::parse($new)->diffInMinutes(\Carbon\Carbon::parse($old));
    }
}

if(!function_exists('priceFormatter')){
    function priceFormatter($symbol, $price)
    {
        $filters = \App\Models\ExchangeInfo::where('symbol',$symbol)->first()->filters;
        $npos = strpos($filters[0]['tickSize'],1) - 1;
        return nb($price,$npos);
    }
}

if(!function_exists('endsWithListItem')){
    function endsWithListItem($string, $list)
    {
        foreach($list as $item)
        {
            if(Str::endsWith($string, $item))
            {
                return true;
            }
        }

        return false;
    }
}