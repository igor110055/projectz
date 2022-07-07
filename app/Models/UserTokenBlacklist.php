<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTokenBlacklist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'list',
        'user_id'
    ];

     /**
     * The attributes will type casting to original types
     *
     * @var array
     */
    protected $cast = [
        'list' => 'array'
    ];

    /**
     * The attributes that actually date datetype.
     *
     * @var array
     */
    protected $dates = [
        'created_at','updated_at'
    ];

    /**
     * format json output and serv it by a new attribute
     *
     * @return void
     */
    public function getListItemAttribute()
    {
        return json_decode($this->list);
    }

    /**
     * Get the user that owns the UserTokenBlacklist
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
