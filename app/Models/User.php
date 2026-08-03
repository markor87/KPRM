<?php

namespace App\Models;

use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, Impersonate;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
        'organ_id',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    /**
     * Relacija sa sifarnik_organi tabelom
     */
    public function organ()
    {
        return $this->belongsTo(SifarnikOrgani::class, 'organ_id');
    }

    /**
     * Provera da li je korisnik Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Само Super Admin може да импресонира (уђе у улогу другог корисника).
     */
    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * У Super Admina се не сме улазити (ни у самог себе — то lab404 већ спречава).
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->isSuperAdmin();
    }

    /**
     * Determine if the user can access the Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // All authenticated users can access the admin panel
        // Permission checking is handled by Filament Shield policies
        return true;
    }

    /**
     * Activity log konfiguracija
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'organ_id', 'is_super_admin'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Kreiran novi korisnik',
                'updated' => 'Ažuriran korisnik',
                'deleted' => 'Obrisan korisnik',
                default => "Korisnik {$eventName}",
            })
            ->useLogName('users');
    }

    /**
     * Tap into activity before logging to add IP address
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->ip_address = request()->ip();
    }
}
