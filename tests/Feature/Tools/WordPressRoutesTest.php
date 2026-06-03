<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\WordPressRoutes;

beforeEach(function () {
    Route::get('/api/test', fn () => 'ok')->name('api.test');
    Route::post('/contact', fn () => 'ok')->name('contact.store');
});

it('lists registered laravel routes', function () {
    Nectar::tool(WordPressRoutes::class)
        ->assertOk()
        ->assertSee('api/test')
        ->assertSee('contact');
});

it('includes route methods', function () {
    Nectar::tool(WordPressRoutes::class)
        ->assertOk()
        ->assertSee('GET')
        ->assertSee('POST');
});

it('includes summary with route counts', function () {
    Nectar::tool(WordPressRoutes::class)
        ->assertOk()
        ->assertSee('total')
        ->assertSee('wordpress_routes')
        ->assertSee('laravel_routes');
});
