<?php

namespace App\Filament\Widgets\Concerns;

use Livewire\Attributes\On;

trait HasDashboardFilters
{
    public int $tipKonkursa = 1;

    public int $godina;

    public function mount(): void
    {
        $this->tipKonkursa = request()->query('activeTab', 'javni') === 'interni' ? 2 : 1;
        $this->godina = (int) request()->query('godina', now()->year - 1);
        parent::mount();
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
}
