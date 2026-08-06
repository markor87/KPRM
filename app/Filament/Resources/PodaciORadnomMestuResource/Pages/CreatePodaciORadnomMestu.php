<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Throwable;
use App\Filament\Resources\PodaciORadnomMestuResource;
use App\Models\SifarnikKodoviGradova;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreatePodaciORadnomMestu extends CreateRecord
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    /**
     * Креирање и `afterCreate()` (где иде sync места рада) морају да буду у истој
     * трансакцији — иначе пад sync-а оставља креиран запис без места рада.
     */
    protected ?bool $hasDatabaseTransactions = true;

    public array $mestaRadaData = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function updatedData($value, $key): void
    {
        if (preg_match('/^mestaRada\.[^.]+\.sifarnik_kodovi_gradova_id$/', $key)) {
            $parts = explode('.', $key);
            $itemKey = $parts[1];

            if ($value) {
                $grad = SifarnikKodoviGradova::find($value);
                $this->data['mestaRada'][$itemKey]['region']    = $grad?->region;
                $this->data['mestaRada'][$itemKey]['oblast']    = $grad?->oblast;
                $this->data['mestaRada'][$itemKey]['kod_grada'] = $grad?->kod_grada;
            } else {
                $this->data['mestaRada'][$itemKey]['region']    = null;
                $this->data['mestaRada'][$itemKey]['oblast']    = null;
                $this->data['mestaRada'][$itemKey]['kod_grada'] = null;
            }
        }
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
                if (isset($mesto['sifarnik_kodovi_gradova_id']) && $mesto['sifarnik_kodovi_gradova_id']) {
                    $grad = SifarnikKodoviGradova::find($mesto['sifarnik_kodovi_gradova_id']);
                    $syncData[$mesto['sifarnik_kodovi_gradova_id']] = [
                        'broj_izvrsilaca' => $mesto['broj_izvrsilaca'] ?? 1,
                        'region'          => $grad?->region,
                        'oblast'          => $grad?->oblast,
                        'kod_grada'       => $grad?->kod_grada,
                    ];
                }
            }

            // Sync са pivot подацима
            $this->record->mestaRada()->sync($syncData);
        }

        // Затим логирај belongsToMany релације.
        // ВАЖНО: ЕКСПЛИЦИТНА листа релација, а НЕ рефлексија преко свих метода модела
        // — рефлексија би позвала и деструктивне методе без параметара (нпр.
        // forceDelete()/delete() из SoftDeletes trait-а) и обрисала тек креиран запис.
        foreach (['mestaRada', 'oblastiRada'] as $relationName) {
            try {
                $relation = $this->record->{$relationName}();

                if (! $relation instanceof BelongsToMany) {
                    continue;
                }

                $newValues = $this->record->{$relationName}()
                    ->pluck($this->getRelationDisplayColumn($relation))
                    ->toArray();

                if (!empty($newValues)) {
                    $newNamesString = implode(', ', array_values($newValues));

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
            } catch (Throwable $e) {
                Log::warning('Greška pri logiranju relacije u CreatePodaciORadnomMestu', [
                    'relation' => $relationName,
                    'record_id' => $this->record->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                continue;
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
            'sifarnik_kodovi_gradova' => 'grad',
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
