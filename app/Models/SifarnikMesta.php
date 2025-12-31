<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikMesta extends Model
{
    protected $table = 'sifarnik_mesta';

    public $timestamps = false;

    protected $fillable = [
        'mesto',
    ];
}
