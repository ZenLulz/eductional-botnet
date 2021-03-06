<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

use Config;

use App\Attack;

class Bot extends Model
{
  /**
  * The attributes that are mass assignable.
  *
  * @var array
  */
  protected $fillable = ['mac_address', 'name', 'cpu', 'operating_system', 'state'];

  /**
  * return max time before the bot is considered disconncted
  *
  * @return Ingterger
  */
  public static function maxSecondsBeforeDisconnect()
  {
    return 5 * Config::get('settings.seconds_between_beats');
  }

  /**
  * reset bots status if their considered disconnected
  */
  public static function resetBotsConnection()
  {
    Bot::where(
      'connected_at',
      '<',
      Carbon::now()->subSeconds(Bot::maxSecondsBeforeDisconnect())->toDateTimeString()
    )
    ->currentlyConnected()
    ->update(['state' => 'disconnected', 'attack_id' => null]);
  }

  /**
  * Scope a query to get only currently connected bots.
  *
  * @param \Illuminate\Database\Eloquent\Builder $query
  * @return \Illuminate\Database\Eloquent\Builder
  */
  public function scopeCurrentlyConnected($query)
  {
    return $query->where('state', 'connected')->orWhere('state', 'attacking');
  }

  /**
  * Scope a query to get only idle bots.
  *
  * @param \Illuminate\Database\Eloquent\Builder $query
  * @return \Illuminate\Database\Eloquent\Builder
  */
  public function scopeIdle($query)
  {
    return $query->ofState('connected')->where('attack_id', null);
  }

  /**
  * Scope a query to get only attacking bots.
  *
  * @param \Illuminate\Database\Eloquent\Builder $query
  * @return \Illuminate\Database\Eloquent\Builder
  */
  public function scopeAttacking($query)
  {
    return $query->ofState('attacking');
  }

  /**
  * Scope a query to get only disconnected bots.
  *
  * @param \Illuminate\Database\Eloquent\Builder $query
  * @return \Illuminate\Database\Eloquent\Builder
  */
  public function scopeDisconnected($query)
  {
    return $query->ofState(['disconnected']);
  }

  /**
  * Scope a query to get only bots with states.
  *
  * @param \Illuminate\Database\Eloquent\Builder $query
  * @param mixed $state
  * @return \Illuminate\Database\Eloquent\Builder
  */
  public function scopeOfState($query, $state)
  {
    return $query->where('state', $state);
  }

  /**
  * Get the attack that owns the bot.
  *
  * @return App\Attack;
  */
  public function attack()
  {
    return $this->belongsTo('App\Attack', 'attack_id');
  }

  /**
  * true if we need iformation form bot.
  *
  * @return Boolean
  */
  public function needInformation()
  {
    return (!$this->name);
  }

  /**
  * check if bot needs to be said start attack
  *
  * @return Boolean
  */
  public function needAttackStart()
  {
    return ($this->state == 'connected' and $this->attack()->count());
  }

  /**
  * check if bot needs to be said start attack
  *
  * @return Boolean
  */
  public function needAttackEnd()
  {
    return ($this->state == 'attacking' and $this->attack()->count() and $this->attack()->first()->finish);
  }
}
