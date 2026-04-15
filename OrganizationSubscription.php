<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationSubscription extends Model
{
    use HasFactory;

    protected $table = 'organization_subscriptions';
    protected $fillable = [
        'subscription_plan_id',
        'organization_id',
        'status',
        'start_time',
        'end_time',
        'transaction_id',
        'transaction_status',
        'session_id',
        'plan_name',
        'plan_price',
        'plan_months',
        'plan_stripe_id',
        'plan_stripe_price_id',
        'email',
        'stripe_subscriptions_id'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 2);
    }

    public function scopeSuccess($q)
    {
        return $q->where('transaction_status', 2);
    }

    public function getTransactionStatusTagAttribute()
    {
//        0 : fail | 1 : pending | 2 : success
        $status = '<span class="badge text-white badge-info">Pending</span>';
        if ($this->transaction_status == 2) {
            $status = '<span class="badge text-white badge-success">Success</span>';
        } elseif ($this->transaction_status == 0) {
            $status = '<span class="badge text-white badge-danger">Fail</span>';
        }
        return $status;
    }

    public function getDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-14');
//        return $dateHp->dateFormat($this->created_at,'format-14');
    }

    public function getPlanPriceWithIconAttribute()
    {

        return "$" . $this->plan_price;
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
    }

    public function getStartDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->start_time, 'custom_1');
    }

    public function getEndDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->end_time, 'custom_1');
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
