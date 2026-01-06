<?php

return [

    'column_toggle' => [

        'heading' => 'Колоне',

    ],

    'columns' => [

        'actions' => [
            'label' => 'Акција|Акције',
        ],

        'text' => [

            'actions' => [
                'collapse_list' => 'Прикажи :count мање',
                'expand_list' => 'Прикажи :count више',
            ],

            'more_list_items' => 'и још :count',

        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Означи/одзначи све ставке за групне акције.',
        ],

        'bulk_select_record' => [
            'label' => 'Означи/одзначи ставку :key за групне акције.',
        ],

        'bulk_select_group' => [
            'label' => 'Означи/одзначи групу :title за групне акције.',
        ],

        'search' => [
            'label' => 'Претрага',
            'placeholder' => 'Претрага',
            'indicator' => 'Претрага',
        ],

    ],

    'summary' => [

        'heading' => 'Сажетак',

        'subheadings' => [
            'all' => 'Сви :label',
            'group' => ':group сажетак',
            'page' => 'Ова страница',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Просек',
            ],

            'count' => [
                'label' => 'Број',
            ],

            'sum' => [
                'label' => 'Збир',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Заврши поређање записа',
        ],

        'enable_reordering' => [
            'label' => 'Поређај записе',
        ],

        'filter' => [
            'label' => 'Филтер',
        ],

        'group' => [
            'label' => 'Група',
        ],

        'open_bulk_actions' => [
            'label' => 'Групне акције',
        ],

        'toggle_columns' => [
            'label' => 'Промени колоне',
        ],

    ],

    'empty' => [

        'heading' => 'Нема :model',

        'description' => 'Креирајте :model да бисте почели.',

    ],

    'filters' => [

        'actions' => [

            'apply' => [
                'label' => 'Примени филтере',
            ],

            'remove' => [
                'label' => 'Уклони филтер',
            ],

            'remove_all' => [
                'label' => 'Уклони све филтере',
                'tooltip' => 'Уклони све филтере',
            ],

            'reset' => [
                'label' => 'Ресетуј',
            ],

        ],

        'heading' => 'Филтери',

        'indicator' => 'Активни филтери',

        'multi_select' => [
            'placeholder' => 'Сви',
        ],

        'select' => [
            'placeholder' => 'Сви',
        ],

        'trashed' => [

            'label' => 'Обрисани записи',

            'only_trashed' => 'Само обрисани записи',

            'with_trashed' => 'Са обрисаним записима',

            'without_trashed' => 'Без обрисаних записа',

        ],

    ],

    'grouping' => [

        'fields' => [

            'group' => [
                'label' => 'Групиши по',
                'placeholder' => 'Групиши по',
            ],

            'direction' => [

                'label' => 'Смер групе',

                'options' => [
                    'asc' => 'Растуће',
                    'desc' => 'Опадајуће',
                ],

            ],

        ],

    ],

    'reorder_indicator' => 'Превуците и испустите записе по редоследу.',

    'selection_indicator' => [

        'selected_count' => '1 запис означен|:count записа означено',

        'actions' => [

            'select_all' => [
                'label' => 'Означи све :count',
            ],

            'deselect_all' => [
                'label' => 'Одзначи све',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Сортирај по',
            ],

            'direction' => [

                'label' => 'Смер сортирања',

                'options' => [
                    'asc' => 'Растуће',
                    'desc' => 'Опадајуће',
                ],

            ],

        ],

    ],

];
