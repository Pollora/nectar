<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\WpOption;

afterEach(function (): void {
    unset($GLOBALS['nectar_test_options']);
});

it('returns existing option value', function () {
    Nectar::tool(WpOption::class, ['key' => 'blogname'])
        ->assertOk()
        ->assertSee('Test Blog')
        ->assertSee('true');
});

it('reports non-existing option', function () {
    Nectar::tool(WpOption::class, ['key' => 'nonexistent_option_key'])
        ->assertOk()
        ->assertSee('false');
});

it('returns permalink structure', function () {
    Nectar::tool(WpOption::class, ['key' => 'permalink_structure'])
        ->assertOk()
        ->assertSee('/%postname%/');
});

it('requires key parameter', function () {
    Nectar::tool(WpOption::class)
        ->assertOk()
        ->assertSee('required');
});

// Placeholders below are deliberately low-entropy words: they stand for secrets, they are not any.

it('refuses an option whose name looks like a secret and says why and how to allow it', function (string $key) {
    $GLOBALS['nectar_test_options'] = [$key => 'placeholder-value'];

    Nectar::tool(WpOption::class, ['key' => $key])
        ->assertOk()
        ->assertSee('looks like a secret')
        ->assertSee('wp_option.allow')
        ->assertDontSee('placeholder-value');
})->with([
    'password' => 'mailserver_pass',
    'password long' => 'smtp_password',
    'secret' => 'client_secret',
    'token' => 'access_token',
    'api key' => 'stripe_api_key',
    'api-key' => 'service-api-key',
    'apikey' => 'maps_apikey',
    'private key' => 'signing_private_key',
    'salt' => 'custom_salt',
    'auth' => 'oauth_state',
    'credential' => 'ftp_credentials',
    'license' => 'plugin_license_key',
    'auth_key' => 'auth_key',
    'secure_auth_key' => 'secure_auth_key',
    'logged_in_key' => 'logged_in_key',
    'nonce_key' => 'nonce_key',
    'recovery_keys' => 'recovery_keys',
    'nonce_salt' => 'nonce_salt',
]);

it('matches the sensitive names whatever their case', function (string $key) {
    $GLOBALS['nectar_test_options'] = [$key => 'placeholder-value'];

    Nectar::tool(WpOption::class, ['key' => $key])
        ->assertOk()
        ->assertSee('looks like a secret')
        ->assertDontSee('placeholder-value');
})->with(['API_KEY', 'My_Password', 'LOGGED_IN_KEY', 'Recovery_Keys']);

it('does not reveal whether a refused option exists', function () {
    $GLOBALS['nectar_test_options'] = [];

    Nectar::tool(WpOption::class, ['key' => 'absent_secret'])
        ->assertOk()
        ->assertSee('looks like a secret')
        ->assertDontSee('exists');
});

it('returns an option that is allowed by its exact name', function () {
    $GLOBALS['nectar_test_options'] = ['stripe_api_key' => 'placeholder-value'];
    config(['nectar.wp_option.allow' => ['stripe_api_key']]);

    Nectar::tool(WpOption::class, ['key' => 'stripe_api_key'])
        ->assertOk()
        ->assertSee('placeholder-value')
        ->assertSee('true');
});

it('compares the allowed name regardless of case and surrounding spaces', function () {
    $GLOBALS['nectar_test_options'] = ['Stripe_Api_Key' => 'placeholder-value'];
    config(['nectar.wp_option.allow' => [' STRIPE_API_KEY ']]);

    Nectar::tool(WpOption::class, ['key' => 'Stripe_Api_Key'])
        ->assertOk()
        ->assertSee('placeholder-value');
});

it('never treats an allowed name as a pattern', function (string $allowed) {
    $GLOBALS['nectar_test_options'] = ['stripe_api_key' => 'placeholder-value'];
    config(['nectar.wp_option.allow' => [$allowed, 42, '']]);

    Nectar::tool(WpOption::class, ['key' => 'stripe_api_key'])
        ->assertOk()
        ->assertSee('looks like a secret')
        ->assertDontSee('placeholder-value');
})->with(['*', 'stripe_*', '.*', 'stripe_api', 'stripe_api_key_other']);

it('masks the sensitive sub-keys of an array option at any depth', function () {
    $GLOBALS['nectar_test_options'] = ['plugin_settings' => [
        'endpoint' => 'https://api.example.com',
        'API_KEY' => 'placeholder-one',
        'nested' => ['timeout' => 30, 'client-secret' => 'placeholder-two', 'list' => [['token' => 'placeholder-three', 'label' => 'ok']]],
        'password' => ['placeholder-four'],
        7 => 'seventh',
    ]];

    $response = Nectar::tool(WpOption::class, ['key' => 'plugin_settings'])
        ->assertOk()
        ->assertSee('https://api.example.com')
        ->assertSee('30')
        ->assertSee('seventh')
        ->assertSee('label')
        ->assertSee('[masqué]');

    foreach (['placeholder-one', 'placeholder-two', 'placeholder-three', 'placeholder-four'] as $secret) {
        $response->assertDontSee($secret);
    }
});

it('masks the sensitive properties of an object option', function () {
    $GLOBALS['nectar_test_options'] = ['plugin_object' => (object) ['mode' => 'live', 'auth_token' => 'placeholder-value']];

    Nectar::tool(WpOption::class, ['key' => 'plugin_object'])
        ->assertOk()
        ->assertSee('live')
        ->assertSee('[masqué]')
        ->assertDontSee('placeholder-value');
});

it('keeps masking the sub-keys of an option that is allowed', function () {
    $GLOBALS['nectar_test_options'] = ['plugin_license_settings' => ['plan' => 'pro', 'license' => 'placeholder-value']];
    config(['nectar.wp_option.allow' => ['plugin_license_settings']]);

    Nectar::tool(WpOption::class, ['key' => 'plugin_license_settings'])
        ->assertOk()
        ->assertSee('pro')
        ->assertSee('[masqué]')
        ->assertDontSee('placeholder-value');
});

it('leaves an ordinary option unchanged', function () {
    $GLOBALS['nectar_test_options'] = ['plugin_layout' => ['columns' => 3, 'title' => 'Home', 'enabled' => true]];

    Nectar::tool(WpOption::class, ['key' => 'plugin_layout'])
        ->assertOk()
        ->assertSee('"columns":3')
        ->assertSee('Home')
        ->assertDontSee('[masqué]');
});

it('rejects a key that is not a string', function () {
    Nectar::tool(WpOption::class, ['key' => ['blogname']])
        ->assertOk()
        ->assertSee('required');
});
