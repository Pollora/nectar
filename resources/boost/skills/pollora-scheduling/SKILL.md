---
name: pollora-scheduling
description: Schedule recurring and single tasks with Pollora's #[Schedule] attribute or fluent API, on WP-Cron, Action Scheduler or the Laravel queue.
---

# Pollora Scheduling Development

## When to use this skill
Use this skill when creating scheduled tasks (recurring or single), choosing where they run (WP-Cron, Action Scheduler, the Laravel queue), or finding out why one did not run. The engine is the `pollora/schedule` package (framework v13.36+).

## Creating Scheduled Tasks

### Using Every Enum (Recommended)

```php
<?php

namespace Theme\MyTheme\Cms\Schedule;

use Pollora\Attributes\Schedule;
use Pollora\Schedule\Every;

class MaintenanceTasks
{
    #[Schedule(Every::HOUR)]
    public function clearTemporaryFiles(): void
    {
        // Runs every hour
    }

    #[Schedule(Every::DAY)]
    public function dailyCleanup(): void
    {
        // Runs once daily
    }

    #[Schedule(Every::WEEK)]
    public function weeklyReport(): void
    {
        // Runs once per week
    }

    #[Schedule(Every::MONTH)]
    public function monthlyAudit(): void
    {
        // Runs once per month (auto-registers custom schedule)
    }
}
```

### Available Every Values

| Value | WordPress Interval |
|-------|-------------------|
| `Every::HOUR` | `hourly` |
| `Every::TWICE_DAILY` | `twicedaily` |
| `Every::DAY` | `daily` |
| `Every::WEEK` | `weekly` |
| `Every::MONTH` | Custom (auto-registered) |
| `Every::YEAR` | Custom (auto-registered) |

### Using Interval Class (Precise Timing)

```php
use Pollora\Attributes\Schedule;
use Pollora\Schedule\Interval;

class DataSync
{
    #[Schedule(new Interval(hours: 3, minutes: 30))]
    public function syncExternalData(): void
    {
        // Runs every 3 hours and 30 minutes
    }

    #[Schedule(new Interval(weeks: 2, display: 'Bi-weekly'))]
    public function biweeklyTask(): void
    {
        // Custom interval with friendly display name
    }

    #[Schedule(new Interval(minutes: 15))]
    public function frequentCheck(): void
    {
        // Runs every 15 minutes
    }
}
```

### Custom Hook Names and Arguments

```php
#[Schedule(
    Every::DAY,
    hook: 'cleanup_old_records',
    args: ['type' => 'full', 'force' => true]
)]
public function cleanup(array $args): void
{
    $type = $args['type'];   // 'full'
    $force = $args['force']; // true
}
```

### Dependency Injection

Scheduled task classes support constructor injection:

```php
class ReportSchedule
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly MailService $mail,
    ) {}

    #[Schedule(Every::WEEK)]
    public function sendWeeklyReport(): void
    {
        $report = $this->reports->generateWeekly();
        $this->mail->send('admin@example.com', $report);
    }
}
```

## Placement

Place schedule classes where the discovery system scans:
- `app/Cms/Schedule/` in themes
- `app/Schedule/` in the main application or modules

## Fluent API

When the schedule depends on a condition or a computed value. Declare it on every request: it is scheduled once.

```php
use Pollora\Schedule\Interval;
use Pollora\Schedule\Schedule;

Schedule::event('reports_compile')->daily()->at('02:00')->call([Reports::class, 'compile']);
Schedule::event('cache_warm')->every(new Interval(minutes: 15))->call(WarmCache::class);
Schedule::once('send_digest')->in(minutes: 10)->with(['user' => 42])->call(SendDigest::class);

Schedule::cancel('send_digest', ['user' => 42]);
Schedule::next('reports_compile');   // DateTimeImmutable|null
Schedule::fake();                    // in tests: Schedule::assertScheduled('send_digest')
```

`at('02:00')` follows the timezone of the site. `call()` takes an invokable class, `[Class::class, 'method']`, a closure or a hook name; classes are built by the container.

## Drivers

`config/schedule.php` (`POLLORA_SCHEDULE_DRIVER`) decides where **every** event goes, including the `wp_schedule_event()` calls of WordPress, plugins and themes:

| Driver | Runs events | Needs |
|---|---|---|
| `wp-cron` (default) | WP-Cron | A system cron requesting `wp-cron.php` (Pollora sets `DISABLE_WP_CRON`) |
| `action-scheduler` | Action Scheduler's queue runner | A plugin that bundles it (WooCommerce) |
| `laravel` | A queue worker: one delayed job per event | A connection that accepts delays (not `sync`/`null`) and `php artisan queue:work` |

The `cron` option indexes every event, with a `driver` key on those another driver keeps. Changing the driver moves nothing: events already scheduled stay put until `php artisan pollora:schedule:migrate` (`--dry-run`, `--from`, `--to`). An unavailable driver leaves events on WP-Cron.

The removed `wordpress.use_laravel_scheduler` is replaced by `POLLORA_SCHEDULE_DRIVER=laravel` and a worker (no more `schedule:run`).

## Managing Scheduled Events

```bash
# Every event, with its type and driver (--type, --driver, --site, --network, --json)
ddev php artisan pollora:schedule:list
ddev wp cron event list --fields=hook,type,driver

# Move events to the driver of the site
ddev php artisan pollora:schedule:migrate --dry-run

# laravel driver: send again events whose job was lost
ddev php artisan pollora:schedule:recover --dry-run

# Run all due cron events (runs every due event, whoever keeps it)
ddev wp cron event run --due-now
```

`pollora:doctor` reports an unknown or unavailable driver, events left to migrate, late events (no worker) and events that failed for good. The `scheduled_events` Nectar tool returns the same from the index.

## Important Notes

- **Auto-discovered** — no manual `wp_schedule_event()` calls needed
- Hook names are auto-generated from the method name if not specified
- `Every::MONTH` and `Every::YEAR` automatically register custom WordPress cron schedules
- The `Interval` class allows arbitrary timing combinations
- WordPress cron is "pseudo-cron" — it runs on page visits. Pollora sets `DISABLE_WP_CRON`: with the `wp-cron` and `action-scheduler` drivers something must request `wp-cron.php`; with `laravel` a worker must run
- Run `php artisan discovery:clear` after adding new schedule classes