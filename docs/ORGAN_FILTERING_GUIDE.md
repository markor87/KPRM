# Изолација података по органу

Подаци о радним местима су изоловани по органу — корисник једног органа не сме да види
ни мења записе другог. Изолација стоји у **три независна слоја**; ниједан се не сме
уклонити „јер га покрива онај други".

> Раније је овај документ описивао trait `App\Filament\Traits\HasOrganFiltering`. Тај
> trait није користила ниједна класа и обрисан је. Стварни механизам је описан испод.

## 1. Упит ресурса — `getEloquentQuery()`

`PodaciORadnomMestuResource::getEloquentQuery()` провлачи упит кроз
`OrganFilterService::applyOrganFilter()`. То покрива листу, извоз и све акције над
табелом.

```php
public static function getEloquentQuery(): Builder
{
    return app(OrganFilterService::class)->applyOrganFilter(parent::getEloquentQuery(), 'organ');
}
```

## 2. Полиса — `PodaciORadnomMestuPolicy`

Приватна метода `pripadaOrganuKorisnika()` пресреће појединачни запис (`view`, `update`,
`delete`, `restore`, `replicate`). То хвата и директан улазак преко URL-а са туђим ID-ем,
где упит из слоја 1 не учествује.

## 3. Модел — `PodaciORadnomMestu::booted()`

При упису се орган присилно везује за орган корисника, да ни грешка у форми ни ручно
подметнута вредност не могу да „пребаце" запис у други орган.

## `OrganFilterService`

`app/Services/OrganFilterService.php` је једино место где се одлучује шта корисник види:

| Метода | Где се користи | Понашање |
|---|---|---|
| `applyOrganFilter()` | табела, извоз | ко има `ViewAny:PodaciORadnomMestu` види све органе; остали само свој; без органа — ништа |
| `applyOrganFilterForCharts()` | свих 11 виџета, листа година | увек један орган — изабрани (уз дозволу) или сопствени |
| `resolveChartOrganId()` | контролна табла | одлучује који је то орган; вредност са фронта се прихвата само уз дозволу |
| `canSelectOrgan()` / `getSelectableOrgani()` | падајућа листа органа | дозвола `IzborOrganaNaKontrolnojTabli` |

Разлика између прва два реда је намерна: у табели носилац `ViewAny` дозволе види све
органе, док графикони **увек** приказују тачно један орган — иначе би бројеви били збир
целе државе и не би значили ништа.

## Избор органа на контролној табли

Корисник са посебном дозволом **„Избор органа на контролној табли"**
(`OrganFilterService::PERMISSION_IZBOR_ORGANA` = `IzborOrganaNaKontrolnojTabli`, таб
*Посебне дозволе* при уређивању улоге) добија падајућу листу изнад графикона. Избор иде
кроз URL и Livewire догађај `organChanged` до свих виџета.

Без те дозволе избор се игнорише — и кад се `?organ=` ручно дода у адресу — па корисник
остаје на свом органу. Провера је у `resolveChartOrganId()`, не у приказу дугмета.

## Нови виџет

```php
use App\Filament\Widgets\Concerns\HasDashboardFilters;

class NoviChart extends ApexChartWidget
{
    use HasDashboardFilters;   // доноси tipKonkursa, godina, organ + слушаче догађаја

    protected function getOptions(): array
    {
        $query = PodaciORadnomMestu::whereYear('datum_oglasavanja', $this->getGodina())
            ->where('tip_konkursa', $this->tipKonkursa);

        $query = app(OrganFilterService::class)
            ->applyOrganFilterForCharts($query, 'organ', $this->getOrganId());

        // ...
    }
}
```

Ако се изостави `applyOrganFilterForCharts()`, виџет ће приказивати податке свих органа
свима — то је најлакша грешка у овом делу кода и не пријављује се сама.

## Нови ресурс

Ресурси `UserResource`, `InvitationResource` и `ActivityResource` **немају** изолацију по
органу. За кориснике и евиденцију активности то тренутно не цури јер те дозволе има само
Super Admin, а код позивница је прекогранично слање намерно (позивнице шаље централна
служба свим органима). Пре него што се тим ресурсима дозволе прошире на друге улоге,
мора се додати слој 1 (и слој 2 ако се приступа појединачном запису).
