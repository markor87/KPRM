<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikOblastRada extends Model
{
    public $timestamps = false;

    protected $table = 'sifarnik_oblast_rada';

    protected $fillable = ['oblast_rada'];
}
