<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\WordPressInfo;

it('returns wordpress information', function () {
    Nectar::tool(WordPressInfo::class)
        ->assertOk()
        ->assertSee('6.9.0')
        ->assertSee('https://example.com')
        ->assertSee('en_US')
        ->assertSee('WooCommerce');
});

it('includes plugin active status', function () {
    Nectar::tool(WordPressInfo::class)
        ->assertOk()
        ->assertSee('WooCommerce')
        ->assertSee('Akismet');
});

it('reports multisite status', function () {
    Nectar::tool(WordPressInfo::class)
        ->assertOk()
        ->assertSee('false');
});
