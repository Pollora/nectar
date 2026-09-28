<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV1332\UpgradePolloraV1332;

it('applies to 13.x releases before 13.32', function (string $version) {
    expect((new UpgradePolloraV1332)->appliesTo($version))->toBeTrue();
})->with(['13.0.0', 'v13.2.0', '13.4.3']);

it('does not apply to 12.x, nor to 13.32 and later, pre-releases included', function (string $version) {
    expect((new UpgradePolloraV1332)->appliesTo($version))->toBeFalse();
})->with(['12.5.0', '13.32.0-beta', '13.32.0-beta.8', '13.32.0', '13.33.0', '14.0.0']);

it('renders the upgrade guide', function () {
    $guide = (string) (new UpgradePolloraV1332)->handle()->content();

    expect($guide)
        ->toContain('patches-relock')
        ->toContain('resources/views/blocks')
        ->toContain('Pollora\\Hook\\Adapter\\Out\\WordPress')
        ->toContain('@import "tailwindcss";')
        ->toContain('`@posts`')
        ->not->toContain('@verbatim');
});
