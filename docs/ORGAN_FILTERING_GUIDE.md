# Organ Filtering Guide za Widgets

## Pregled

Ovaj dokument objašnjava kako implementirati filtriranje po organu u Filament widget-ima.

## Quick Start

### Za nove widget-e:

**Korak 1**: Dodaj trait u widget klasu:
```php
use App\Filament\Traits\HasOrganFiltering;

class YourWidget extends BaseWidget
{
    use HasOrganFiltering;

    // Tvoj widget kod...
}
```

**Korak 2**: Koristi filtering metode u stat-ovima:
```php
protected function getStats(): array
{
    return [
        Stat::make('Count',
            $this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
        ),
    ];
}
```

## Dostupne Metode

### `getFilteredCount(string $model, string $organColumn = 'organ'): int`
Vraća filtered count zapisa za model.

**Parametri:**
- `$model` - Ime klase modela (npr. `PodaciORadnomMestu::class`)
- `$organColumn` - Ime kolone za organ (default: `'organ'`)

**Primer:**
```php
$count = $this->getFilteredCount(PodaciORadnomMestu::class, 'organ');
```

### `applyOrganFilter(Builder $query, string $organColumn = 'organ'): Builder`
Primenjuje organ filtering na bilo koji Eloquent query.

**Parametri:**
- `$query` - Eloquent query builder
- `$organColumn` - Ime kolone za organ (default: `'organ'`)

**Primer:**
```php
$query = PodaciORadnomMestu::query()
    ->where('status_konkursa_na_dan_1', 'active');

$count = $this->applyOrganFilter($query, 'organ')->count();
```

### `canViewAllOrganData(): bool`
Proverava da li trenutni korisnik može videti sve podatke (bez organ filtera).

**Vraća:** `true` ako korisnik može videti sve podatke, `false` inače.

**Primer:**
```php
if ($this->canViewAllOrganData()) {
    // Korisnik vidi sve podatke
}
```

### `getUserOrganId(): ?int`
Vraća organ_id trenutnog korisnika.

**Vraća:** `int` organ_id ili `null` ako korisnik nema organ.

**Primer:**
```php
$organId = $this->getUserOrganId();
```

---

## Filtering Logika

Filtering radi po sledećoj hijerarhiji:

1. **Super Admin** (`is_super_admin = true` ILI 'Super Admin' rola) → Vidi **SVE** podatke
2. **Korisnici sa `view_all_radna_mesta` dozvolom** → Vidi **SVE** podatke
3. **Obični korisnici sa `organ_id`** → Vidi SAMO podatke gde `organ` = njihov `organ_id`
4. **Korisnici bez `organ_id`** → Ne vidi ništa (prazan rezultat)

---

## Primeri Korišćenja

### Primer 1: Jednostavan brojač

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PodaciORadnomMestu;
use App\Filament\Traits\HasOrganFiltering;

class SimpleCountWidget extends BaseWidget
{
    use HasOrganFiltering;

    protected function getStats(): array
    {
        return [
            Stat::make('Radna Mesta',
                $this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
            )
            ->description('Broj radnih mesta')
            ->color('success'),
        ];
    }
}
```

### Primer 2: Kompleksan query sa dodatnim filterima

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PodaciORadnomMestu;
use App\Filament\Traits\HasOrganFiltering;

class ActiveRadnaMestaWidget extends BaseWidget
{
    use HasOrganFiltering;

    protected function getStats(): array
    {
        // Kreiraj custom query sa dodatnim filterima
        $query = PodaciORadnomMestu::query()
            ->where('status_konkursa_na_dan_1', 'active')
            ->whereMonth('datum_oglasavanja', now()->month)
            ->whereYear('datum_oglasavanja', now()->year);

        // Primeni organ filtering
        $count = $this->applyOrganFilter($query, 'organ')->count();

        return [
            Stat::make('Aktivni Konkursi Ovog Meseca', $count)
                ->description('Radna mesta oglašena ovog meseca')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
        ];
    }
}
```

### Primer 3: Dinamičke labele zavisno od korisničkih dozvola

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PodaciORadnomMestu;
use App\Filament\Traits\HasOrganFiltering;

class ConditionalWidget extends BaseWidget
{
    use HasOrganFiltering;

    protected function getStats(): array
    {
        // Proveri da li je filtrirano
        $isFiltered = !$this->canViewAllOrganData();
        $userOrgan = $isFiltered ? auth()->user()?->organ?->organ : null;

        return [
            Stat::make(
                $isFiltered ? "Radna Mesta u {$userOrgan}" : 'Sva Radna Mesta',
                $this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
            )
            ->description($isFiltered
                ? 'Filtrirano za vaš organ'
                : 'Sva radna mesta u sistemu'
            )
            ->color('info'),
        ];
    }
}
```

### Primer 4: Chart widget sa filteringom

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PodaciORadnomMestu;
use App\Filament\Traits\HasOrganFiltering;

class RadnaMestaPerMonthChart extends ChartWidget
{
    use HasOrganFiltering;

    protected static ?string $heading = 'Radna Mesta po Mesecima';

    protected function getData(): array
    {
        $query = PodaciORadnomMestu::query()
            ->selectRaw('MONTH(datum_oglasavanja) as month, COUNT(*) as count')
            ->whereYear('datum_oglasavanja', now()->year)
            ->groupBy('month');

        // Primeni organ filtering
        $data = $this->applyOrganFilter($query, 'organ')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Broj Radnih Mesta',
                    'data' => $data->pluck('count')->toArray(),
                ],
            ],
            'labels' => $data->pluck('month')->map(function ($month) {
                return date('F', mktime(0, 0, 0, $month, 1));
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
```

---

## Custom Column Names

Ako tvoj model koristi drugu kolonu za organ (ne `'organ'`), specificiraj je kao drugi parametar:

```php
// Za User model koji koristi 'organ_id' kolonu
$this->getFilteredCount(User::class, 'organ_id')

// Za PodaciORadnomMestu model koji koristi 'organ' kolonu
$this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
```

**Primeri kolona:**
- `User` model: `organ_id`
- `PodaciORadnomMestu` model: `organ`

---

## Registrovanje Widget-a

Nakon što kreiraš widget, registruj ga u `AdminPanelProvider.php`:

```php
// app/Providers/Filament/AdminPanelProvider.php

->widgets([
    \App\Filament\Widgets\StatsOverviewWidget::class,
    \App\Filament\Widgets\YourNewWidget::class, // Dodaj ovde
])
```

---

## Testing Checklist

Testiraj sa različitim tipovima korisnika:

### 1. Super Admin Korisnik
- [ ] Login kao Super Admin
- [ ] Proveri da vidiš **SVE** podatke
- [ ] Labele treba da kažu: "Total Users", "Total Radna Mesta" (ne "Your Organ")
- [ ] Description: "All registered users in the system"

### 2. Korisnik sa `view_all_radna_mesta` Dozvolom
- [ ] Login kao korisnik sa dozvolom
- [ ] Proveri da vidiš **SVE** podatke (kao Super Admin)
- [ ] Ista labela i description kao Super Admin

### 3. Običan Korisnik sa `organ_id`
- [ ] Login kao običan korisnik sa dodeljenim organom
- [ ] Proveri da vidiš **SAMO** podatke za svoj organ
- [ ] Labele treba da kažu: "Users in Your Organ", "Radna Mesta in Your Organ"
- [ ] Description: "Users in [Organ Name]"
- [ ] Count treba da odgovara broju zapisa za taj organ

### 4. Korisnik bez `organ_id`
- [ ] Login kao korisnik bez dodeljenog organa
- [ ] Proveri da vidiš **0** zapisa
- [ ] Bez error-a

---

## Common Pitfalls (Česte greške)

### 1. Zaboravljen Custom Column Name
**Problem:** Koristiš model sa drugom kolonom za organ, ali ne specificiš kolonu.

**Greška:**
```php
// User model ima 'organ_id', ne 'organ'
$this->getFilteredCount(User::class) // ❌ Traži 'organ' kolonu
```

**Rešenje:**
```php
$this->getFilteredCount(User::class, 'organ_id') // ✅ Korektno
```

### 2. Filtriranje System-Wide Podataka
**Problem:** Pokušavaš da filtriraš podatke koji ne bi trebalo da budu filtrirani (npr. roles, permissions).

**Greška:**
```php
Stat::make('Roles', $this->getFilteredCount(Role::class, 'organ')) // ❌
```

**Rešenje:**
```php
Stat::make('Roles', Role::count()) // ✅ Roles su globalni
```

### 3. Zaboravljen Trait
**Problem:** Pozivas filtering metode bez trait-a.

**Greška:**
```php
class MyWidget extends BaseWidget
{
    // Zaboravio si: use HasOrganFiltering;

    protected function getStats(): array
    {
        return [
            Stat::make('Count', $this->getFilteredCount(Model::class)) // ❌ Error
        ];
    }
}
```

**Rešenje:**
```php
use App\Filament\Traits\HasOrganFiltering;

class MyWidget extends BaseWidget
{
    use HasOrganFiltering; // ✅

    protected function getStats(): array
    {
        return [
            Stat::make('Count', $this->getFilteredCount(Model::class)) // ✅
        ];
    }
}
```

---

## Performance Optimizacija

### Caching

Ako widget-i zahtevaju česte refr ešove, dodaj caching:

```php
use Illuminate\Support\Facades\Cache;

protected function getFilteredCount(string $model, string $organColumn = 'organ'): int
{
    $cacheKey = "widget.{$model}.organ.{$this->getUserOrganId()}.count";

    return Cache::remember($cacheKey, 60, function () use ($model, $organColumn) {
        $query = $model::query();
        return $this->applyOrganFilter($query, $organColumn)->count();
    });
}
```

---

## Napredni Primeri

### Statistika sa Procentima

```php
protected function getStats(): array
{
    $totalQuery = PodaciORadnomMestu::query();
    $total = $this->applyOrganFilter($totalQuery, 'organ')->count();

    $activeQuery = PodaciORadnomMestu::query()
        ->where('status_konkursa_na_dan_1', 'active');
    $active = $this->applyOrganFilter($activeQuery, 'organ')->count();

    $percentage = $total > 0 ? round(($active / $total) * 100, 2) : 0;

    return [
        Stat::make('Aktivni Konkursi', $active)
            ->description("{$percentage}% od ukupno {$total}")
            ->color('success'),
    ];
}
```

### Poređenje sa Prošlim Periodom

```php
protected function getStats(): array
{
    $currentMonthQuery = PodaciORadnomMestu::query()
        ->whereMonth('datum_oglasavanja', now()->month);
    $currentMonth = $this->applyOrganFilter($currentMonthQuery, 'organ')->count();

    $lastMonthQuery = PodaciORadnomMestu::query()
        ->whereMonth('datum_oglasavanja', now()->subMonth()->month);
    $lastMonth = $this->applyOrganFilter($lastMonthQuery, 'organ')->count();

    $trend = $currentMonth - $lastMonth;
    $trendText = $trend >= 0 ? "+{$trend}" : "{$trend}";

    return [
        Stat::make('Radna Mesta Ovog Meseca', $currentMonth)
            ->description("{$trendText} u odnosu na prošli mesec")
            ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($trend >= 0 ? 'success' : 'danger'),
    ];
}
```

---

## Support

Za pitanja ili probleme, pogledaj:
- **OrganFilterService**: `app/Services/OrganFilterService.php`
- **HasOrganFiltering trait**: `app/Filament/Traits/HasOrganFiltering.php`
- **Primer**: `app/Filament/Widgets/StatsOverviewWidget.php`

---

## Changelog

### 2026-01-04
- Inicijalna verzija dokumentacije
- Dodati primeri za chart widgets
- Dodati napredni primeri (procenti, trend)
