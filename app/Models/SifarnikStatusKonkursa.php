<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikStatusKonkursa extends Model
{
    protected $table = 'sifarnik_status_konkursa';

    public $timestamps = false;

    protected $fillable = [
        'status_konkursa',
    ];
}
