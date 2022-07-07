<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pair extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'websocket'
    ];

    protected $cast = [
        'websocket' => 'boolean'
    ];


    /**
     * Get all of the tickers for the Pair
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickers()
    {
        return $this->hasMany(Ticker::class, 'pairId', 'id');
    }

    public function getLowerNameAttribute()
    {
        return Str::lower($this->name);
    }

}
