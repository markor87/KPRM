@php
    $i = $this->izvestaj ?? [];

    $plocice = [
        ['Нових записа',      $i['broj_novih'] ?? 0,      'rgb(21 128 61)',  'rgb(240 253 244)'],
        ['Ажурираних',        $i['broj_azuriranih'] ?? 0, 'rgb(29 78 216)',  'rgb(239 246 255)'],
        ['Прескочених',       $i['broj_preskocenih'] ?? 0,'rgb(82 82 91)',   'rgb(250 250 250)'],
        ['Грешака',           $i['broj_gresaka'] ?? 0,    'rgb(185 28 28)',  'rgb(254 242 242)'],
        ['Измењених поља',    $i['broj_izmenjenih_polja'] ?? 0, 'rgb(126 34 206)', 'rgb(250 245 255)'],
        ['Поља за пражњење',  $i['broj_praznjenja'] ?? 0, 'rgb(180 83 9)',   'rgb(255 247 237)'],
    ];
@endphp

<div>
    @if ($i['zavrsen'] ?? false)
        <div style="border:1px solid rgb(134 239 172);background:rgb(240 253 244);color:rgb(21 128 61);
                    border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1rem;font-weight:600;font-size:.875rem;">
            Увоз је завршен и подаци су уписани.
        </div>
    @else
        <div style="border:1px solid rgb(253 186 116);background:rgb(255 247 237);color:rgb(154 52 18);
                    border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem;">
            Ово је <strong>пробни увоз</strong> — ништа још није уписано у базу.
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;margin-bottom:1rem;">
        @foreach ($plocice as [$naslov, $broj, $boja, $pozadina])
            <div style="border:1px solid rgb(228 228 231);border-radius:.5rem;padding:.75rem 1rem;background:{{ $pozadina }};">
                <div style="font-size:1.5rem;font-weight:700;color:{{ $boja }};line-height:1.2;">{{ $broj }}</div>
                <div style="font-size:.75rem;color:rgb(82 82 91);margin-top:.125rem;">{{ $naslov }}</div>
            </div>
        @endforeach
    </div>

    @if (($i['broj_praznjenja'] ?? 0) > 0)
        <div style="border:1px solid rgb(253 186 116);background:rgb(255 247 237);color:rgb(154 52 18);
                    border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem;line-height:1.5;">
            <strong>Пажња:</strong> {{ \App\Services\Uvoz\UvozRadnihMesta::brojPolja($i['broj_praznjenja']) }}
            биће обрисано, јер су одговарајуће ћелије у датотеци празне. Ако то не желите, вратите се на корак мапирања и
            <strong>уклоните мапирање</strong> тих колона — немапирано поље се не дира.
        </div>
    @endif

    @if (! empty($i['izmene']))
        <div style="font-weight:600;font-size:.9375rem;margin-bottom:.5rem;">Шта се мења на постојећим записима</div>

        <div style="font-size:.8125rem;color:rgb(82 82 91);margin-bottom:.5rem;">
            @if (($i['ukupno_izmena'] ?? 0) > count($i['izmene']))
                Приказано {{ count($i['izmene']) }} од {{ $i['ukupno_izmena'] }} измена. Цео списак је у извештају за преузимање.
            @else
                Свака измена посебно — стара вредност поред нове.
            @endif
        </div>

        <div style="overflow-x:auto;border:1px solid rgb(228 228 231);border-radius:.5rem;margin-bottom:1rem;">
            <table style="width:100%;border-collapse:collapse;font-size:.8125rem;">
                <thead>
                    <tr style="background:rgb(244 244 245);text-align:left;">
                        <th style="padding:.5rem .75rem;white-space:nowrap;">Ред</th>
                        <th style="padding:.5rem .75rem;white-space:nowrap;">Запис</th>
                        <th style="padding:.5rem .75rem;">Поље</th>
                        <th style="padding:.5rem .75rem;">Стара вредност</th>
                        <th style="padding:.5rem .75rem;">Нова вредност</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($i['izmene'] as $iz)
                        <tr style="border-top:1px solid rgb(228 228 231);{{ $iz['praznjenje'] ? 'background:rgb(255 247 237);' : '' }}">
                            <td style="padding:.5rem .75rem;white-space:nowrap;vertical-align:top;">{{ $iz['broj_reda'] }}</td>
                            <td style="padding:.5rem .75rem;white-space:nowrap;vertical-align:top;">{{ $iz['zapis'] }}</td>
                            <td style="padding:.5rem .75rem;vertical-align:top;">{{ $iz['polje'] }}</td>
                            <td style="padding:.5rem .75rem;vertical-align:top;color:rgb(113 113 122);">
                                {{ $iz['staro'] === '' ? '(празно)' : $iz['staro'] }}
                            </td>
                            <td style="padding:.5rem .75rem;vertical-align:top;font-weight:600;
                                       color:{{ $iz['praznjenje'] ? 'rgb(154 52 18)' : 'rgb(21 128 61)' }};">
                                {{ $iz['novo'] === '' ? '(празно — брише се)' : $iz['novo'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif (($i['broj_azuriranih'] ?? 0) > 0)
        <div style="font-size:.875rem;color:rgb(82 82 91);margin-bottom:1rem;">
            Ниједно поље на постојећим записима се не мења — вредности у датотеци исте су као у бази.
        </div>
    @endif

    @if (! empty($i['novi_zapisi']))
        <div style="font-size:.8125rem;color:rgb(82 82 91);margin-bottom:1rem;line-height:1.6;">
            <strong>Нови записи:</strong>
            @foreach ($i['novi_zapisi'] as $nz)
                ред {{ $nz['broj_reda'] }} ({{ $nz['polja'] }} попуњених поља){{ ! $loop->last ? ',' : '' }}
            @endforeach
            — њихове вредности су у извештају за преузимање, лист „Измене".
        </div>
    @endif

    @if (! empty($i['redovi']))
        <div style="font-size:.8125rem;color:rgb(82 82 91);margin-bottom:.5rem;">
            Приказано {{ $i['prikazano'] }} од {{ $i['broj_zanimljivih'] }} редова са грешкама или упозорењима.
            Цео списак је у извештају за преузимање.
        </div>

        <div style="overflow-x:auto;border:1px solid rgb(228 228 231);border-radius:.5rem;">
            <table style="width:100%;border-collapse:collapse;font-size:.8125rem;">
                <thead>
                    <tr style="background:rgb(244 244 245);text-align:left;">
                        <th style="padding:.5rem .75rem;white-space:nowrap;">Ред</th>
                        <th style="padding:.5rem .75rem;white-space:nowrap;">Исход</th>
                        <th style="padding:.5rem .75rem;">Порука</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($i['redovi'] as $red)
                        @php $greska = ! empty($red['greske']); @endphp
                        <tr style="border-top:1px solid rgb(228 228 231);{{ $greska ? 'background:rgb(254 242 242);' : '' }}">
                            <td style="padding:.5rem .75rem;white-space:nowrap;vertical-align:top;">{{ $red['broj_reda'] }}</td>
                            <td style="padding:.5rem .75rem;white-space:nowrap;vertical-align:top;
                                       color:{{ $greska ? 'rgb(185 28 28)' : 'rgb(82 82 91)' }};">
                                {{ $red['opis'] }}
                            </td>
                            <td style="padding:.5rem .75rem;line-height:1.5;">
                                @foreach ($red['greske'] as $poruka)
                                    <div style="color:rgb(185 28 28);">{{ $poruka }}</div>
                                @endforeach
                                @foreach ($red['upozorenja'] as $poruka)
                                    <div style="color:rgb(154 52 18);">{{ $poruka }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="font-size:.875rem;color:rgb(21 128 61);">
            Ниједан ред нема грешку ни упозорење.
        </div>
    @endif
</div>
