<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Organization extends Model
{
    use HasFactory;

    protected $table = 'organizations';
    protected $fillable = ['name', 'user_id', 'phone', 'mobile', 'description', 'price', 'no_of_instructor',
//        'withdrawal_date',
        'payment_style_id', 'no_of_student',
        'city',
        'state',
        'zip_code',
        'country',
//        'build_year'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentStyle()
    {
        return $this->belongsTo(PaymentStyle::class, 'payment_style_id');
    }

    public function instructors()
    {
        return $this->hasMany(Instructor::class, 'organization_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'organization_id');
    }

    public function subscription()
    {
        return $this->hasOne(OrganizationSubscription::class, 'organization_id')->where('transaction_status', 2)->where('status', 2);
    }

    public function subscriptions()
    {
        return $this->hasMany(OrganizationSubscription::class, 'organization_id');
    }

    public function status(){
        return $this->hasone(OrganizationSubscription::class,'organization_id')->where('status','!=',null);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
//
//            dd($user->instructors->toarray());
            foreach ($user->instructors as $a) {
//                dd($a);
                $a->delete();
            }
//            dd($user->instructors->toarray());
            foreach ($user->subscriptions as $a) {
                $a->delete();
            }
            foreach ($user->students as $a) {
                $a->delete();
            }
        });
    }

    public function getPhoneNoAttribute()
    {
        $data = $this->phone;

        if ($data && preg_match('/^(\d{3})(\d{3})(\d{4})$/', $data, $matches)) {
            $result = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            return $result;
        }
        return $data;
//        return $this->belongsTo(Organization::class,'organization_id');
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
    }

    public function getWithdrawalDateAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-6');
    }

}
