<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'subscription_plans';
    protected $fillable = ['plan_name',
        'stripe_plan_id',
        'type',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'meeting_time',
        'min_participants',
        'max_participants',
        'storage',
        'plan_monthly_price',
        'plan_yearly_price',
        'trail',
        'extra'
    ];
    protected $casts = [
        'extra' => 'array'
    ];
    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
    }

    public function getPlanPriceWithIconAttribute()
    {

        return "$" . $this->plan_price;
    }
    public function plan_price_cal($qty=1)
    {

        return "$" . ($this->price*$qty);
    }

    protected function getCreateDateAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at,'custom_1');
    }
    protected function getCreateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at,'time-format-1');
    }
}
