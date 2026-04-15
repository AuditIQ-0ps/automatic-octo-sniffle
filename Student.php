<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    protected $table='students';
    protected $fillable=['user_id','organization_id','instructor_id','phone','mobile'];
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
}
