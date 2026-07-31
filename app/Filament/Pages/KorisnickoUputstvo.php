<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class KorisnickoUputstvo extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Корисничко упутство';

    protected static ?string $title = 'Корисничко упутство';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.korisnicko-uputstvo';

    protected string $videoPath = 'uputstvo/Обука о коришћењу платформе за унос података о конкурсним поступцима.mp4';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preuzmiPdf')
                ->label('Преузми PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => $this->pdfUrl()),
        ];
    }

    public function videoPostoji(): bool
    {
        return Storage::disk('local')->exists($this->videoPath);
    }

    public function pdfUrl(): string
    {
        return route('filament.admin.korisnicko-uputstvo.pdf');
    }

    public function videoStreamUrl(): string
    {
        return route('filament.admin.korisnicko-uputstvo.video');
    }
}
