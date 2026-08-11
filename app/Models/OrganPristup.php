<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Приступ надређеног органа (нпр. министарства) подређеном органу (управи у саставу).
 *
 * Постојање записа = право прегледа. Прекидачи додају креирање, измену и брисање, и сваки
 * од њих важи тек ако корисникова улога већ носи одговарајућу дозволу
 * (Create/Update/Delete:PodaciORadnomMestu). Улога даје ШТА, овај запис даје ГДЕ.
 */
class OrganPristup extends Model
{
    use LogsActivity;

    protected $table = 'organ_pristupi';

    protected $fillable = [
        'nadredjeni_organ_id',
        'podredjeni_organ_id',
        'moze_kreiranje',
        'moze_izmenu',
        'moze_brisanje',
    ];

    protected function casts(): array
    {
        return [
            'moze_kreiranje' => 'boolean',
            'moze_izmenu' => 'boolean',
            'moze_brisanje' => 'boolean',
        ];
    }

    public function nadredjeniOrgan(): BelongsTo
    {
        return $this->belongsTo(SifarnikOrgani::class, 'nadredjeni_organ_id');
    }

    public function podredjeniOrgan(): BelongsTo
    {
        return $this->belongsTo(SifarnikOrgani::class, 'podredjeni_organ_id');
    }

    /**
     * Свака додела и одузимање права мора да остане у трагу.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nadredjeni_organ_id',
                'podredjeni_organ_id',
                'moze_kreiranje',
                'moze_izmenu',
                'moze_brisanje',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Додељен приступ подређеном органу',
                'updated' => 'Измењен приступ подређеном органу',
                'deleted' => 'Укинут приступ подређеном органу',
                default => "Приступ органу {$eventName}",
            })
            ->useLogName('organ_pristupi');
    }

    /**
     * Tap into activity before logging to add IP address
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->ip_address = request()->ip();
    }
}
