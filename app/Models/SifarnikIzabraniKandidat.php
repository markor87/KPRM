<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikIzabraniKandidat extends Model
{
    protected $table = 'sifarnik_izabrani_kandidat';

    public $timestamps = false;

    protected $fillable = [
        'izabrani_kandidat',
    ];
}
