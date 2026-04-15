<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamUser extends Model
{
    use HasFactory;
    protected $table='stream_users';
    protected $fillable=['peer_id','user_id','event_id','uid'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->created_at,'format-19');
    }
}
