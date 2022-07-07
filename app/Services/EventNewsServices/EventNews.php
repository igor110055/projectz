<?php

namespace App\Services\EventNewsServices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use App\Models\Events\Event as EventModel;
use App\Services\EventNewsServices\EventNews as EventNewsServices;

class EventNews {

    public $client;

    public function __construct($method = 'GET', $endpoint = 'events', $page = null)
    {
        if(is_null($page)){
            $this->client = Http::withHeaders([
                'Accept-Encoding' => 'deflate, gzip',
                'x-api-key'       => env('COINMARKETCAL')
            ])->acceptJson()->$method("https://developers.coinmarketcal.com/v1/{$endpoint}")->json();
        } else {
            $this->client = Http::withHeaders([
                'Accept-Encoding' => 'deflate, gzip',
                'x-api-key'       => env('COINMARKETCAL')
            ])->acceptJson()->$method("https://developers.coinmarketcal.com/v1/{$endpoint}?page={$page}")->json();
        }

        $this->events = $this->request();
    }

    public function request()
    {
        return $this->client['body'];
    }

    public function checkDiff()
    {
        foreach($this->events as $token){
            $remoteEvents[] = $token['name'];
        }

        foreach(EventModel::get() as $token){
            $localEvents[] = $token->name;
        }

        $missingElements = Arr::flatten(array_diff($remoteEvents, $localEvents));

        if(count($missingElements) > 0){
            for($i = 0; $i < count($missingElements); $i++){

                $remote = collect($this->events)->where('name',$missingElements[$i])
                    ->first();

                try {

                    foreach($remote['coins'] as $coin){
                        $remoteCoinIds[] = \App\Models\Events\Event::whereRemoteId($coin['id'])
                            ->first()->id;
                    }

                    foreach($remote['categories'] as $category){
                        $categoryIds[] = \App\Models\Events\EventCategory::whereName($category['name'])
                            ->first()->id;
                    }

                    EventModel::create([
                        'title'       => $remote['title'],
                        'coins'       => $remote['coins'],
                        'dateEvent'   => $remote['date_event'],
                        'dateCreated' => $remote['date_created'],
                        'categories'  => $remote['categories'],
                        'proof'       => $remote['proof'],
                        'source'      => $remote['source'],
                        'categoryIds' => $categoryIds,
                        'tokenIds'    => $remoteCoinIds
                    ]);

                    print_r("Added :".$missingElements[$i]."\n");
                } catch(\Illuminate\Database\QueryException $e){
                    print_r($missingElements[$i]." eklenemedi."."\n"."Reason:"."\n".$e->getMessage()."\n\n");
                }
            }
        }
    }

    /**
     * Store remote data to the local storage
     *
     * @return void
     */
    public function storeRemote()
    {
        $events = new EventNewsServices('GET','events');        // get remote data
        $page = $events->client['_metadata']['page_count']; // get data pagination's last page

        $pageData[] = $events->client['body'];
        // ? visit every page of the paginations to collect all the events on that pages
        if($page > 0){
            for($i = 1; $i < $page; $i++){
                echo($i.". sayfa..");
                sleep(3);
                $events = new EventNewsServices('GET','events',$i);
                array_push($pageData, $events->client['body']);
                echo($i.". sayfa tamam."."\n");
                sleep(1);
            }
        }

        foreach($pageData as $page){
            foreach($page as $remote){
                try {
                    if(isset($remote['coins'])){
                        foreach($remote['coins'] as $coin){
                            $remoteCoinIds[$remote['id']][] = \App\Models\Events\EventToken::where('remoteId',$coin['id'])
                                ->first()->id;
                        }
                    }

                    if(isset($remote['categories'])){
                        foreach($remote['categories'] as $category){
                            $categoryIds[$remote['id']][] = \App\Models\Events\EventCategory::whereName($category['name'])
                                ->first()->id;
                        }
                    }

                    \App\Models\Events\Event::create([
                        'remoteId'    => $remote['id'],
                        'title'       => $remote['title']['en'],
                        'coins'       => json_encode($remote['coins']),
                        'dateEvent'   => $remote['date_event'],
                        'dateCreated' => $remote['created_date'],
                        'categories'  => isset($remote['categories']) ?
                            json_encode($remote['categories']) : null,
                        'proof'       => $remote['proof'],
                        'source'      => $remote['source'],
                        'categoryIds' => json_encode(['id' => $categoryIds[$remote['id']] ]),
                        'tokenIds'    => json_encode(['id' => $remoteCoinIds[$remote['id']]]),
                    ]);

                    echo("{$remote['title']['en']} etkinliği işlendi"."\n");
                }
                catch(\Illuminate\Database\QueryException $e){
                    Log::channel('stderr')->warning($remote['title']['en'].' kaydı atlandı.'."\n");
                    print_r( 'REASON:'."\n".$e->getMessage() );
                    continue;
                }
            }
        }
    }
}
