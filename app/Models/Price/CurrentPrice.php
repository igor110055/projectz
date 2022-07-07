<?php

namespace App\Models\Price;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol','price','timestamp','pair_id'
    ];

    protected $dates = [
        'created_at','updated_at'
    ];
}
