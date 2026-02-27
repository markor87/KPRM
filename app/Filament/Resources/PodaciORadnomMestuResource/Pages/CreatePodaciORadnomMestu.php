<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Throwable;
use App\Filament\Resources\PodaciORadnomMestuResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreatePodaciORadnomMestu extends CreateRecord
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    public array $mestaRadaData = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Обрада података пре чувања
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Читај директно из form state (јер је поље dehydrated(false))
        $this->mestaRadaData = $this->data['mestaRada'] ?? [];

        // Уклони из data array ако постоји
        unset($data['mestaRada']);

        return $data;
    }

    /**
     * Logiraj many-to-many relacije nakon kreiranja
     */
    protected function afterCreate(): void
    {
        // Прво сачувај mestaRada релацију са pivot подацима
        if (isset($this->mestaRadaData)) {
            $syncData = [];

            foreach ($this->mestaRadaData as $mesto) {
                if (isset($mesto['sifarnik_mesta_id']) && $mesto['sifarnik_mesta_id']) {
                    $syncData[$mesto['sifarnik_mesta_id']] = [
                        'broj_izvrsilaca' => $mesto['broj_izvrsilaca'] ?? 1,
                    ];
                }
            }

            // Sync са pivot подацима
            $this->record->mestaRada()->sync($syncData);
        }

        // Затим логирај релације
        // Pronađi sve belongsToMany relacije na modelu
        $reflection = new ReflectionClass($this->record);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class === get_class($this->record) &&
                $method->getNumberOfParameters() === 0 &&
                !in_array($method->name, ['getKey', 'getMorphClass', 'getTable'])) {

                try {
                    $relation = $this->record->{$method->name}();

                    // Proveravamo da li je belongsToMany relacija
                    if ($relation instanceof BelongsToMany) {
                        $relationName = $method->name;

                        // Učitaj nove vrednosti sa nazivima
                        $newValues = $this->record->{$relationName}()
                            ->pluck($this->getRelationDisplayColumn($relation))
                            ->toArray();

                        // Logiraj ako ima vrednosti
                        if (!empty($newValues)) {
                            $newNames = array_values($newValues);

                            // Konvertuj array u string za prikaz
                            $newNamesString = implode(', ', $newNames);

                            activity('podaci_o_radnom_mestu')
                                ->performedOn($this->record)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'relation' => $relationName,
                                    'attributes' => [$relationName => $newNamesString],
                                ])
                                ->tap(function ($activity) {
                                    $activity->ip_address = request()->ip();
                                })
                                ->log('Dodato ' . $this->getRelationLabel($relationName));
                        }
                    }
                } catch (Throwable $e) {
                    // Logiraj greške umesto tihog ignorisanja
                    Log::warning('Greška pri logiranju relacije u CreatePodaciORadnomMestu', [
                        'method' => $method->name,
                        'record_id' => $this->record->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }
    }

    /**
     * Odredi kolonu za prikaz naziva relacije (mesto, organ, itd.)
     */
    protected function getRelationDisplayColumn($relation): string
    {
        $relatedModel = $relation->getRelated();
        $tableName = $relatedModel->getTable();

        // Mapiranje tabela na njihove display kolone
        $displayColumns = [
            'sifarnik_mesta' => 'mesto',
            'sifarnik_organi' => 'organ',
            'sifarnik_zvanje' => 'zvanje',
        ];

        return $displayColumns[$tableName] ?? 'name';
    }

    /**
     * Odredi label za relaciju u logu
     */
    protected function getRelationLabel(string $relationName): string
    {
        $labels = [
            'mestaRada' => 'mesta rada',
            'organi' => 'organi',
            'zvanja' => 'zvanja',
        ];

        return $labels[$relationName] ?? $relationName;
    }
}
