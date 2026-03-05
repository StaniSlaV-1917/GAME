<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fullname',
        'position',
        'birth_date',
        'contact',
    ];
}
