<?php

namespace App\Filament\Widgets\Concerns;

use Livewire\Attributes\On;

trait HasDashboardFilters
{
    public int $tipKonkursa = 1;

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
        parent::mount();
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
}
