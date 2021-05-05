<?php

namespace App\Models;

use App\Traits\HasTable;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATUS_CANCELED = 0;
    const STATUS_CONCLUDED = 1;
    const STATUS_WAITING = 2;
    const STATUS_PREPARATION = 3;
    const STATUS_DELIVERY = 4;

    use HasFactory;
    use HasTable;

    protected $guarded = [];

    public function defineTable()
    {
        $this->setSortBy('created_at')
            ->addColumns(['id', 'name', 'status_text', 'created_at'], ['id', 'name', 'created_at'])
            ->addColumnName('created_at', 'Created Day')
            ->addColumnName('status_text', 'Status')
            ->addSearchFields(['id', 'name', 'created_at']);
    }

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }

    public function getStatusTextAttribute()
    {
        return [
            __('Canceled'),
            __('Concluded'),
            __('Waiting'),
            __('Preparation'),
            __('Delivery'),
        ][$this->attributes['status']];
    }

    public function scopeCurrentUser($query, $userId = null)
    {
        return $query->where('user_id', $userId ?? Auth::id());
    }

    public function scopeToday($query)
    {
        return $query->where('created_at', '>=', Carbon::today());
    }

    public function food()
    {
        return $this->hasMany(OrderFood::class)->with('extras');
    }

    public function getTotal($food)
    {
        return round($food->map(function ($foodItem) {
            $foodExtra = $foodItem->extras->map(function ($extraItem) {
                return $extraItem->quantity * (float) $extraItem->getRawOriginal('price');
            })->sum();
            return $foodItem->quantity * (float) $foodItem->getRawOriginal('price') + $foodExtra;
        })->sum(), 2);
    }

    public function getCancelableAttribute()
    {
        if ($this->status != Order::STATUS_CANCELED) {
            return false;
        }

        if ($this->status == Order::STATUS_WAITING) {
            return true;
        }

        return (new Carbon($this->created_at))->addMinutes(Setting::get('order_canceled_timeout'))->greaterThan(Carbon::now());

    }

    public function scopeIsDefault($query, $isDefault = true)
    {
        return $query->where('is_default', $isDefault);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
