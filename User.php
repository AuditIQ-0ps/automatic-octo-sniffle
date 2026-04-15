<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'last_login_at',
        'last_login_ip',
        'status'
    ];

    protected $appends = ['profile', 'name'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->hasOne(Organization::class, 'user_id');
    }

    public function instructor()
    {
        return $this->hasOne(Instructor::class, 'user_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getUserIDAttribute()
    {
        return $this->id;
    }

    public function getJoinDateTimeAttribute()
    {
        $dateHp = new DateHelper();
        return $dateHp->dateFormat($this->created_at, 'format-19');
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

    public function sendPasswordResetNotification($token)
    {

        $data = [
            'type' => 'reset',
            'token' => $token,
            'email' => $this->email,
            'name' => $this->first_name,
            'url' => \Illuminate\Support\Facades\URL::to('password/reset', $token)
        ];
//        dd($data);
        dispatch(new \App\Jobs\SendEmailJob($data));
//        $this->notify(new ResetPasswordNotification($token));
//        dd(11);
    }

    public function getHomeRouteAttribute()
    {

        $route = '';
        switch ($this->role_id) {
            case 1:
                $route = "admin.home";
                break;
            case 2:
                $route = "organization.home";
                break;
            case 3:
                $route = "instructor.home";
                break;
            case 4:
                $route = "student.home";
                break;
        }
        return $route;

    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
//            dd(1111);
            $user->organization->delete();
            $user->instructor->delete();
            $user->student->delete();
        });
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function getRole()
    {
        switch ($this->role_id) {
            case 1:
                $route = "admin";
                break;
            case 2:
                $route = "organization";
                break;
            case 3:
                $route = "instructors";
                break;
            case 4:
                $route = "students";
                break;
        }
        return $route;

    }

    public function getProfileAttribute()
    {
        return $this->photo ? url($this->photo) : url('public/blank_user.png');
    }
}
