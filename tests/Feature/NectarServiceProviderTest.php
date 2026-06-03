<?php

declare(strict_types=1);

it('registers nectar as enabled in local environment', function () {
    expect(config('nectar.enabled'))->toBeTrue();
});

it('merges config with default values', function () {
    expect(config('nectar.mcp.tools.exclude'))->toBe([]);
    expect(config('nectar.mcp.tools.include'))->toBe([]);
});

it('has wp_cli allowed commands configured', function () {
    $commands = config('nectar.wp_cli.allowed_commands');

    expect($commands)->toBeArray()
        ->toContain('core version')
        ->toContain('plugin list')
        ->toContain('option get');
});
