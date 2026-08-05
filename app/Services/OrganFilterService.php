<?php

namespace App\Services;

use App\Models\PodaciORadnomMestu;
use App\Models\SifarnikOrgani;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrganFilterService
{
    /**
     * Дозвола која омогућава избор органа на контролној табли (падајућа листа изнад
     * графикона). Дефинисана је као custom Shield дозвола у config/filament-shield.php,
     * па се додељује по улогама у „Улоге → измени → Остало".
     */
    public const PERMISSION_IZBOR_ORGANA = 'IzborOrganaNaKontrolnojTabli';

    /**
     * Кеш листе органа за трајање захтева — контролну таблу чини 11 виџета, а сваки
     * посебно тражи сервис, па без овога иста упита иде 11 пута.
     *
     * @var array<int, string>|null
     */
    private static ?array $organiCache = null;

    /**
     * Apply organ-based filtering to a query
     *
     * @param Builder $query
     * @param string $organColumn The column name for organ (default: 'organ')
     * @return Builder
     */
    public function applyOrganFilter(Builder $query, string $organColumn = 'organ'): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Korisnici sa ViewAny:PodaciORadnomMestu dozvolom vide sve
        if ($user->can('ViewAny:PodaciORadnomMestu')) {
            return $query;
        }

        // Filtriraj po organ_id korisnika
        if ($user->organ_id) {
            return $query->where($organColumn, $user->organ_id);
        }

        // Korisnici bez organa ne vide ništa
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply organ-based filtering for charts (always filters by user's organ, even for super admin)
     *
     * Ако корисник има дозволу за избор органа и изабрао је орган на контролној табли,
     * тај избор има предност над сопственим органом корисника.
     *
     * @param Builder $query
     * @param string $organColumn The column name for organ (default: 'organ')
     * @param int|null $izabraniOrgan Орган изабран у падајућој листи на контролној табли
     * @return Builder
     */
    public function applyOrganFilterForCharts(Builder $query, string $organColumn = 'organ', ?int $izabraniOrgan = null): Builder
    {
        $organId = $this->resolveChartOrganId($izabraniOrgan);

        if ($organId === null) {
            // Нема ни изабраног ни сопственог органа — нема ни података
            return $query->whereRaw('1 = 0');
        }

        return $query->where($organColumn, $organId);
    }

    /**
     * Да ли текући корисник сме да бира орган на контролној табли.
     */
    public function canSelectOrgan(): bool
    {
        return (bool) Auth::user()?->can(self::PERMISSION_IZBOR_ORGANA);
    }

    /**
     * Орган по коме се филтрирају графикони: изабрани (ако корисник сме и ако је избор
     * валидан) или сопствени орган корисника.
     */
    public function resolveChartOrganId(?int $izabraniOrgan = null): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($izabraniOrgan !== null
            && $this->canSelectOrgan()
            && array_key_exists($izabraniOrgan, $this->getSelectableOrgani())) {
            return $izabraniOrgan;
        }

        return $user->organ_id ?: null;
    }

    /**
     * Органи понуђени у падајућој листи — само они који уопште имају унете податке,
     * плус сопствени орган корисника (да избор увек има одговарајућу ставку).
     *
     * @return array<int, string> [id => назив органа]
     */
    public function getSelectableOrgani(): array
    {
        if (self::$organiCache !== null) {
            return self::$organiCache;
        }

        $sopstveniOrgan = Auth::user()?->organ_id;

        $organi = SifarnikOrgani::query()
            ->where(function (Builder $query) use ($sopstveniOrgan): void {
                $query->whereIn(
                    'id',
                    PodaciORadnomMestu::query()->select('organ')->whereNotNull('organ')
                );

                if ($sopstveniOrgan) {
                    $query->orWhere('id', $sopstveniOrgan);
                }
            })
            ->orderBy('organ')
            ->pluck('organ', 'id')
            ->map(fn ($naziv): string => (string) $naziv)
            ->all();

        return self::$organiCache = $organi;
    }

    /**
     * Check if current user can view all data (bypasses organ filtering)
     *
     * @return bool
     */
    public function canViewAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->can('ViewAny:PodaciORadnomMestu');
    }

    /**
     * Get the current user's organ ID
     *
     * @return int|null
     */
    public function getUserOrganId(): ?int
    {
        return Auth::user()?->organ_id;
    }

    /**
     * Check if user should see empty results
     *
     * @return bool
     */
    public function shouldShowEmptyResults(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return true;
        }

        return !$this->canViewAllData() && !$user->organ_id;
    }
}
