<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tries extends Model
{
    protected $fillable = [
        'try',
        'first_try',
        'next_try',
        'ip'
    ];
}
