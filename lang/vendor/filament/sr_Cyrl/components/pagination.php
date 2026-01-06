<?php

return [

    'label' => 'Пагинација',

    'overview' => '{1} Приказан 1 резултат|[2,*] Приказано :first до :last од :total резултата',

    'fields' => [

        'records_per_page' => [

            'label' => 'По страници',

            'options' => [
                'all' => 'Све',
            ],

        ],

    ],

    'actions' => [

        'first' => [
            'label' => 'Прва',
        ],

        'go_to_page' => [
            'label' => 'Иди на страницу :page',
        ],

        'last' => [
            'label' => 'Последња',
        ],

        'next' => [
            'label' => 'Следећа',
        ],

        'previous' => [
            'label' => 'Претходна',
        ],

    ],

];
