<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Погледајте видео упутство директно у апликацији, или преузмите упутство (PDF и видео)
        помоћу дугмади у горњем десном углу.
    </p>

    {{-- Видео --}}
    <x-filament::section>
        <x-slot name="heading">Видео упутство</x-slot>
        <x-slot name="description">Обука о коришћењу платформе за унос података о конкурсним поступцима.</x-slot>

        @if ($this->videoPostoji())
            <video
                controls
                preload="metadata"
                class="w-full rounded-lg border border-gray-200 dark:border-gray-700"
                style="max-width: 100%;"
            >
                <source src="{{ $this->videoStreamUrl() }}" type="video/mp4">
                Ваш прегледач не подржава репродукцију видеа.
            </video>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Видео упутство ускоро.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
