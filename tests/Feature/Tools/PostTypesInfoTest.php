<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\PostTypesInfo;

it('lists custom post types excluding builtins by default', function () {
    Nectar::tool(PostTypesInfo::class)
        ->assertOk()
        ->assertSee('book')
        ->assertSee('product')
        ->assertDontSee('"post":{"label":"Posts"');
});

it('includes builtins when requested', function () {
    Nectar::tool(PostTypesInfo::class, ['include_builtin' => true])
        ->assertOk()
        ->assertSee('book')
        ->assertSee('post');
});

it('filters by post type slug', function () {
    Nectar::tool(PostTypesInfo::class, ['filter' => 'book'])
        ->assertOk()
        ->assertSee('book')
        ->assertDontSee('product');
});

it('returns post type supports and taxonomies', function () {
    Nectar::tool(PostTypesInfo::class, ['filter' => 'book'])
        ->assertOk()
        ->assertSee('title')
        ->assertSee('editor')
        ->assertSee('thumbnail')
        ->assertSee('genre');
});
