<?php

declare(strict_types=1);

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
    | wp_option Tool
    |--------------------------------------------------------------------------
    |
    | The wp_option tool refuses options whose name looks like a secret (password,
    | secret, token, API key, private key, salt, auth, credential, license) and the
    | WordPress security keys, because what it returns is sent to an AI model.
    | List here, by exact name, the options that are safe to show. Never a pattern:
    | a name is allowed only if it is written in full. Secret-looking sub-keys inside
    | an array option stay masked even for an allowed option.
    |
    */

    'wp_option' => [
        'allow' => [],
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
