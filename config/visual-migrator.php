<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Path
    |--------------------------------------------------------------------------
    |
    | This is the path where the visual migrator dashboard will be accessible.
    |
    */
    'path' => 'visual-migrator',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every visual migrator route.
    |
    */
    'middleware' => [
        'web',
        // 'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Migrations
    |--------------------------------------------------------------------------
    |
    | These tables will be ignored by the Visual Migrator. This prevents 
    | accidental modifications to core Laravel tables.
    |
    */
    /**
     * List of migration tables to be ignored by the visual migrator.
     * These tables will not be parsed or displayed in the diagram dashboard.
     * Useful for hiding Laravel boilerplate tables or system tables.
     */
    'ignore_migrations' => [
        'users',
        'password_reset_tokens',
        'failed_jobs',
        'personal_access_tokens',
        'cache',
        'jobs',
        'job_batches',
        'sessions',
    ],

];
