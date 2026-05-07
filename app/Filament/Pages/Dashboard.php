<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    #[Url]
    public string $activeTab = 'javni';

    public function getColumns(): int|array
    {
        return 12;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Html::make(fn(): string => view('filament.dashboard-tabs', [
                'activeTab' => $this->activeTab,
            ])->render()),
            $this->getWidgetsContentComponent(),
        ]);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('tipKonkursaChanged', tipKonkursa: $tab === 'interni' ? 2 : 1);
    }
}
