<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikZvanje extends Model
{
    protected $table = 'sifarnik_zvanje';

    public $timestamps = false;

    protected $fillable = [
        'zvanje',
    ];
}
