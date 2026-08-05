<?php

namespace App\Filament\Pages;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    #[Url]
    public string $activeTab = 'javni';

    /**
     * Null znaci "korisnik nije birao" -> podrazumevano prosla godina.
     * Ne moze se staviti kao podrazumevana vrednost property-ja jer nije konstanta.
     */
    #[Url]
    public ?int $godina = null;

    /**
     * Орган изабран у падајућој листи. Види се само уз дозволу
     * OrganFilterService::PERMISSION_IZBOR_ORGANA; иначе важи сопствени орган корисника.
     */
    #[Url]
    public ?int $organ = null;

    public function getColumns(): int|array
    {
        return 12;
    }

    public function canSelectOrgan(): bool
    {
        return app(OrganFilterService::class)->canSelectOrgan();
    }

    /**
     * Орган по коме се тренутно филтрирају графикони (изабрани или сопствени).
     */
    public function getOrganId(): ?int
    {
        return app(OrganFilterService::class)->resolveChartOrganId($this->organ);
    }

    /**
     * @return array<int, string>
     */
    public function getOrgani(): array
    {
        return app(OrganFilterService::class)->getSelectableOrgani();
    }

    public function getGodina(): int
    {
        return $this->godina ?? (now()->year - 1);
    }

    /**
     * Godine za koje uopste postoje oglaseni konkursi (uz organ filter, da korisnik
     * vidi samo godine koje su mu relevantne). Trenutno izabrana godina se uvek nudi.
     *
     * @return array<int, int>
     */
    public function getGodine(): array
    {
        $query = PodaciORadnomMestu::query()->whereNotNull('datum_oglasavanja');
        $query = app(OrganFilterService::class)->applyOrganFilterForCharts($query, 'organ', $this->organ);

        $godine = $query->selectRaw('YEAR(datum_oglasavanja) as g')
            ->distinct()
            ->orderByDesc('g')
            ->pluck('g')
            ->map(fn ($g): int => (int) $g)
            ->all();

        $aktuelna = $this->getGodina();

        if (! in_array($aktuelna, $godine, true)) {
            $godine[] = $aktuelna;
            rsort($godine);
        }

        return $godine;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Html::make(fn(): string => view('filament.dashboard-tabs', [
                'activeTab' => $this->activeTab,
                'godina' => $this->getGodina(),
                'godine' => $this->getGodine(),
                'mozeBiratiOrgan' => $this->canSelectOrgan(),
                'organ' => $this->getOrganId(),
                'organi' => $this->canSelectOrgan() ? $this->getOrgani() : [],
            ])->render()),
            $this->getWidgetsContentComponent(),
        ]);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('tipKonkursaChanged', tipKonkursa: $tab === 'interni' ? 2 : 1);
    }

    public function setGodina(int $godina): void
    {
        $this->godina = $godina;
        $this->dispatch('godinaChanged', godina: $godina);
    }

    /**
     * Долази са фронта као стринг ('' кад ништа није изабрано). Сама провера дозволе
     * је у OrganFilterService — овде се вредност само нормализује.
     */
    public function setOrgan(string $organ): void
    {
        if (! $this->canSelectOrgan()) {
            return;
        }

        $this->organ = $organ === '' ? null : (int) $organ;
        $this->dispatch('organChanged', organ: $this->organ);
    }
}
