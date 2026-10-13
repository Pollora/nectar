<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ScheduledEvents extends Tool
{
    protected string $name = 'scheduled_events';

    protected string $description = 'List the scheduled events (WP-Cron, Action Scheduler or the Laravel queue) with the driver that keeps each, and the state of the scheduling setup: configured driver, whether it is available, events left to migrate, late events and recent failures. Read from the "cron" option; no query reaches the driver.';

    private const array BUILT_IN_PROTECTED = ['action_scheduler_run_queue', 'pollora/async/run', 'pollora/async/recover'];

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Only "once" or "recurring" events.'),
            'driver' => $schema->string()->description('Only events kept by this driver ("wp-cron", "action-scheduler", "laravel").'),
            'limit' => $schema->integer()->description('Maximum number of events listed, soonest first. Default 100.'),
        ];
    }

    public function handle(Request $request): Response
    {
        if (! function_exists('_get_cron_array')) {
            return Response::text('WordPress is not loaded in this context.');
        }

        $type = $request->get('type');
        $driver = $request->get('driver');
        $limit = max(1, (int) ($request->get('limit') ?: 100));

        if ($type !== null && ! in_array($type, ['once', 'recurring'], true)) {
            return Response::text('"type" is "once" or "recurring".');
        }

        $configured = $this->configured();
        $available = $this->available($configured);
        $protected = $this->protectedHooks();
        $events = $this->events();

        $toMigrate = [];
        $late = 0;
        $lateAfter = max(60, (int) config('schedule.laravel.late_after', 600));

        foreach ($events as &$event) {
            $event['placement'] = $this->placement($event, $configured, $available, $protected);

            if ($event['placement'] === 'to migrate') {
                $toMigrate[$event['driver']] = ($toMigrate[$event['driver']] ?? 0) + 1;
            }

            if ($event['driver'] === 'laravel' && $event['timestamp'] < time() - $lateAfter) {
                $late++;
            }
        }
        unset($event);

        $listed = array_values(array_filter($events, static fn (array $event): bool => ($type === null || $event['type'] === $type) && ($driver === null || $event['driver'] === $driver)));

        return Response::json([
            'configured_driver' => $configured,
            'known_driver' => in_array($configured, ['wp-cron', 'action-scheduler', 'laravel'], true),
            'driver_available' => $available,
            'protected_hooks' => $protected,
            'to_migrate' => $toMigrate,
            'late_events' => $late,
            'recent_failures' => $this->failures(),
            'count' => count($listed),
            'truncated' => count($listed) > $limit,
            'events' => array_map(static function (array $event): array {
                unset($event['timestamp']);

                return $event;
            }, array_slice($listed, 0, $limit)),
        ]);
    }

    private function configured(): string
    {
        $driver = config('schedule.driver', 'wp-cron');

        return is_string($driver) && $driver !== '' ? $driver : 'wp-cron';
    }

    private function available(string $driver): bool
    {
        return match ($driver) {
            'action-scheduler' => function_exists('as_schedule_single_action'),
            'laravel' => $this->queueRuns(),
            default => true,
        };
    }

    private function queueRuns(): bool
    {
        $connection = config('schedule.laravel.connection') ?: config('queue.default', 'sync');
        $driver = config(sprintf('queue.connections.%s.driver', $connection));

        return is_string($driver) && ! in_array($driver, ['sync', 'null'], true);
    }

    /**
     * @return list<string>
     */
    private function protectedHooks(): array
    {
        $hooks = config('schedule.protected_hooks', []);

        return array_values(array_unique([...self::BUILT_IN_PROTECTED, ...array_map(strval(...), is_array($hooks) ? $hooks : [])]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        $crons = _get_cron_array();
        $events = [];

        foreach ($crons as $timestamp => $hooks) {
            if (! is_int($timestamp) || ! is_array($hooks)) {
                continue;
            }

            foreach ($hooks as $hook => $entries) {
                foreach ((array) $entries as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    $recurring = ! empty($entry['schedule']);

                    $events[] = [
                        'hook' => (string) $hook,
                        'type' => $recurring ? 'recurring' : 'once',
                        'recurrence' => $recurring ? (string) $entry['schedule'] : null,
                        'interval' => isset($entry['interval']) ? (int) $entry['interval'] : null,
                        'next_run' => gmdate('c', $timestamp),
                        'next_run_in' => $timestamp - time(),
                        'driver' => (string) ($entry['driver'] ?? 'wp-cron'),
                        'args' => (array) ($entry['args'] ?? []),
                        'timestamp' => $timestamp,
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Where an event is against where the configuration wants it: "active", "protected" or "fallback" (on WP-Cron on purpose), "to migrate".
     *
     * @param  array<string, mixed>  $event
     * @param  list<string>  $protected
     */
    private function placement(array $event, string $configured, bool $available, array $protected): string
    {
        if ($event['driver'] === $configured) {
            return 'active';
        }

        if ($event['driver'] === 'wp-cron' && in_array($event['hook'], $protected, true)) {
            return 'protected';
        }

        if ($event['driver'] === 'wp-cron' && ! $available) {
            return 'fallback';
        }

        return 'to migrate';
    }

    /**
     * Failures of the "laravel" driver in the last week.
     *
     * @return list<array<string, mixed>>
     */
    private function failures(): array
    {
        $stored = get_option('pollora_schedule_failures', []);

        return array_values(array_filter(is_array($stored) ? $stored : [], static fn (mixed $failure): bool => is_array($failure) && ($failure['time'] ?? 0) > time() - 604800));
    }
}
