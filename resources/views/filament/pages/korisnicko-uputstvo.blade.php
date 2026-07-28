<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Овде можете преузети корисничко упутство или погледати видео упутство директно у апликацији.
    </p>

    {{-- Документ --}}
    <x-filament::section>
        <x-slot name="heading">Документ</x-slot>
        <x-slot name="description">Корисничко упутство у PDF формату.</x-slot>

        <x-filament::button
            tag="a"
            :href="$this->pdfUrl()"
            icon="heroicon-o-document-arrow-down"
        >
            Преузми PDF упутство
        </x-filament::button>
    </x-filament::section>

    {{-- Видео --}}
    <x-filament::section>
        <x-slot name="heading">Видео упутство</x-slot>
        <x-slot name="description">Погледајте видео упутство или га преузмите.</x-slot>

        @if ($this->videoPostoji())
            <div class="space-y-4">
                <video
                    controls
                    preload="metadata"
                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700"
                    style="max-width: 100%;"
                >
                    <source src="{{ $this->videoStreamUrl() }}" type="video/mp4">
                    Ваш прегледач не подржава репродукцију видеа.
                </video>

                <x-filament::button
                    tag="a"
                    :href="$this->videoDownloadUrl()"
                    icon="heroicon-o-arrow-down-tray"
                    color="gray"
                >
                    Преузми видео
                </x-filament::button>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Видео упутство ускоро.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
