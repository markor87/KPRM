<?php

return [

    'title' => 'Регистрација',

    'heading' => 'Региструјте се',

    'actions' => [

        'login' => [
            'before' => 'или',
            'label' => 'пријавите се на свој налог',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Е-пошта',
        ],

        'name' => [
            'label' => 'Име',
        ],

        'password' => [
            'label' => 'Лозинка',
            'validation_attribute' => 'лозинка',
        ],

        'password_confirmation' => [
            'label' => 'Потврди лозинку',
        ],

        'actions' => [

            'register' => [
                'label' => 'Региструј се',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Превише покушаја регистрације',
            'body' => 'Покушајте поново за :seconds секунди.',
        ],

    ],

];
