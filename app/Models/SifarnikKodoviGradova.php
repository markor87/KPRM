<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikKodoviGradova extends Model
{
    protected $table = 'sifarnik_kodovi_gradova';

    public $timestamps = false;

    protected $fillable = ['region', 'oblast', 'kod_grada', 'grad'];
}
