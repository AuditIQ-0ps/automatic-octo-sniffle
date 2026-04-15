<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    use HasFactory;
    protected $table='question_types';
    protected $fillable=['type'];
    public function getJoinDateTimeAttribute()
    {
        $dateHp=new DateHelper();
        return $dateHp->dateFormat($this->created_at,'format-19');
    }
}
