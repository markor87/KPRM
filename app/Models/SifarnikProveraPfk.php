<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikProveraPfk extends Model
{
    protected $table = 'sifarnik_provera_pfk';

    public $timestamps = false;

    protected $fillable = [
        'provera_pfk',
    ];
}
