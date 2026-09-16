<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | These values must match the parameters the frontend uses when it generates
    | the topic-view token (NEXT_PUBLIC_MEMORYSIZE / _ITERATIONS / _PARALLELISM),
    | because IncreaseTopicViewCountListener reconstructs the encoded hash header
    | from them before verifying.
    |
    */

    'argon' => [
        'memory' => env('HASH_MEMORY_COST', 65536),
        'threads' => env('HASH_PARALLELISM_FACTOR', 1),
        'time' => env('HASH_ITERATION', 4),
        'verify' => true,
    ],

];
