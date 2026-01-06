<?php

return [

    'title' => 'Верификујте е-пошту',

    'heading' => 'Верификујте вашу е-пошту',

    'actions' => [

        'resend_notification' => [
            'label' => 'Пошаљи поново',
        ],

    ],

    'messages' => [
        'notification_not_received' => 'Нисте примили е-пошту коју смо послали?',
        'notification_sent' => 'Послали смо е-пошту на :email са инструкцијама како да верификујете вашу адресу.',
    ],

    'notifications' => [

        'notification_resent' => [
            'title' => 'Поново смо послали е-пошту.',
        ],

        'notification_resend_throttled' => [
            'title' => 'Превише покушаја слања',
            'body' => 'Покушајте поново за :seconds секунди.',
        ],

    ],

];
