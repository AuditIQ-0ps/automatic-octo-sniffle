<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $fillable = ['user_id', 'date', 'start_at', 'end_at', 'duration', 'class', 'uid','is_live'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function streamUser()
    {
        return $this->hasMany(StreamUser::class, 'event_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            foreach ($user->streamUser as $a) {
                $a->delete();
            }
        });
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->created_at,'format-19');
    }

    public function getStartDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->date." ".$this->start_at,'format-19');
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
