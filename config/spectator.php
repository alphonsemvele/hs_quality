<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Spec Source
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default spec source that should be used
    | by the framework.
    |
    */

    'default' => env('SPEC_SOURCE', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many sources as you wish, and you
    | may even configure multiple source of the same type. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'sources' => [
        'local' => [
            'source' => 'local',
            'base_path' => env('SPEC_PATH'),
        ],

        'remote' => [
            'source' => 'remote',
            'base_path' => env('SPEC_PATH'),
            'params' => env('SPEC_URL_PARAMS', ''),
        ],

        'github' => [
            'source' => 'github',
            'base_path' => env('SPEC_GITHUB_PATH'),
            'repo' => env('SPEC_GITHUB_REPO'),
            'token' => env('SPEC_GITHUB_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Configure path defaults, like prefixes.
    |
    */

    // Scramble generates paths as /v1/... (no /api prefix). Spectator strips
    // this prefix from the actual request path before matching the spec.
    'path_prefix' => env('SPEC_PATH_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Error Format
    |--------------------------------------------------------------------------
    |
    | Controls the format of schema validation error messages in test output.
    | Use 'text' (default) for human-readable coloured terminal output, or
    | 'json' for machine-readable output suited to CI log parsers and LLMs.
    |
    | You can also call Spectator::useJsonErrors() / useTextErrors() per-test.
    |
    */

    'error_format' => env('SPECTATOR_ERROR_FORMAT', 'text'),

    /*
    |--------------------------------------------------------------------------
    |
    | Specify the groups that spectator's middleware should be prepended to.
    |
    */

    'middleware_groups' => ['api'],
];
