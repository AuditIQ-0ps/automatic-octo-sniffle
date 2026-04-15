<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';
    protected $fillable = ['question', 'score', 'user_id', 'quiz_category_id', 'quiz_id', 'subject_id', 'question_type_id'];

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function quizCategory()
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class, 'question_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            foreach ($user->questionOptions as $a) {
                $a->delete();
            }
        });
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
    }

}
