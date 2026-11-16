<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV13\UpgradePolloraV13;
use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV1332\UpgradePolloraV1332;

/**
 * Make Composer report the given framework versions, whatever is installed.
 *
 * @param  array<string, string>  $entry  The "pretty_version" and "version" the package carries; empty for a package without either
 */
function frameworkInstalledAs(array $entry): void
{
    $property = new ReflectionProperty(InstalledVersions::class, 'installedByVendor');
    $fake = [
        'root' => ['name' => 'app', 'pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'reference' => null, 'type' => 'project', 'install_path' => __DIR__, 'aliases' => [], 'dev' => true],
        'versions' => ['pollora/framework' => $entry + ['dev_requirement' => false]],
    ];
    $byVendor = [];

    foreach (array_keys(ClassLoader::getRegisteredLoaders()) as $vendorDir) {
        $byVendor[strtr($vendorDir, '\\', '/')] = $fake;
    }

    $property->setValue(null, $byVendor);
}

beforeEach(function (): void {
    $this->originalVendors = (new ReflectionProperty(InstalledVersions::class, 'installedByVendor'))->getValue();
});

afterEach(function (): void {
    (new ReflectionProperty(InstalledVersions::class, 'installedByVendor'))->setValue(null, $this->originalVendors);
});

it('offers the 12 to 13 upgrade from 12.0 up to, not including, 13.0', function (string $pretty, bool $expected): void {
    frameworkInstalledAs(['pretty_version' => $pretty, 'version' => ltrim($pretty, 'vV').'.0']);

    expect((new UpgradePolloraV13)->shouldRegister())->toBe($expected);
})->with([
    'before 12' => ['11.9.0', false],
    'first 12' => ['12.0.0', true],
    'prefixed with v' => ['v12.5.0', true],
    'prefixed with V' => ['V12.5.0', true],
    'first 13' => ['13.0.0', false],
]);

it('offers the 13.32 upgrade from 13.0 up to, not including, 13.32', function (string $pretty, bool $expected): void {
    frameworkInstalledAs(['pretty_version' => $pretty, 'version' => ltrim($pretty, 'vV').'.0']);

    expect((new UpgradePolloraV1332)->shouldRegister())->toBe($expected);
})->with([
    'last 12' => ['12.9.0', false],
    'first 13' => ['13.0.0', true],
    'prefixed with v' => ['v13.5.0', true],
    'prefixed with V' => ['V13.5.0', true],
    'first 13.32' => ['13.32.0', false],
]);

it('reads the numeric version when the installed framework is a branch', function (string $prompt, string $numeric, bool $expected): void {
    frameworkInstalledAs(['pretty_version' => 'dev-main', 'version' => $numeric]);

    expect((new $prompt)->shouldRegister())->toBe($expected);
})->with([
    'v13 on 12.3' => [UpgradePolloraV13::class, '12.3.0.0', true],
    'v13 on 13.1' => [UpgradePolloraV13::class, '13.1.0.0', false],
    'v13.32 on 13.10' => [UpgradePolloraV1332::class, '13.10.0.0', true],
    'v13.32 on 13.40' => [UpgradePolloraV1332::class, '13.40.0.0', false],
]);

it('trusts the pretty version over the numeric one when it is a release', function (string $prompt, bool $expected): void {
    frameworkInstalledAs(['pretty_version' => $prompt === UpgradePolloraV13::class ? 'v12.5.0' : 'v13.5.0', 'version' => '99.0.0.0']);

    expect((new $prompt)->shouldRegister())->toBe($expected);
})->with([
    'v13' => [UpgradePolloraV13::class, true],
    'v13.32' => [UpgradePolloraV1332::class, true],
]);

it('is not offered when Composer knows no version of the framework', function (string $prompt): void {
    frameworkInstalledAs([]);

    expect((new $prompt)->shouldRegister())->toBeFalse();
})->with([UpgradePolloraV13::class, UpgradePolloraV1332::class]);
