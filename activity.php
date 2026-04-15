<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class activity extends Model
{
    use HasFactory;
    protected $table = 'activities';
    protected $fillable = [
        'userId',
        'date',
        'login',
        'logout',
        'total_time',
        'activity',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
    public function setActivitiesAttribute($value)
    {
        $activities = [];

        foreach ($value as $array_item) {
            if (!is_null($array_item['key'])) {
                $activities[] = $array_item;
            }
        }

        $this->attributes['activities'] = json_encode($activities);
    }
    protected $casts = [
        'activity' => 'array'
    ];
    protected function getLoginDateAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at,'custom_1');
    }
    protected function getLoginTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at,'time-format-1');
    }
}
