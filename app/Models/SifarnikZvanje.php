<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SifarnikOrgani;

class SifarnikZvanje extends Model
{
    protected $table = 'sifarnik_zvanje';

    public $timestamps = false;

    protected $fillable = [
        'zvanje',
        'organ_id',
    ];

    public function organ()
    {
        return $this->belongsTo(SifarnikOrgani::class, 'organ_id');
    }
}
