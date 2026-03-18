<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use Filament\Actions\DeleteAction;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Throwable;
use App\Filament\Resources\PodaciORadnomMestuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;

class EditPodaciORadnomMestu extends EditRecord
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    #[Locked]
    public array $oldRelationships = [];

    public array $mestaRadaData = [];

    #[Locked]
    public ?string $previousUrl = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    /**
     * Обрада података пре чувања
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Читај директно из form state (јер је поље dehydrated(false))
        $this->mestaRadaData = $this->data['mestaRada'] ?? [];

        // Уклони из data array ако постоји
        unset($data['mestaRada']);

        return $data;
    }

    /**
     * Sačuvaj stare vrednosti many-to-many relacija kada se stranica učita
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->previousUrl = url()->previous();

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

                        // Sačuvaj stare vrednosti sa nazivima
                        $oldValues = $this->record->{$relationName}
                            ->pluck($this->getRelationDisplayColumn($relation))
                            ->toArray();

                        $this->oldRelationships[$relationName] = $oldValues;
                    }
                } catch (Throwable $e) {
                    Log::warning('Greška pri učitavanju relacije u EditPodaciORadnomMestu::mount', [
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
     * Logiraj promene many-to-many relacija nakon snimanja
     */
    protected function afterSave(): void
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

        // Затим логирај промене
        foreach ($this->oldRelationships as $relationName => $oldValues) {
            try {
                $relation = $this->record->{$relationName}();

                // Učitaj nove vrednosti sa nazivima
                $newValues = $this->record->{$relationName}()
                    ->pluck($this->getRelationDisplayColumn($relation))
                    ->toArray();

                // Uporedi i logiraj ako ima promena
                if ($oldValues != $newValues) {
                    $oldNames = array_values($oldValues);
                    $newNames = array_values($newValues);

                    // Konvertuj array u string za prikaz
                    $oldNamesString = implode(', ', $oldNames);
                    $newNamesString = implode(', ', $newNames);

                    activity('podaci_o_radnom_mestu')
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'relation' => $relationName,
                            'old' => [$relationName => $oldNamesString],
                            'attributes' => [$relationName => $newNamesString],
                        ])
                        ->tap(function ($activity) {
                            $activity->ip_address = request()->ip();
                        })
                        ->log('Ažurirana ' . $this->getRelationLabel($relationName));
                }
            } catch (Throwable $e) {
                Log::warning('Greška pri logiranju relacije u EditPodaciORadnomMestu::afterSave', [
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
