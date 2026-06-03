<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;

beforeEach(function () {
    $composerLock = json_encode([
        'packages' => [
            ['name' => 'pollora/framework', 'version' => 'v13.4.0'],
            ['name' => 'pollora/nectar', 'version' => 'v0.1.0'],
            ['name' => 'laravel/framework', 'version' => 'v13.0.0'],
        ],
    ]);

    file_put_contents(base_path('composer.lock'), $composerLock);
});

afterEach(function () {
    @unlink(base_path('composer.lock'));
    @unlink(base_path('bootstrap/cache/discovery.php'));
    @rmdir(base_path('bootstrap/cache'));
    @rmdir(base_path('bootstrap'));
});

it('returns framework status with all expected keys', function () {
    @mkdir(base_path('bootstrap/cache'), 0777, true);
    file_put_contents(base_path('bootstrap/cache/discovery.php'), '<?php return [];');

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('v13.4.0')
        ->assertSee('6.9.0');
});

it('reports pollora packages from composer.lock', function () {
    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('pollora/framework')
        ->assertSee('pollora/nectar')
        ->assertDontSee('"laravel/framework"');
});

it('reports discovery cache as not cached when file missing', function () {
    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('false');
});
