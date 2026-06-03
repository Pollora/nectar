<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\TaxonomiesInfo;

it('lists custom taxonomies excluding builtins by default', function () {
    Nectar::tool(TaxonomiesInfo::class)
        ->assertOk()
        ->assertSee('genre')
        ->assertDontSee('category');
});

it('includes builtins when requested', function () {
    Nectar::tool(TaxonomiesInfo::class, ['include_builtin' => true])
        ->assertOk()
        ->assertSee('genre')
        ->assertSee('category');
});

it('filters by taxonomy slug', function () {
    Nectar::tool(TaxonomiesInfo::class, ['filter' => 'genre'])
        ->assertOk()
        ->assertSee('genre')
        ->assertSee('book');
});

it('returns taxonomy details', function () {
    Nectar::tool(TaxonomiesInfo::class, ['filter' => 'genre'])
        ->assertOk()
        ->assertSee('Genres')
        ->assertSee('Genre')
        ->assertSee('true');
});
