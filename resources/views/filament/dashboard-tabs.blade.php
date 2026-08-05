{{-- Размак се задаје инлајн: `mb-4` не постоји у Filament v4 теми (види стилове ниже). --}}
<nav role="tablist" class="fi-tabs" style="width: fit-content; margin-bottom: 1.5rem">
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
{{--
    Стилови су намерно писани „ручно", а не Tailwind класама: Filament v4 тема се
    компајлира са `source(none)`, па утилити класе (gap-2, max-w-xs, text-sm…) уопште
    не постоје у изграђеном CSS-у и филтери би остали неформатирани.
--}}
<style>
    .kprm-filteri {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1.5rem;
        margin-bottom: 1rem;
    }

    .kprm-filter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .kprm-filter > label {
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: rgb(107 114 128);
        white-space: nowrap;
    }

    .kprm-filter > select {
        appearance: none;
        max-width: 100%;
        border: 0;
        border-radius: 0.5rem;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' clip-rule='evenodd'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 1.125rem;
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        font-weight: 600;
        color: rgb(55 65 81);
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05), 0 0 0 1px rgb(9 9 11 / 0.1);
        cursor: pointer;
    }

    .kprm-filter > select:focus {
        outline: 2px solid rgb(59 130 246 / 0.6);
        outline-offset: 0;
    }

    .kprm-filter--organ > select {
        width: 22rem;
        text-overflow: ellipsis;
    }

    .dark .kprm-filter > label {
        color: rgb(156 163 175);
    }

    .dark .kprm-filter > select {
        background-color: rgb(255 255 255 / 0.05);
        color: rgb(209 213 219);
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05), 0 0 0 1px rgb(255 255 255 / 0.2);
    }

    .dark .kprm-filter > select option {
        background-color: rgb(24 24 27);
        color: rgb(209 213 219);
    }

    @media (max-width: 640px) {
        .kprm-filter--organ > select {
            width: 100%;
        }
    }
</style>

<div class="kprm-filteri">
    @if ($mozeBiratiOrgan)
        <div class="kprm-filter kprm-filter--organ">
            <label for="dashboard-organ">Орган:</label>

            <select id="dashboard-organ" wire:change="setOrgan($event.target.value)">
                <option value="" @selected($organ === null)>— изабери орган —</option>

                @foreach ($organi as $organId => $nazivOrgana)
                    <option value="{{ $organId }}" @selected((int) $organId === $organ)>{{ $nazivOrgana }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="kprm-filter">
        <label for="dashboard-godina">За годину:</label>

        <select id="dashboard-godina" wire:change="setGodina($event.target.value)">
            @foreach ($godine as $g)
                <option value="{{ $g }}" @selected($g === $godina)>{{ $g }}</option>
            @endforeach
        </select>
    </div>
</div>
