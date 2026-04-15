<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tracker extends Model
{
    use HasFactory;
    protected $table='trackers';
    protected $fillable=[
        'name'
    ];
}
