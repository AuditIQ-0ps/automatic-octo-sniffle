<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    protected $table='subjects';
    protected $fillable=['name'];
//    public function getJoinDateTimeAttribute()
//    {
//        $dateHp=new DateHelper();
//        return $dateHp->dateFormat($this->created_at,'format-19');
//    }
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
