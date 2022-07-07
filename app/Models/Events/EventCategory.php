<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    protected $fillable = [
        'name'
    ];

    /**
     * Get all of the events for the EventCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'categoryId', 'id');
    }
}
