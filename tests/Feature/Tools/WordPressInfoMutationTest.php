<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Tools\WordPressInfo;

/**
 * @return array<string, mixed>
 */
function wordPressInfoPayload(): array
{
    return json_decode((string) (new WordPressInfo)->handle(new Request([]))->content(), true);
}

/**
 * The constants the tool reports, with a distinct value each.
 *
 * @return array<string, mixed>
 */
function wordPressInfoConstants(): array
{
    $constants = [
        'WP_DEBUG' => true,
        'WP_DEBUG_LOG' => false,
        'MULTISITE' => false,
        'WP_ALLOW_MULTISITE' => true,
        'DISALLOW_FILE_MODS' => true,
        'DISALLOW_FILE_EDIT' => false,
        'WP_AUTO_UPDATE_CORE' => 'minor',
        'DISABLE_WP_CRON' => true,
        'WP_MEMORY_LIMIT' => '64M',
        'WP_MAX_MEMORY_LIMIT' => '256M',
        'WP_POST_REVISIONS' => 5,
        'AUTOSAVE_INTERVAL' => 120,
        'WP_CONTENT_DIR' => '/tmp/wordpress/wp-content',
    ];

    foreach ($constants as $name => $value) {
        if (! defined($name)) {
            define($name, $value);
        }
    }

    return array_combine(array_keys($constants), array_map(constant(...), array_keys($constants)));
}

beforeEach(function (): void {
    $GLOBALS['nectar_test_options'] = [];
});

afterEach(function (): void {
    unset($GLOBALS['nectar_test_options'], $GLOBALS['nectar_test_plugins'], $GLOBALS['nectar_test_template'], $GLOBALS['nectar_test_missing_options']);
});

it('reports every key with its value', function (): void {
    $payload = wordPressInfoPayload();

    expect($payload)->toBe([
        'version' => '6.9.0',
        'site_url' => 'https://example.com',
        'home_url' => 'https://example.com',
        'is_multisite' => false,
        'active_theme' => 'test-theme',
        'parent_theme' => null,
        'plugins' => [
            ['name' => 'WooCommerce', 'version' => '9.0.0', 'active' => true],
            ['name' => 'Akismet Anti-spam', 'version' => '5.3', 'active' => false],
        ],
        'constants' => $payload['constants'],
        'locale' => 'en_US',
        'permalink_structure' => '/%postname%/',
    ]);
});

it('reports each of the thirteen WordPress constants that is defined', function (): void {
    $expected = wordPressInfoConstants();

    expect(wordPressInfoPayload()['constants'])->toBe($expected);
});

it('names the parent theme only when the template differs from the stylesheet', function (): void {
    $GLOBALS['nectar_test_template'] = 'parent-theme';

    $payload = wordPressInfoPayload();

    expect($payload['active_theme'])->toBe('test-theme')
        ->and($payload['parent_theme'])->toBe('parent-theme');
});

it('falls back to the file name and to an unknown version for a plugin without headers', function (): void {
    $GLOBALS['nectar_test_plugins'] = ['bare/bare.php' => []];

    expect(wordPressInfoPayload()['plugins'])->toBe([
        ['name' => 'bare/bare.php', 'version' => 'unknown', 'active' => false],
    ]);
});

it('marks a plugin active when the active_plugins option lists its file', function (): void {
    $GLOBALS['nectar_test_options'] = ['active_plugins' => ['akismet/akismet.php']];

    $active = array_column(wordPressInfoPayload()['plugins'], 'active', 'name');

    expect($active)->toBe(['WooCommerce' => false, 'Akismet Anti-spam' => true]);
});

it('reads a single active plugin stored as a string', function (): void {
    $GLOBALS['nectar_test_options'] = ['active_plugins' => 'akismet/akismet.php'];

    $active = array_column(wordPressInfoPayload()['plugins'], 'active', 'name');

    expect($active)->toBe(['WooCommerce' => false, 'Akismet Anti-spam' => true]);
});

it('compares plugin files strictly', function (): void {
    $GLOBALS['nectar_test_plugins'] = ['0' => ['Name' => 'Zero']];
    $GLOBALS['nectar_test_options'] = ['active_plugins' => [0]];

    expect(wordPressInfoPayload()['plugins'][0]['active'])->toBeFalse();
});

it('reports the permalink structure of the site, empty when plain', function (): void {
    $GLOBALS['nectar_test_options'] = ['permalink_structure' => '/%year%/%postname%/'];

    expect(wordPressInfoPayload()['permalink_structure'])->toBe('/%year%/%postname%/');
});

it('reports an empty permalink structure when the option does not exist', function (): void {
    $GLOBALS['nectar_test_missing_options'] = ['permalink_structure'];

    expect(wordPressInfoPayload()['permalink_structure'])->toBe('');
});
