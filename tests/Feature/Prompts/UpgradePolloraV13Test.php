<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV13\UpgradePolloraV13;
use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV1332\UpgradePolloraV1332;

/**
 * The framework version the test environment resolved, as the prompts read it.
 */
function installedFrameworkVersion(): ?string
{
    $version = InstalledVersions::getPrettyVersion('pollora/framework');

    if ($version === null || str_starts_with($version, 'dev-')) {
        $version = InstalledVersions::getVersion('pollora/framework');
    }

    return $version === null ? null : ltrim($version, 'vV');
}

it('renders the 12.x to 13.x upgrade guide', function (): void {
    $guide = (string) (new UpgradePolloraV13)->handle()->content();

    expect($guide)->not->toBeEmpty()
        ->and($guide)->not->toContain('@verbatim');
});

it('is offered only while the installed framework is a 12.x release', function (): void {
    $version = installedFrameworkVersion();

    expect($version)->not->toBeNull();

    $is12 = version_compare($version, '12.0.0', '>=') && version_compare($version, '13.0.0', '<');

    expect((new UpgradePolloraV13)->shouldRegister())->toBe($is12);
});

it('is offered for the 13.32 upgrade only while the installed framework is older than 13.32', function (): void {
    $version = installedFrameworkVersion();

    $expected = version_compare($version, '13.0.0', '>=') && version_compare($version, '13.32.0-dev', '<');

    expect((new UpgradePolloraV1332)->shouldRegister())->toBe($expected);
});
