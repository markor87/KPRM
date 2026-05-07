<?php

namespace App\Filament\Widgets\Concerns;

use Livewire\Attributes\On;

trait HasTipKonkursaFilter
{
    public int $tipKonkursa = 1;

    public function mount(): void
    {
        $this->tipKonkursa = request()->query('activeTab', 'javni') === 'interni' ? 2 : 1;
        parent::mount();
    }

    #[On('tipKonkursaChanged')]
    public function tipKonkursaChanged(int $tipKonkursa): void
    {
        $this->tipKonkursa = $tipKonkursa;
        $this->updateOptions();
    }
}
