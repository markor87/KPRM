<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\OrganFilterService;
use Livewire\Attributes\On;

trait HasDashboardFilters
{
    public int $tipKonkursa = 1;

    /**
     * Орган изабран у падајућој листи на контролној табли. Null знаци „није биран" →
     * важи сопствени орган корисника. Избор поштујемо само ако корисник има дозволу
     * (проверу ради OrganFilterService, не веруј вредности која стиже са фронта).
     */
    public ?int $organ = null;

    /**
     * Null знаци „није постављено" → подразумевано прошла година. Не може се ставити
     * као подразумевана вредност јер now() није константа. Држимо default null да
     * својство буде УВЕК иницијализовано (спречава „must not be accessed before
     * initialization" на путањама где се mount прескаче, нпр. lazy рендер).
     */
    public ?int $godina = null;

    public function mount(): void
    {
        $this->tipKonkursa = request()->query('activeTab', 'javni') === 'interni' ? 2 : 1;
        $this->godina = (int) request()->query('godina', now()->year - 1);

        $organ = request()->query('organ');
        $this->organ = is_numeric($organ) ? (int) $organ : null;

        parent::mount();
    }

    /**
     * Орган по коме виџет филтрира податке — изабрани (ако корисник сме) или сопствени.
     */
    public function getOrganId(): ?int
    {
        return app(OrganFilterService::class)->resolveChartOrganId($this->organ);
    }

    /**
     * Увек враћа исправну годину — фаллбацк на прошлу годину ако није изабрана.
     * Виџети користе ову методу уместо директног $this->godina.
     */
    public function getGodina(): int
    {
        return $this->godina ?? (now()->year - 1);
    }

    #[On('tipKonkursaChanged')]
    public function tipKonkursaChanged(int $tipKonkursa): void
    {
        $this->tipKonkursa = $tipKonkursa;
        $this->updateOptions();
    }

    #[On('godinaChanged')]
    public function godinaChanged(int $godina): void
    {
        $this->godina = $godina;
        $this->updateOptions();
    }

    #[On('organChanged')]
    public function organChanged(?int $organ): void
    {
        $this->organ = $organ;
        $this->updateOptions();
    }
}
