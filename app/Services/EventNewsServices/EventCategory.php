<?php

namespace App\Services\EventNewsServices;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\EventNewsServices\EventNews;
use \App\Models\Events\EventCategory as EventCategoryModel;

class EventCategory {

    public function __construct()
    {
        $client = new EventNews('GET','categories');
        $this->categories = $client->request();
    }

    public function getRemote()
    {
        return $this->categories;
    }

    public function checkAndSaveDiff()
    {
        foreach($this->categories as $token){
            $remoteCategories[] = $token['name'];
        }

        foreach(EventCategoryModel::get() as $token){
            $localCategory[] = $token->name;
        }

        $missingElements = Arr::flatten(array_diff($remoteCategories, $localCategory));

        if(count($missingElements) > 0){
            for($i = 0; $i < count($missingElements); $i++){
                $temp = collect($this->categories)->where('name',$missingElements[$i])->first();
                EventCategoryModel::create(['name' => $temp['name'] ]);
                print_r("Added :".$missingElements[$i]."\n");
            }
        };
    }

    public function storeRemote()
    {
        foreach($this->getRemote() as $remote){
            try{
                \App\Models\Events\EventCategory::create([
                    'name' => $remote['name']
                ]);
            } catch(\Illuminate\Database\QueryException $e){
                Log::channel('stderr')->warning($remote['name'].' kaydı atlandı.'."\n");
                continue;
            }
        }
    }
}
