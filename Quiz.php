<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   Quiz extends Model
{
    use HasFactory;

    protected $table = 'quizzes';
    protected $fillable = ['user_id', 'quiz_name'];

    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'quiz_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function results()
    {
        return $this->hasMany(QuizResult::class, 'user_instructor_id');
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
