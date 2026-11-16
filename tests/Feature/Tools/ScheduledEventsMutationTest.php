<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Tools\ScheduledEvents;

/**
 * @param  array<string, mixed>  $arguments
 */
function scheduledMutationResponse(array $arguments = []): string
{
    return (string) (new ScheduledEvents)->handle(new Request($arguments))->content();
}

/**
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function scheduledMutationPayload(array $arguments = []): array
{
    return json_decode(scheduledMutationResponse($arguments), true);
}

/**
 * Cron entries of one hook per timestamp: [timestamp => hook] with each entry's fields.
 *
 * @param  array<int, array{0: int|string, 1: array<string, mixed>}>  $events
 */
function scheduledMutationCron(array $events): void
{
    $cron = [];

    foreach ($events as [$timestamp, $entry]) {
        $cron[$timestamp] = $entry;
    }

    $GLOBALS['nectar_test_cron'] = $cron;
}

/**
 * @param  array<string, mixed>  $fields
 * @return array<string, array<string, array<string, mixed>>>
 */
function scheduledMutationHook(string $hook, array $fields = []): array
{
    return [$hook => [md5($hook) => ['schedule' => false, 'args' => [], ...$fields]]];
}

/**
 * @param  list<int>  $timestamps
 */
function scheduledMutationOnceEvents(array $timestamps): void
{
    $cron = [];

    foreach ($timestamps as $index => $timestamp) {
        $cron[$timestamp] = scheduledMutationHook('hook_'.$index);
    }

    $GLOBALS['nectar_test_cron'] = $cron;
}

beforeEach(function (): void {
    $GLOBALS['nectar_test_cron'] = [];
    $GLOBALS['nectar_test_options'] = [];

    config([
        'schedule.driver' => 'wp-cron',
        'schedule.protected_hooks' => [],
        'schedule.laravel.late_after' => 600,
        'schedule.laravel.connection' => null,
        'queue.default' => 'database',
        'queue.connections.database' => ['driver' => 'database'],
    ]);
});

afterEach(function (): void {
    unset($GLOBALS['nectar_test_cron'], $GLOBALS['nectar_test_options']);
});

it('answers with the documented keys and nothing else', function (): void {
    scheduledMutationCron([[time() + 60, scheduledMutationHook('tidy', ['schedule' => 'hourly', 'interval' => 3600])]]);

    $payload = scheduledMutationPayload();

    expect(array_keys($payload))->toBe(['configured_driver', 'known_driver', 'driver_available', 'protected_hooks', 'to_migrate', 'late_events', 'recent_failures', 'count', 'truncated', 'events'])
        ->and(array_keys($payload['events'][0]))->toBe(['hook', 'type', 'recurrence', 'interval', 'next_run', 'next_run_in', 'driver', 'args', 'placement']);
});

it('describes an event field by field', function (): void {
    $timestamp = time() + 3600;
    scheduledMutationCron([[$timestamp, scheduledMutationHook('tidy', ['schedule' => 'hourly', 'interval' => 3600, 'args' => ['a' => 1], 'driver' => 'wp-cron'])]]);

    $event = scheduledMutationPayload()['events'][0];

    expect($event)->toMatchArray([
        'hook' => 'tidy',
        'type' => 'recurring',
        'recurrence' => 'hourly',
        'interval' => 3600,
        'next_run' => gmdate('c', $timestamp),
        'driver' => 'wp-cron',
        'args' => ['a' => 1],
        'placement' => 'active',
    ])->and($event['next_run_in'])->toBeBetween(3595, 3600);
});

it('counts the seconds until a past event as negative', function (): void {
    scheduledMutationOnceEvents([time() - 100]);

    expect(scheduledMutationPayload()['events'][0]['next_run_in'])->toBeBetween(-105, -100);
});

it('gives a one-off event no recurrence and no interval', function (): void {
    scheduledMutationOnceEvents([time() + 60]);

    expect(scheduledMutationPayload()['events'][0])->toMatchArray(['type' => 'once', 'recurrence' => null, 'interval' => null, 'driver' => 'wp-cron', 'args' => []]);
});

it('casts the loosely typed fields of a cron entry', function (): void {
    scheduledMutationCron([[time() + 60, [
        123 => [md5('x') => ['schedule' => 3600, 'interval' => '300', 'driver' => 7, 'args' => 'scalar']],
    ]]]);

    $event = scheduledMutationPayload()['events'][0];

    expect($event['hook'])->toBe('123')
        ->and($event['recurrence'])->toBe('3600')
        ->and($event['interval'])->toBe(300)
        ->and($event['driver'])->toBe('7')
        ->and($event['args'])->toBe(['scalar']);
});

it('skips the hooks whose entries are not a list', function (): void {
    scheduledMutationCron([[time() + 60, ['scalar_hook' => 'scalar'] + scheduledMutationHook('real')]]);

    $payload = scheduledMutationPayload();

    expect(array_column($payload['events'], 'hook'))->toBe(['real']);
});

it('accepts both types and refuses any other with the exact message', function (string $type): void {
    scheduledMutationCron([
        [time() + 60, scheduledMutationHook('single')],
        [time() + 120, scheduledMutationHook('repeat', ['schedule' => 'daily'])],
    ]);

    $payload = scheduledMutationPayload(['type' => $type]);

    expect(array_column($payload['events'], 'hook'))->toBe([$type === 'once' ? 'single' : 'repeat']);
})->with(['once', 'recurring']);

it('refuses an unknown type with the exact message', function (): void {
    expect(scheduledMutationResponse(['type' => 'sometimes']))->toBe('"type" is "once" or "recurring".');
});

it('numbers the filtered events from zero', function (): void {
    scheduledMutationCron([
        [time() + 60, scheduledMutationHook('single')],
        [time() + 120, scheduledMutationHook('repeat', ['schedule' => 'daily'])],
        [time() + 180, scheduledMutationHook('again', ['schedule' => 'daily'])],
    ]);

    $events = scheduledMutationPayload(['type' => 'recurring'])['events'];

    expect($events)->toHaveCount(2)
        ->and(array_is_list($events))->toBeTrue();
});

it('lists at most the limit, and says so only when something was left out', function (int|string $limit, int $listed, bool $truncated): void {
    scheduledMutationOnceEvents([time() + 60, time() + 120, time() + 180]);

    $payload = scheduledMutationPayload(['limit' => $limit]);

    expect($payload['events'])->toHaveCount($listed)
        ->and($payload['count'])->toBe(3)
        ->and($payload['truncated'])->toBe($truncated);
})->with([
    'one' => [1, 1, true],
    'two' => [2, 2, true],
    'as a string' => ['2', 2, true],
    'exactly all' => [3, 3, false],
    'more than all' => [4, 3, false],
    'zero means the default' => [0, 3, false],
    'negative is clamped to one' => [-5, 1, true],
]);

it('lists a hundred events by default', function (): void {
    scheduledMutationOnceEvents(range(time() + 1000, time() + 1100));

    $payload = scheduledMutationPayload();

    expect($payload['events'])->toHaveCount(100)
        ->and($payload['count'])->toBe(101)
        ->and($payload['truncated'])->toBeTrue();
});

it('knows the three drivers and flags any other', function (string $driver, bool $known): void {
    config(['schedule.driver' => $driver]);

    $payload = scheduledMutationPayload();

    expect($payload['configured_driver'])->toBe($driver)
        ->and($payload['known_driver'])->toBe($known);
})->with([
    'wp-cron' => ['wp-cron', true],
    'action-scheduler' => ['action-scheduler', true],
    'laravel' => ['laravel', true],
    'something else' => ['cron-tab', false],
]);

it('falls back to wp-cron when the configured driver is not a usable string', function (mixed $configured): void {
    config(['schedule.driver' => $configured]);

    expect(scheduledMutationPayload()['configured_driver'])->toBe('wp-cron');
})->with(['empty' => [''], 'integer' => [5], 'array' => [['laravel']], 'null' => [null]]);

it('says whether each driver can run', function (): void {
    config(['schedule.driver' => 'action-scheduler']);
    expect(scheduledMutationPayload()['driver_available'])->toBe(function_exists('as_schedule_single_action'));

    config(['schedule.driver' => 'wp-cron']);
    expect(scheduledMutationPayload()['driver_available'])->toBeTrue();
});

it('finds the laravel queue unable to run on the sync and null drivers', function (string $driver, bool $runs): void {
    config([
        'schedule.driver' => 'laravel',
        'queue.default' => 'primary',
        'queue.connections.primary' => ['driver' => $driver],
    ]);

    expect(scheduledMutationPayload()['driver_available'])->toBe($runs);
})->with([
    'sync' => ['sync', false],
    'null' => ['null', false],
    'database' => ['database', true],
    'redis' => ['redis', true],
]);

it('prefers the connection configured for the laravel driver', function (): void {
    config([
        'schedule.driver' => 'laravel',
        'schedule.laravel.connection' => 'scheduled',
        'queue.default' => 'sync',
        'queue.connections.sync' => ['driver' => 'sync'],
        'queue.connections.scheduled' => ['driver' => 'redis'],
    ]);

    expect(scheduledMutationPayload()['driver_available'])->toBeTrue();
});

it('cannot run the laravel driver on an unknown connection', function (): void {
    config(['schedule.driver' => 'laravel', 'queue.default' => 'missing']);

    expect(scheduledMutationPayload()['driver_available'])->toBeFalse();
});

it('adds the configured protected hooks to the built-in ones, once each, as strings', function (): void {
    config(['schedule.protected_hooks' => ['pollora/async/run', 'my_hook', 123]]);

    $hooks = scheduledMutationPayload()['protected_hooks'];

    expect($hooks)->toBe(['action_scheduler_run_queue', 'pollora/async/run', 'pollora/async/recover', 'my_hook', '123'])
        ->and(array_is_list($hooks))->toBeTrue();
});

it('keeps the built-in protected hooks when the configuration is not a list', function (): void {
    config(['schedule.protected_hooks' => 'my_hook']);

    expect(scheduledMutationPayload()['protected_hooks'])->toBe(['action_scheduler_run_queue', 'pollora/async/run', 'pollora/async/recover']);
});

it('places a protected wp-cron event under protected when laravel is configured', function (): void {
    config(['schedule.driver' => 'laravel', 'schedule.protected_hooks' => ['my_hook']]);
    scheduledMutationCron([[time() + 60, scheduledMutationHook('my_hook')]]);

    expect(scheduledMutationPayload()['events'][0]['placement'])->toBe('protected');
});

it('counts the events to migrate per driver', function (): void {
    config(['schedule.driver' => 'laravel']);
    scheduledMutationCron([
        [time() + 60, scheduledMutationHook('one') + scheduledMutationHook('two')],
        [time() + 120, scheduledMutationHook('three') + scheduledMutationHook('four', ['driver' => 'action-scheduler'])],
        [time() + 180, scheduledMutationHook('five', ['driver' => 'laravel'])],
    ]);

    expect(scheduledMutationPayload()['to_migrate'])->toBe(['wp-cron' => 3, 'action-scheduler' => 1]);
});

it('counts as late only the overdue events of the laravel driver', function (): void {
    scheduledMutationCron([
        [time() - 700, scheduledMutationHook('overdue_laravel', ['driver' => 'laravel'])],
        [time() - 701, scheduledMutationHook('overdue_wp_cron')],
        [time() + 700, scheduledMutationHook('upcoming_laravel', ['driver' => 'laravel'])],
        [time() - 500, scheduledMutationHook('recent_laravel', ['driver' => 'laravel'])],
    ]);

    expect(scheduledMutationPayload()['late_events'])->toBe(1);
});

it('waits for the configured delay before calling an event late', function (): void {
    config(['schedule.laravel.late_after' => '300']);
    scheduledMutationCron([
        [time() - 400, scheduledMutationHook('late', ['driver' => 'laravel'])],
        [time() - 200, scheduledMutationHook('not_yet', ['driver' => 'laravel'])],
    ]);

    expect(scheduledMutationPayload()['late_events'])->toBe(1);
});

it('never waits less than a minute and reads the delay as a number', function (mixed $delay): void {
    config(['schedule.laravel.late_after' => $delay]);
    scheduledMutationCron([
        [time() - 100, scheduledMutationHook('late', ['driver' => 'laravel'])],
        [time() - 30, scheduledMutationHook('not_yet', ['driver' => 'laravel'])],
    ]);

    expect(scheduledMutationPayload()['late_events'])->toBe(1);
})->with(['ten seconds' => [10], 'not numeric' => ['abc'], 'zero' => [0]]);

it('waits ten minutes when no delay is configured', function (): void {
    config(['schedule.laravel' => ['connection' => null]]);
    scheduledMutationCron([
        [time() - 700, scheduledMutationHook('late', ['driver' => 'laravel'])],
        [time() - 500, scheduledMutationHook('not_yet', ['driver' => 'laravel'])],
    ]);

    expect(scheduledMutationPayload()['late_events'])->toBe(1);
});

it('keeps the failures of the last week only, as a list', function (): void {
    $GLOBALS['nectar_test_options'] = ['pollora_schedule_failures' => [
        ['hook' => 'ancient', 'time' => time() - 604800 - 10],
        ['hook' => 'exactly_a_week', 'time' => time() - 604800],
        ['hook' => 'almost_a_week', 'time' => time() - 604799],
        ['hook' => 'no_time'],
        'not an array',
        ['hook' => 'recent', 'time' => time() - 5],
    ]];

    $failures = scheduledMutationPayload()['recent_failures'];

    expect(array_column($failures, 'hook'))->toBe(['almost_a_week', 'recent'])
        ->and(array_is_list($failures))->toBeTrue();
});

it('reports no failures when the option holds something else than a list', function (): void {
    $GLOBALS['nectar_test_options'] = ['pollora_schedule_failures' => 'garbage'];

    expect(scheduledMutationPayload()['recent_failures'])->toBe([]);
});
