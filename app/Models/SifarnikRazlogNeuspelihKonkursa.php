<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikRazlogNeuspelihKonkursa extends Model
{
    protected $table = 'sifarnik_razlog_neuspelih_konkursa';

    public $timestamps = false;

    protected $fillable = [
        'razlog',
    ];
}
