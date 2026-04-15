<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    use HasFactory;
    protected $table='quiz_results';
    protected $fillable=['user_id','user_instructor_id','total_score','achieve_score','date'];
    public function getJoinDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->created_at,'format-19');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
