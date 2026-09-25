@php
    $ukupno = count(\App\Services\Uvoz\SemaUvoza::polja());
    $mapirano = collect($this->data['mapiranje'] ?? [])
        ->filter(fn ($indeks) => $indeks !== null && $indeks !== '')
        ->count();

    $iskoriscene = collect($this->data['mapiranje'] ?? [])
        ->filter(fn ($indeks) => $indeks !== null && $indeks !== '')
        ->map(fn ($indeks) => (int) $indeks)
        ->values()
        ->all();

    $neiskoriscene = collect($this->zaglavlje)
        ->reject(fn ($naziv, $indeks) => in_array($indeks, $iskoriscene, true))
        ->filter(fn ($naziv) => $naziv !== '')
        ->values();
@endphp

<div style="border:1px solid rgb(228 228 231);border-radius:.5rem;padding:.75rem 1rem;background:rgb(250 250 250);">
    <div style="font-weight:600;font-size:.875rem;">
        Мапирано {{ $mapirano }} / {{ $ukupno }} поља
    </div>

    <div style="font-size:.8125rem;color:rgb(82 82 91);margin-top:.375rem;line-height:1.5;">
        Немапирано поље се при увозу <strong>уопште не дира</strong> — вредност у бази остаје.
        Мапирано поље чија је ћелија празна биће <strong>испражњено</strong>.
    </div>

    @if ($neiskoriscene->isNotEmpty())
        <div style="font-size:.8125rem;color:rgb(146 64 14);margin-top:.5rem;line-height:1.5;">
            Колоне из датотеке које нису искоришћене ({{ $neiskoriscene->count() }}):
            {{ $neiskoriscene->join(', ') }}
        </div>
    @endif
</div>
