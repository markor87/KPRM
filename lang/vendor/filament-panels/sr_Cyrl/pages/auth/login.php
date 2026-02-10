<?php

return [

    'title' => 'Пријава',

    'heading' => 'Пријавите се',

    'actions' => [

        'register' => [
            'before' => 'или',
            'label' => 'региструјте се за налог',
        ],

        'request_password_reset' => [
            'label' => 'Заборавили сте лозинку?',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Е-пошта',
        ],

        'password' => [
            'label' => 'Лозинка',
        ],

        'remember' => [
            'label' => 'Запамти ме',
        ],

        'actions' => [

            'authenticate' => [
                'label' => 'Пријави се',
            ],

        ],

    ],

    'messages' => [

        'failed' => 'Погрешна електронска пошта или лозинка.',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Превише покушаја пријаве',
            'body' => 'Покушајте поново за :seconds секунди.',
        ],

    ],

];
