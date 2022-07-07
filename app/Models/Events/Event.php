<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'remoteId',
        'title',
        'coins',
        'dateEvent',
        'dateCreated',
        'categories',
        'proof',
        'source',
        'categoryIds',
        'tokenIds',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $cast = [
        'coins' => 'array',
        'categories' => 'array',
        'categoryIds' => 'array',
        'tokenIds' => 'array',
        'dateEvent' => 'datetime',
        'dateCreated' => 'datetime'
    ];

    /**
     * The attributes that date time.
     *
     * @var array
     */
    protected $dates = [
        'created_at','updated_at',
    ];

    /**
     * Get the token that owns the Event
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'categoryId', 'id');
    }

    /**
     * Get the token that owns the Event
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function token()
    {
        return $this->belongsTo(EventToken::class, 'coins->remoteId', 'id');
    }
}
