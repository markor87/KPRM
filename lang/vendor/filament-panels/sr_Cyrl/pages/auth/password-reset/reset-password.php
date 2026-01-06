<?php

return [

    'title' => 'Ресетујте лозинку',

    'heading' => 'Ресетујте лозинку',

    'form' => [

        'email' => [
            'label' => 'Е-пошта',
        ],

        'password' => [
            'label' => 'Лозинка',
            'validation_attribute' => 'лозинка',
        ],

        'password_confirmation' => [
            'label' => 'Потврди лозинку',
        ],

        'actions' => [

            'reset' => [
                'label' => 'Ресетуј лозинку',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Превише покушаја ресетовања',
            'body' => 'Покушајте поново за :seconds секунди.',
        ],

    ],

];
