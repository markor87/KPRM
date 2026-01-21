<x-filament-panels::page.simple>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Добродошли у КПРМ
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Молимо вас да попуните податке за завршетак регистрације
        </p>
    </div>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-4 justify-start mt-6">
            <x-filament::button
                type="submit"
                size="lg"
            >
                Региструј се
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page.simple>
