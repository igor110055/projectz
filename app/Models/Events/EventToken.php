<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Model;

class EventToken extends Model
{
    protected $fillable = [
        'remoteId',
        'name',
        'rank',
        'symbol',
        'fullname',
    ];

    /**
     * Get all of the events for the Event
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'tokenId', 'id');
    }
}
