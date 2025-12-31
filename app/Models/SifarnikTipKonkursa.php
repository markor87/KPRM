<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikTipKonkursa extends Model
{
    protected $table = 'sifarnik_tip_konkursa';

    public $timestamps = false;

    protected $fillable = [
        'tip_konkursa',
    ];
}
