<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Tools\ScheduledEvents;

/**
 * @return array<string, mixed>
 */
function placementPayload(): array
{
    return json_decode((string) (new ScheduledEvents)->handle(new Request([]))->content(), true);
}

beforeEach(function (): void {
    $GLOBALS['nectar_test_options'] = [];
    config(['schedule.protected_hooks' => [], 'schedule.driver' => 'wp-cron']);
});

afterEach(function (): void {
    unset($GLOBALS['nectar_test_cron'], $GLOBALS['nectar_test_options']);
});

it('keeps a wp-cron event where it is when wp-cron is the configured driver', function (): void {
    $GLOBALS['nectar_test_cron'] = [time() + 60 => ['tidy' => [md5(serialize([])) => ['schedule' => 'hourly', 'args' => [], 'interval' => 3600]]]];

    $payload = placementPayload();

    expect($payload['configured_driver'])->toBe('wp-cron')
        ->and($payload['driver_available'])->toBeTrue()
        ->and($payload['events'][0]['placement'])->toBe('active');
});

it('falls back to wp-cron while the configured laravel driver cannot run', function (): void {
    config([
        'schedule.driver' => 'laravel',
        'queue.default' => 'sync',
        'queue.connections.sync' => ['driver' => 'sync'],
    ]);
    $GLOBALS['nectar_test_cron'] = [time() + 60 => ['tidy' => [md5(serialize([])) => ['schedule' => 'hourly', 'args' => [], 'interval' => 3600]]]];

    $payload = placementPayload();

    expect($payload['driver_available'])->toBeFalse()
        ->and($payload['events'][0]['placement'])->toBe('fallback');
});

it('ignores cron entries that are not shaped like events', function (): void {
    $GLOBALS['nectar_test_cron'] = [
        'version' => 2,
        time() + 60 => 'not a list of hooks',
        time() + 120 => ['tidy' => ['broken-entry' => 'not an array', md5(serialize([])) => ['schedule' => false, 'args' => []]]],
    ];

    $payload = placementPayload();

    expect($payload['count'])->toBe(1)
        ->and($payload['events'][0]['hook'])->toBe('tidy');
});
