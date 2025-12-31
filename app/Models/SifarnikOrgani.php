<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikOrgani extends Model
{
    protected $table = 'sifarnik_organi';

    public $timestamps = false;

    protected $fillable = [
        'vrsta_organ_id',
        'organ',
    ];

    /**
     * Relacija sa sifarnik_vrsta_organa tabelom
     */
    public function vrstaOrgana()
    {
        return $this->belongsTo(SifarnikVrstaOrgana::class, 'vrsta_organ_id');
    }
}
