<?php

return [

    'builder' => [

        'actions' => [

            'clone' => [
                'label' => 'Клонирај',
            ],

            'add' => [

                'label' => 'Додај у :label',

                'modal' => [

                    'heading' => 'Додај у :label',

                    'actions' => [

                        'add' => [
                            'label' => 'Додај',
                        ],

                    ],

                ],

            ],

            'add_between' => [

                'label' => 'Уметни између блокова',

                'modal' => [

                    'heading' => 'Додај у :label',

                    'actions' => [

                        'add' => [
                            'label' => 'Додај',
                        ],

                    ],

                ],

            ],

            'delete' => [
                'label' => 'Обриши',
            ],

            'edit' => [

                'label' => 'Измени',

                'modal' => [

                    'heading' => 'Измени блок',

                    'actions' => [

                        'save' => [
                            'label' => 'Сачувај промене',
                        ],

                    ],

                ],

            ],

            'reorder' => [
                'label' => 'Премести',
            ],

            'move_down' => [
                'label' => 'Премести доле',
            ],

            'move_up' => [
                'label' => 'Премести горе',
            ],

            'collapse' => [
                'label' => 'Сакриј',
            ],

            'expand' => [
                'label' => 'Прошири',
            ],

            'collapse_all' => [
                'label' => 'Сакриј све',
            ],

            'expand_all' => [
                'label' => 'Прошири све',
            ],

        ],

    ],

    'checkbox_list' => [

        'actions' => [

            'deselect_all' => [
                'label' => 'Одзначи све',
            ],

            'select_all' => [
                'label' => 'Означи све',
            ],

        ],

    ],

    'file_upload' => [

        'editor' => [

            'actions' => [

                'cancel' => [
                    'label' => 'Откажи',
                ],

                'drag_crop' => [
                    'label' => 'Режим превлачења "исецање"',
                ],

                'drag_move' => [
                    'label' => 'Режим превлачења "померање"',
                ],

                'flip_horizontal' => [
                    'label' => 'Обрни слику хоризонтално',
                ],

                'flip_vertical' => [
                    'label' => 'Обрни слику вертикално',
                ],

                'move_down' => [
                    'label' => 'Премести слику доле',
                ],

                'move_left' => [
                    'label' => 'Премести слику лево',
                ],

                'move_right' => [
                    'label' => 'Премести слику десно',
                ],

                'move_up' => [
                    'label' => 'Премести слику горе',
                ],

                'reset' => [
                    'label' => 'Ресетуј',
                ],

                'rotate_left' => [
                    'label' => 'Ротирај слику лево',
                ],

                'rotate_right' => [
                    'label' => 'Ротирај слику десно',
                ],

                'set_aspect_ratio' => [
                    'label' => 'Постави однос :ratio',
                ],

                'save' => [
                    'label' => 'Сачувај',
                ],

                'zoom_100' => [
                    'label' => 'Зумирај слику на 100%',
                ],

                'zoom_in' => [
                    'label' => 'Увећај',
                ],

                'zoom_out' => [
                    'label' => 'Умањи',
                ],

            ],

            'fields' => [

                'height' => [
                    'label' => 'Висина',
                    'unit' => 'px',
                ],

                'rotation' => [
                    'label' => 'Ротација',
                    'unit' => 'deg',
                ],

                'width' => [
                    'label' => 'Ширина',
                    'unit' => 'px',
                ],

                'x_position' => [
                    'label' => 'X',
                    'unit' => 'px',
                ],

                'y_position' => [
                    'label' => 'Y',
                    'unit' => 'px',
                ],

            ],

            'aspect_ratios' => [

                'label' => 'Односи',

                'no_fixed' => [
                    'label' => 'Слободно',
                ],

            ],

            'svg' => [

                'messages' => [
                    'confirmation' => 'Editing SVG files is not recommended as it can result in quality loss when scaling.\n Are you sure you want to continue?',
                    'disabled' => 'Уређивање SVG фајлова је онемогућено јер може довести до губитка квалитета при скалирању.',
                ],

            ],

        ],

    ],

    'key_value' => [

        'actions' => [

            'add' => [
                'label' => 'Додај ред',
            ],

            'delete' => [
                'label' => 'Обриши ред',
            ],

            'reorder' => [
                'label' => 'Поређај ред',
            ],

        ],

        'fields' => [

            'key' => [
                'label' => 'Кључ',
            ],

            'value' => [
                'label' => 'Вредност',
            ],

        ],

    ],

    'markdown_editor' => [

        'toolbar_buttons' => [
            'attach_files' => 'Приложи фајлове',
            'blockquote' => 'Цитат',
            'bold' => 'Подебљано',
            'bullet_list' => 'Листа',
            'code_block' => 'Блок кода',
            'heading' => 'Наслов',
            'italic' => 'Искошено',
            'link' => 'Линк',
            'ordered_list' => 'Нумерисана листа',
            'redo' => 'Понови',
            'strike' => 'Прецртано',
            'table' => 'Табела',
            'undo' => 'Поништи',
        ],

    ],

    'radio' => [

        'boolean' => [
            'true' => 'Да',
            'false' => 'Не',
        ],

    ],

    'repeater' => [

        'actions' => [

            'add' => [
                'label' => 'Додај у :label',
            ],

            'add_between' => [
                'label' => 'Уметни између',
            ],

            'delete' => [
                'label' => 'Обриши',
            ],

            'clone' => [
                'label' => 'Клонирај',
            ],

            'reorder' => [
                'label' => 'Премести',
            ],

            'move_down' => [
                'label' => 'Премести доле',
            ],

            'move_up' => [
                'label' => 'Премести горе',
            ],

            'collapse' => [
                'label' => 'Сакриј',
            ],

            'expand' => [
                'label' => 'Прошири',
            ],

            'collapse_all' => [
                'label' => 'Сакриј све',
            ],

            'expand_all' => [
                'label' => 'Прошири све',
            ],

        ],

    ],

    'rich_editor' => [

        'dialogs' => [

            'link' => [

                'actions' => [
                    'link' => 'Линк',
                    'unlink' => 'Уклони линк',
                ],

                'label' => 'URL',

                'placeholder' => 'Унесите URL',

            ],

        ],

        'toolbar_buttons' => [
            'attach_files' => 'Приложи фајлове',
            'blockquote' => 'Цитат',
            'bold' => 'Подебљано',
            'bullet_list' => 'Листа',
            'code_block' => 'Блок кода',
            'h1' => 'Наслов',
            'h2' => 'Наслов',
            'h3' => 'Поднаслов 3',
            'italic' => 'Искошено',
            'link' => 'Линк',
            'ordered_list' => 'Нумерисана листа',
            'redo' => 'Понови',
            'strike' => 'Прецртано',
            'underline' => 'Подвучено',
            'undo' => 'Поништи',
        ],

    ],

    'select' => [

        'actions' => [

            'create_option' => [

                'label' => 'Креирај',

                'modal' => [

                    'heading' => 'Креирај',

                    'actions' => [

                        'create' => [
                            'label' => 'Креирај',
                        ],

                        'create_another' => [
                            'label' => 'Креирај и креирај још један',
                        ],

                    ],

                ],

            ],

            'edit_option' => [

                'label' => 'Измени',

                'modal' => [

                    'heading' => 'Измени',

                    'actions' => [

                        'save' => [
                            'label' => 'Сачувај',
                        ],

                    ],

                ],

            ],

        ],

        'boolean' => [
            'true' => 'Да',
            'false' => 'Не',
        ],

        'loading_message' => 'Учитавање...',

        'max_items_message' => 'Само :count може бити изабрано.',

        'no_options_message' => 'Нема доступних опција.',

        'no_search_results_message' => 'Нема резултата који одговарају вашој претрази.',

        'placeholder' => 'Изаберите опцију',

        'searching_message' => 'Претрага...',

        'search_prompt' => 'Почните да куцате за претрагу...',

    ],

    'tags_input' => [
        'placeholder' => 'Нова ознака',
    ],

    'text_input' => [

        'actions' => [

            'hide_password' => [
                'label' => 'Сакриј лозинку',
            ],

            'show_password' => [
                'label' => 'Прикажи лозинку',
            ],

        ],

    ],

    'toggle_buttons' => [

        'boolean' => [
            'true' => 'Да',
            'false' => 'Не',
        ],

    ],

    'wizard' => [

        'actions' => [

            'previous_step' => [
                'label' => 'Назад',
            ],

            'next_step' => [
                'label' => 'Следеће',
            ],

        ],

    ],

];
