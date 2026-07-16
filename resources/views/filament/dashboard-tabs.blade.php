<nav role="tablist" class="fi-tabs mb-4" style="width: fit-content">
    <button
        type="button"
        role="tab"
        wire:click="setActiveTab('javni')"
        aria-selected="{{ $activeTab === 'javni' ? 'true' : 'false' }}"
        @class(['fi-tabs-item', 'fi-active' => $activeTab === 'javni'])
    >
        <span class="fi-tabs-item-label">Јавни</span>
    </button>

    <button
        type="button"
        role="tab"
        wire:click="setActiveTab('interni')"
        aria-selected="{{ $activeTab === 'interni' ? 'true' : 'false' }}"
        @class(['fi-tabs-item', 'fi-active' => $activeTab === 'interni'])
    >
        <span class="fi-tabs-item-label">Интерни</span>
    </button>
</nav>
<div class="mb-4 flex items-center gap-2">
    <label for="dashboard-godina" class="text-sm text-gray-500 dark:text-gray-400">За годину:</label>

    <select
        id="dashboard-godina"
        wire:change="setGodina($event.target.value)"
        class="fi-input block rounded-lg border-none bg-white py-1.5 pe-8 ps-3 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-gray-300 dark:ring-white/20 dark:focus:ring-primary-500"
    >
        @foreach ($godine as $g)
            <option value="{{ $g }}" @selected($g === $godina)>{{ $g }}</option>
        @endforeach
    </select>
</div>
