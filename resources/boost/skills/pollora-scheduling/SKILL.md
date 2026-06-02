---
name: pollora-scheduling
description: Schedule recurring tasks using Pollora Schedule attributes with WordPress cron integration.
---

# Pollora Scheduling Development

## When to use this skill
Use this skill when creating scheduled/recurring tasks that run via WordPress cron, using Pollora's attribute-based scheduling system.

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

## Managing Scheduled Events

```bash
# List scheduled cron events
ddev wp cron event list

# Run all due cron events
ddev wp cron event run --due-now
```

## Important Notes

- **Auto-discovered** — no manual `wp_schedule_event()` calls needed
- Hook names are auto-generated from the method name if not specified
- `Every::MONTH` and `Every::YEAR` automatically register custom WordPress cron schedules
- The `Interval` class allows arbitrary timing combinations
- WordPress cron is "pseudo-cron" — it runs on page visits. For real cron, disable `WP_CRON` and use system cron
- Run `php artisan discovery:clear` after adding new schedule classes