<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\WpOption;

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
