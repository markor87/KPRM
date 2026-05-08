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
<p class="mb-4 text-sm text-gray-500 dark:text-gray-400">За годину: <strong class="font-semibold text-gray-700 dark:text-gray-300">{{ now()->year - 1 }}</strong></p>
