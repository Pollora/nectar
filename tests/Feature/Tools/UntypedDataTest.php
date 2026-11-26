<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Mcp\Tools\ModulesInfo;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;
use Pollora\Nectar\Mcp\Tools\WpOption;
use Pollora\Nectar\Tests\Helpers\FakeDiscovery;

/*
 * What the tools do with application data that does not have the expected shape.
 */

enum NectarUntypedSeconds: int
{
    case Hour = 3600;
}

enum NectarUntypedFrequency
{
    case Weekly;
}

/**
 * @param  array<string, mixed>  $arguments
 */
function untypedDataPayload(object $tool, array $arguments = []): mixed
{
    $content = (string) $tool->handle(new Request($arguments))->content();

    return json_decode($content, true) ?? $content;
}

/**
 * The details discovered_components gives a schedule declared with this recurrence.
 *
 * @return array<string, mixed>
 */
function untypedScheduleDetails(mixed $recurrence): array
{
    $attribute = Mockery::mock();
    $attribute->shouldReceive('newInstance')->andReturn((object) ['hook' => 'job', 'recurrence' => $recurrence]);

    app()->instance(DiscoveryManager::class, FakeDiscovery::manager(['schedules' => [['attribute' => $attribute]]]));

    return untypedDataPayload(new DiscoveredComponents, ['type' => 'schedule'])['components']['schedule'][0]['details'];
}

afterEach(function (): void {
    app()->forgetInstance(DiscoveryManager::class);
    @unlink(base_path('composer.lock'));
});

it('shows a recurrence declared as an enum or a stringable', function (mixed $recurrence, string $expected): void {
    expect(untypedScheduleDetails($recurrence))->toBe(['hook' => 'job', 'recurrence' => $expected]);
})->with([
    'integer-backed enum' => [NectarUntypedSeconds::Hour, '3600'],
    'pure enum' => [NectarUntypedFrequency::Weekly, 'Weekly'],
    'stringable' => [new class implements Stringable
    {
        public function __toString(): string
        {
            return 'fortnightly';
        }
    }, 'fortnightly'],
]);

it('gives no details for a recurrence that has no text', function (): void {
    expect(untypedScheduleDetails(new stdClass))->toBe([]);
});

it('lists every component type when the type filter is empty', function (): void {
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager());

    expect(array_keys(untypedDataPayload(new DiscoveredComponents, ['type' => ''])['components']))
        ->toBe(['post_type', 'taxonomy', 'hook', 'schedule', 'rest_route']);
});

it('refuses a type filter that is not text', function (): void {
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager());

    expect(untypedDataPayload(new DiscoveredComponents, ['type' => ['hook']]))->toStartWith('Invalid type.');
});

it('reports no Pollora package from a composer.lock without a package list', function (string $lock): void {
    file_put_contents(base_path('composer.lock'), $lock);

    $payload = untypedDataPayload(new PolloraStatus);

    expect($payload['pollora_version'])->toBe('unknown')
        ->and($payload['pollora_packages'])->toBe([]);
})->with([
    'no packages key' => ['{}'],
    'packages is not a list' => ['{"packages": "none"}'],
    'not an object' => ['"lock"'],
    'not JSON' => ['<<<<<<< HEAD'],
]);

it('skips the composer.lock entries without a text name and version, and reads the others', function (): void {
    file_put_contents(base_path('composer.lock'), json_encode(['packages' => [
        'garbage',
        ['version' => 'v1.0.0'],
        ['name' => 'pollora/unversioned'],
        ['name' => 'pollora/numbered', 'version' => 2],
        ['name' => 'pollora/framework', 'version' => 'v13.4.0'],
    ]]));

    $payload = untypedDataPayload(new PolloraStatus);

    expect($payload['pollora_version'])->toBe('v13.4.0')
        ->and($payload['pollora_packages'])->toBe([['name' => 'pollora/framework', 'version' => 'v13.4.0']]);
});

it('reads a module without the expected methods as empty and unknown', function (): void {
    app()->instance('modules', new class
    {
        /**
         * @return list<object>
         */
        public function all(): array
        {
            return [new stdClass];
        }
    });

    expect(untypedDataPayload(new ModulesInfo)['modules'])->toBe([
        '' => ['name' => '', 'path' => '', 'enabled' => null],
    ]);
});

it('lists no module when the module manager cannot list them', function (): void {
    app()->instance('modules', new stdClass);

    expect(untypedDataPayload(new ModulesInfo))->toBe(['available' => true, 'count' => 0, 'modules' => []]);
});

it('refuses a WordPress security key even with spaces around its name', function (): void {
    expect(untypedDataPayload(new WpOption, ['key' => ' nonce_key ']))->toBeString()->toContain('looks like a secret');
});

it('reads a secret-looking option allowed by a single name, spaces and case aside', function (): void {
    config()->set('nectar.wp_option.allow', ' My_Api_Key ');

    expect(untypedDataPayload(new WpOption, ['key' => ' my_api_key '])['key'])->toBe(' my_api_key ');
});

it('ignores the allow-list entries that are not an option name', function (): void {
    config()->set('nectar.wp_option.allow', [null, 12, '  ', 'my_api_key']);

    expect(untypedDataPayload(new WpOption, ['key' => 'my_api_key'])['key'])->toBe('my_api_key');
});
