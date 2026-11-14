<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('starts the MCP server registered under the pollora-nectar handle', function (): void {
    $received = null;

    // Replaces the real mcp:start, which would block on stdio.
    Artisan::command('mcp:start {handle}', function (string $handle) use (&$received): int {
        $received = $handle;

        return 7;
    });

    $this->artisan('nectar:mcp')->assertExitCode(7);

    expect($received)->toBe('pollora-nectar');
});
