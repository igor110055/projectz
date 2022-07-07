<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get all of the exchange for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exchange()
    {
        return $this->hasMany(Exchange::class, 'user_id', 'id');
    }

    /**
     * Get all of the exchange for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'userId', 'id');
    }

    /**
     * Get all balances for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balances()
    {
        return $this->hasMany(Balance::class, 'userId', 'id');
    }

    /**
     * Get all of the Blacklist for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function blacklist()
    {
        return $this->hasOne(UserTokenBlacklist::class, 'user_id', 'id');
    }

    /**
     * Get User's preferences
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function preference()
    {
        return $this->hasOne(UserPreference::class, 'user_id','id');
    }

    /**
     * If not set any preference Return default value
     *
     * @return string
     */
    public function getQuotePreferenceAttribute()
    {
        return is_null($this->preference)  ? 'USDT' : $this->preference->quoteUnit;
    }

    /**
     * return user's wallet total amount
     *
     * @return void
     */
    public function getTotalBalancesAttribute()
    {
        $pnl = 0;
        foreach($this->balances as $balance){
            if(!in_array($balance->token, fiatTokenList())){
                $portfolio['tokens'][$balance->token] = $balance->total_revenue;
                $pnl += floatval($balance->total_revenue);
            }
        }

        $portfolio['TOTAL'] = number_format($pnl,'2','.',',');

        return $portfolio;
    }
}
