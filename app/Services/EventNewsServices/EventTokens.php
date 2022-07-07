<?php

namespace App\Services\EventNewsServices;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Models\Events\EventToken as EventTokenModel;

class EventTokens {

    public function __construct()
    {
        $client = new EventTokens('GET','coins');
        $this->tokens = $client->request();
    }

    public function getRemote()
    {
        return $this->tokens;
    }

    public function checkDiff()
    {
        foreach($this->tokens as $token){
            $remoteCategories[] = $token['name'];
        }

        foreach(EventTokenModel::get() as $token){
            $localCategories[] = $token->name;
        }

        $missingElements = Arr::flatten(array_diff($remoteCategories, $localCategories));

        if(count($missingElements) > 0){
            for($i = 0; $i < count($missingElements); $i++){

                $remote = collect($this->tokens)->where('name',$missingElements[$i])->first();

                try {
                    EventTokenModel::create([
                        'remoteId' => $remote['id'],
                        'name'     => $remote['name'],
                        'rank'     => $remote['rank'],
                        'symbol'   => $remote['symbol'],
                        'fullname' => $remote['fullname']
                    ]);
                    print_r("Added :".$missingElements[$i]."\n");
                } catch(\Illuminate\Database\QueryException $e){
                    print_r($missingElements[$i]." eklenemedi."."\n"."Reason:"."\n".$e->getMessage()."\n\n");
                }

            }
        };
    }

    public function storeRemote()
    {
        foreach($this->getRemote() as $remote){
            try {
                \App\Models\Events\EventToken::create([
                    'remoteId' => $remote['id'],
                    'name' => $remote['name'],
                    'rank' => $remote['rank'],
                    'symbol' => $remote['symbol'],
                    'fullname' => $remote['fullname'],
                ]);
            } catch(\Illuminate\Database\QueryException $e){
                Log::channel('stderr')->warning($remote['name'].' kaydı atlandı.'."\n");
                continue;
            }
        }
    }
}
