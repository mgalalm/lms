<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
            // Flysystem's local adapter defaults new dirs/files to 0700, which
            // locks out the www-data group entirely. Horizon runs as deployuser
            // (a www-data group member), so files php-fpm (www-data) writes here
            // — e.g. lesson-video-uploads, livewire-tmp — must stay group-readable
            // or the queued PushLessonVideoToBunnyJob can't read them back.
            'permissions' => [
                'file' => [
                    'private' => 0660,
                ],
                'dir' => [
                    'private' => 02770,
                ],
            ],
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
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
            'throw' => false,
            'report' => false,
        ],

        // Bunny Storage (not Bunny Stream, which lesson videos use) —
        // accessed via its S3-compatible API. Browser-facing files only:
        // lesson content images and slideshow PDFs.
        'bunny' => [
            'driver' => 's3',
            'key' => env('BUNNY_S3_ACCESS_KEY'),
            'secret' => env('BUNNY_S3_SECRET_KEY'),
            'region' => 'us-east-1',
            'bucket' => env('BUNNY_S3_BUCKET'),
            'url' => env('BUNNY_CDN_URL'),
            'endpoint' => env('BUNNY_S3_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
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
