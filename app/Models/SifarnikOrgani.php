<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SifarnikOrgani extends Model
{
    protected $table = 'sifarnik_organi';

    public $timestamps = false;

    protected $fillable = [
        'vrsta_organ_id',
        'nadredjeni_organ_id',
        'organ',
    ];

    /**
     * Relacija sa sifarnik_vrsta_organa tabelom
     */
    public function vrstaOrgana()
    {
        return $this->belongsTo(SifarnikVrstaOrgana::class, 'vrsta_organ_id');
    }

    /**
     * Орган у чијем је саставу овај орган (нпр. министарство изнад управе у саставу).
     */
    public function nadredjeniOrgan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'nadredjeni_organ_id');
    }

    /**
     * Органи у саставу овог органа. Само хијерархија — не подразумева никакав приступ.
     */
    public function podredjeniOrgani(): HasMany
    {
        return $this->hasMany(self::class, 'nadredjeni_organ_id');
    }

    /**
     * Изричито додељена права овог органа над подређеним органима.
     */
    public function organPristupi(): HasMany
    {
        return $this->hasMany(OrganPristup::class, 'nadredjeni_organ_id');
    }

    public function korisnici(): HasMany
    {
        return $this->hasMany(User::class, 'organ_id');
    }

    public function radnaMesta(): HasMany
    {
        return $this->hasMany(PodaciORadnomMestu::class, 'organ');
    }
}
