<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nectar Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Nectar's MCP server and tools are enabled.
    | When disabled, no MCP tools will be registered.
    |
    */

    'enabled' => env('NECTAR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | MCP Tools Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which MCP tools are available. You can exclude specific tools
    | or include additional custom tools.
    |
    */

    'mcp' => [
        'tools' => [
            'exclude' => [],
            'include' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WP-CLI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the WP-CLI tool behavior. The allowlist defines which WP-CLI
    | commands are safe to execute through the MCP server.
    |
    */

    'wp_cli' => [
        'allowed_commands' => [
            'core version',
            'plugin list',
            'plugin status',
            'theme list',
            'theme status',
            'option get',
            'option list',
            'post-type list',
            'taxonomy list',
            'user list',
            'cron event list',
            'rewrite list',
            'menu list',
            'sidebar list',
            'widget list',
            'db query',
        ],
    ],

];
