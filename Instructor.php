<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;
    protected $table='instructors';
    protected $fillable=['user_id','organization_id','phone', 'birth_date','age','qualification','experience'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class,'organization_id');
    }

    public function getPhoneNoAttribute()
    {
        $data = $this->phone;

        if( $data &&  preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $data,  $matches ) )
        {
            $result = $matches[1] . '-' .$matches[2] . '-' . $matches[3];
            return $result;
        }
        return $data;
//        return $this->belongsTo(Organization::class,'organization_id');
    }
    public function getJoinDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->created_at,'format-19');
    }
    protected function getCreateDateAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at,'custom_1');
    }

}
