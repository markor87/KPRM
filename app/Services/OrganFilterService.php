<?php

namespace App\Services;

use App\Models\OrganPristup;
use App\Models\PodaciORadnomMestu;
use App\Models\SifarnikOrgani;
use Illuminate\Contracts\Auth\Authenticatable;
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
     * Права текућег корисника по органима, кеширано за трајање захтева.
     *
     * @var array<int, array<int, array{kreiranje: bool, izmena: bool, brisanje: bool}>>
     */
    private static array $pristupiCache = [];

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

        $organi = $this->dostupniOrganiIds();

        // Korisnici bez organa ne vide ništa
        if ($organi === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($organColumn, $organi);
    }

    /**
     * Органи у којима корисник сме да ради: сопствени орган плус подређени органи који су
     * његовом органу изричито додељени у табели organ_pristupi.
     *
     * Постојање доделе значи право прегледа; прекидачи (креирање/измена/брисање) су додатни
     * услов уз дозволу коју корисник већ мора да има кроз своју улогу.
     *
     * @return array<int, int>
     */
    public function dostupniOrganiIds(?Authenticatable $user = null): array
    {
        return array_keys($this->pristupi($user));
    }

    /**
     * Органи који се нуде у пољу „Орган" при уносу — сопствени плус они где је укључено
     * креирање.
     *
     * @return array<int, string> [id => назив органа]
     */
    public function organiZaUnos(): array
    {
        $dozvoljeni = array_keys(array_filter(
            $this->pristupi(),
            fn (array $prava): bool => $prava['kreiranje'],
        ));

        return $this->nazivi($dozvoljeni);
    }

    public function mozeKreiratiUOrganu(?int $organId, ?Authenticatable $user = null): bool
    {
        return $this->pravo($organId, 'kreiranje', $user);
    }

    public function mozeMenjatiUOrganu(?int $organId, ?Authenticatable $user = null): bool
    {
        return $this->pravo($organId, 'izmena', $user);
    }

    public function mozeBrisatiUOrganu(?int $organId, ?Authenticatable $user = null): bool
    {
        return $this->pravo($organId, 'brisanje', $user);
    }

    /**
     * @param 'kreiranje'|'izmena'|'brisanje' $vrsta
     */
    private function pravo(?int $organId, string $vrsta, ?Authenticatable $user = null): bool
    {
        if ($organId === null) {
            return false;
        }

        return $this->pristupi($user)[$organId][$vrsta] ?? false;
    }

    /**
     * Мапа орган → права. Сопствени орган увек носи сва права (њих даље ограничавају дозволе
     * улоге); подређени органи само оно што је изричито укључено.
     *
     * Корисник се прима као аргумент јер полисе добијају корисника над којим се провера ради,
     * а он не мора бити улогован (нпр. Gate::forUser). Без аргумента важи улоговани.
     *
     * @return array<int, array{kreiranje: bool, izmena: bool, brisanje: bool}>
     */
    private function pristupi(?Authenticatable $user = null): array
    {
        $user ??= Auth::user();

        if (! $user || ! $user->organ_id) {
            return [];
        }

        $kljuc = (int) $user->getKey();

        if (isset(self::$pristupiCache[$kljuc])) {
            return self::$pristupiCache[$kljuc];
        }

        $pristupi = [
            (int) $user->organ_id => ['kreiranje' => true, 'izmena' => true, 'brisanje' => true],
        ];

        $dodeljeni = OrganPristup::query()
            ->where('nadredjeni_organ_id', $user->organ_id)
            ->get(['podredjeni_organ_id', 'moze_kreiranje', 'moze_izmenu', 'moze_brisanje']);

        foreach ($dodeljeni as $dodela) {
            $organId = (int) $dodela->podredjeni_organ_id;

            // Сопствени орган не сме да буде сужен доделом
            if (isset($pristupi[$organId])) {
                continue;
            }

            $pristupi[$organId] = [
                'kreiranje' => (bool) $dodela->moze_kreiranje,
                'izmena' => (bool) $dodela->moze_izmenu,
                'brisanje' => (bool) $dodela->moze_brisanje,
            ];
        }

        return self::$pristupiCache[$kljuc] = $pristupi;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, string> [id => назив органа]
     */
    private function nazivi(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return SifarnikOrgani::query()
            ->whereIn('id', $ids)
            ->orderBy('organ')
            ->pluck('organ', 'id')
            ->map(fn ($naziv): string => (string) $naziv)
            ->all();
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
     * Да ли текући корисник сме да бира орган на контролној табли — уз посебну дозволу
     * (бира међу свим органима) или зато што му је додељен бар још један орган поред
     * сопственог (бира само међу својима).
     */
    public function canSelectOrgan(): bool
    {
        if (Auth::user()?->can(self::PERMISSION_IZBOR_ORGANA)) {
            return true;
        }

        return count($this->dostupniOrganiIds()) > 1;
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
     * Органи понуђени у падајућој листи на контролној табли.
     *
     * Уз посебну дозволу: сви органи који уопште имају унете податке, плус сопствени орган
     * корисника (да избор увек има одговарајућу ставку). Без ње: само органи који су
     * кориснику доступни — сопствени и они изричито додељени.
     *
     * @return array<int, string> [id => назив органа]
     */
    public function getSelectableOrgani(): array
    {
        if (self::$organiCache !== null) {
            return self::$organiCache;
        }

        if (! Auth::user()?->can(self::PERMISSION_IZBOR_ORGANA)) {
            return self::$organiCache = $this->nazivi($this->dostupniOrganiIds());
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
