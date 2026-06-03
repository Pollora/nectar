<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\ModulesInfo;

it('reports modules as unavailable when not installed', function () {
    Nectar::tool(ModulesInfo::class)
        ->assertOk()
        ->assertSee('false')
        ->assertSee('not installed');
});

it('reports modules when manager is bound', function () {
    $module = Mockery::mock();
    $module->shouldReceive('getName')->andReturn('Blog');
    $module->shouldReceive('getPath')->andReturn(base_path('Modules/Blog'));
    $module->shouldReceive('isEnabled')->andReturn(true);

    $manager = Mockery::mock();
    $manager->shouldReceive('all')->andReturn([$module]);

    app()->instance('modules', $manager);

    Nectar::tool(ModulesInfo::class)
        ->assertOk()
        ->assertSee('Blog')
        ->assertSee('true');
});
