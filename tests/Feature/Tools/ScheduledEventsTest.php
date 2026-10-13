<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\ScheduledEvents;

/**
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function scheduledEvents(array $arguments = []): array
{
    return json_decode((string) (new ScheduledEvents)->handle(new Request($arguments))->content(), true);
}

beforeEach(function () {
    $key = md5(serialize([]));
    $GLOBALS['nectar_test_cron'] = [
        time() + 3600 => [
            'reports_compile' => [$key => ['schedule' => 'daily', 'args' => [], 'interval' => 86400, 'driver' => 'laravel', 'id' => 'abc']],
            'wp_version_check' => [$key => ['schedule' => 'twicedaily', 'args' => [], 'interval' => 43200]],
        ],
        time() + 7200 => [
            'send_digest' => [md5(serialize(['u' => 1])) => ['schedule' => false, 'args' => ['u' => 1], 'driver' => 'laravel', 'id' => 'def']],
            'action_scheduler_run_queue' => [$key => ['schedule' => 'every_minute', 'args' => [], 'interval' => 60]],
        ],
    ];
    $GLOBALS['nectar_test_options'] = [];

    config([
        'schedule.driver' => 'laravel',
        'schedule.protected_hooks' => [],
        'queue.default' => 'database',
        'queue.connections.database' => ['driver' => 'database'],
    ]);
});

afterEach(function () {
    unset($GLOBALS['nectar_test_cron'], $GLOBALS['nectar_test_options']);
});

it('lists the events with their driver and where they stand against the configured driver', function () {
    $payload = scheduledEvents();
    $events = array_column($payload['events'], null, 'hook');

    expect($payload['configured_driver'])->toBe('laravel')
        ->and($payload['driver_available'])->toBeTrue()
        ->and($payload['count'])->toBe(4)
        ->and($events['reports_compile'])->toMatchArray(['type' => 'recurring', 'recurrence' => 'daily', 'driver' => 'laravel', 'placement' => 'active'])
        ->and($events['send_digest'])->toMatchArray(['type' => 'once', 'args' => ['u' => 1]])
        ->and($events['wp_version_check']['placement'])->toBe('to migrate')
        ->and($events['action_scheduler_run_queue']['placement'])->toBe('protected')
        ->and($payload['to_migrate'])->toBe(['wp-cron' => 1]);
});

it('filters by type and by driver', function () {
    Nectar::tool(ScheduledEvents::class, ['type' => 'once'])->assertOk()->assertSee('send_digest')->assertDontSee('reports_compile');
    Nectar::tool(ScheduledEvents::class, ['driver' => 'wp-cron'])->assertOk()->assertSee('wp_version_check')->assertDontSee('send_digest');
});

it('says when the queue cannot run the laravel driver, and counts late events', function () {
    config(['queue.default' => 'sync', 'queue.connections.sync' => ['driver' => 'sync']]);
    $GLOBALS['nectar_test_cron'] = [time() - 3000 => ['lost' => [md5(serialize([])) => ['schedule' => false, 'args' => [], 'driver' => 'laravel', 'id' => 'x']]]];

    expect(scheduledEvents())->toMatchArray(['driver_available' => false, 'late_events' => 1]);
});

it('reports the failures of the last week', function () {
    $GLOBALS['nectar_test_options'] = ['pollora_schedule_failures' => [
        ['hook' => 'digest', 'time' => time() - 60, 'message' => 'boom'],
        ['hook' => 'old', 'time' => time() - 86400 * 30, 'message' => 'ancient'],
    ]];

    Nectar::tool(ScheduledEvents::class)->assertOk()->assertSee('boom')->assertDontSee('ancient');
});

it('refuses an unknown type', function () {
    Nectar::tool(ScheduledEvents::class, ['type' => 'sometimes'])->assertOk()->assertSee('once');
});
