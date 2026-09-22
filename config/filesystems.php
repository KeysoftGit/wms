<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'import_excel' => [
            'driver' => 'local',
            'root' => public_path('storage/imports'),
            'url' => env('APP_URL').'/storage/imports',
            'visibility' => 'public',
        ],

        'company_logo' => [
            'driver' => 'local',
            'root' => public_path('storage/company'),
            'url' => env('APP_URL').'/storage/company',
            'visibility' => 'public',
        ],

        'employee_photo' => [
            'driver' => 'local',
            'root' => public_path('storage/employee'),
            'url' => env('APP_URL').'/storage/employee',
            'visibility' => 'public',
        ],

        'customer_person' => [
            'driver' => 'local',
            'root' => public_path('storage/customer/person'),
            'url' => env('APP_URL').'/storage/customer/person',
            'visibility' => 'public',
        ],

        'customer_location' => [
            'driver' => 'local',
            'root' => public_path('storage/customer/location'),
            'url' => env('APP_URL').'/storage/customer/location',
            'visibility' => 'public',
        ],

        'customer_id' => [
            'driver' => 'local',
            'root' => public_path('storage/customer/id'),
            'url' => env('APP_URL').'/storage/customer/id',
            'visibility' => 'public',
        ],

        'inventory_part' => [
            'driver' => 'local',
            'root' => public_path('storage/part'),
            'url' => env('APP_URL').'/storage/part',
            'visibility' => 'public',
        ],

        'fixed_asset' => [
            'driver' => 'local',
            'root' => public_path('storage/fixedasset'),
            'url' => env('APP_URL').'/storage/fixedasset',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
