<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SifarnikVrstaOrgana extends Model
{
    protected $table = 'sifarnik_vrsta_organa';

    public $timestamps = false;

    protected $fillable = [
        'vrsta_organa',
    ];

    /**
     * Relacija sa sifarnik_organi tabelom
     */
    public function organi()
    {
        return $this->hasMany(SifarnikOrgani::class, 'vrsta_organ_id');
    }
}
