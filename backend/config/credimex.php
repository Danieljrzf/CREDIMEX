<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CREDIMEX — Base de datos
    |--------------------------------------------------------------------------
    |
    | Configuración no secreta del esquema y del rol de aplicación receptor
    | de privilegios explícitos. Las migraciones y el código de aplicación
    | deben leer config('credimex.database.app_role'), nunca env().
    |
    */

    'database' => [

        'schema' => 'credimex',

        'app_role' => env('DB_APP_ROLE'),

        'environments' => [

            'local' => [
                'database' => 'credimex_dev',
                'owner_user' => 'credimex_owner',
                'app_user' => 'credimex_app',
            ],

            'testing' => [
                'database' => 'credimex_test',
                'owner_user' => 'credimex_test_owner',
                'app_user' => 'credimex_test_app',
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | CREDIMEX — Autenticación API
    |--------------------------------------------------------------------------
    |
    | Duración inicial de la sesión móvil. El servicio lee esta clave;
    | el valor no se duplica en el código de login.
    |
    */

    'auth' => [

        'sesion_ttl_minutos' => 1440,

    ],

];
