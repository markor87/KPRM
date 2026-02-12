<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikVelicinaOrgana extends Model
{
    protected $table = 'sifarnik_velicina_organa';

    public $timestamps = false;

    protected $fillable = [
        'velicina_organa',
    ];
}
