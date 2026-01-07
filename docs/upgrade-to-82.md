# Migrating from v8.1.1 to v8.2

This guide explains how to upgrade your application from CalendarBundle 8.1.1 to 8.2.

## New Features

### Bundle Configuration

The bundle now supports configuration options. See [configuration.md](configuration.md) for details.

```yaml
# config/packages/calendar.yaml
calendar:
    cache_max_age: 300    # HTTP cache (0 to disable)
    json_max_depth: 4     # JSON nesting limit for filters
```

### HTTP Caching

The `/fc-load-events` endpoint now includes HTTP caching by default:
- ETag headers using xxh3 hash
- `Cache-Control: public, max-age=300`
- Automatic `304 Not Modified` responses

To disable: set `cache_max_age: 0` in configuration.

### Fluent Interface

Event setters now return `$this` for method chaining:

```php
$event = (new Event('Title', new \DateTime()))
    ->setEnd(new \DateTime('+1 hour'))
    ->setAllDay(false)
    ->setResourceId('room-1')
    ->addOption('color', 'blue');
```

### Custom Exceptions

New exceptions for better error handling:

| Exception | When Thrown |
|-----------|-------------|
| `InvalidDateException` | Missing or malformed `start`/`end` parameters |
| `InvalidJsonException` | Invalid JSON in `filters` parameter |

Both implement `CalendarExceptionInterface` for catch-all handling.

## Breaking Changes

### 1. Event::getOption() Return Value

**Bug fix**: `getOption()` now returns `null` for missing keys instead of the key itself.

**Before (buggy behavior):**
```php
$event->getOption('nonexistent'); // returned 'nonexistent'
```

**After (correct behavior):**
```php
$event->getOption('nonexistent'); // returns null
$event->getOption('nonexistent', 'default'); // returns 'default'
```

**Migration**: If you relied on the buggy behavior, update your code to handle `null`.

### 2. Event::setEnd(null) Behavior

Setting `end` to `null` now automatically sets `allDay` to `true`.

**Before:**
```php
$event->setAllDay(false);
$event->setEnd(null);
// allDay remained false
```

**After:**
```php
$event->setAllDay(false);
$event->setEnd(null);
// allDay is now true (no end = all day event)
```

**Migration**: If you need a non-all-day event without end time, this is logically inconsistent with FullCalendar. Review your use case.

### 3. Event::setAllDay(true) Normalizes Start Time

Setting `allDay` to `true` now normalizes the start time to midnight.

**Before:**
```php
$event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
$event->setAllDay(true);
// start remained 2024-01-15 14:30:00
```

**After:**
```php
$event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
$event->setAllDay(true);
// start is now 2024-01-15 00:00:00
```

**Migration**: This ensures consistency with FullCalendar. No action needed unless you relied on preserving time for all-day events.

### 4. JSON Depth Limit

The `filters` parameter now has a default depth limit of 4 levels. Deeply nested JSON will throw `BadRequestHttpException`.

**Migration**: If your application uses deeply nested filters, increase `json_max_depth` in configuration.

## Migration Steps

1. **Update the bundle**:
   ```bash
   composer update tattali/calendar-bundle
   ```

2. **Review Event::getOption() usage** in your code for null handling

3. **Optional**: Add configuration file if you need non-default values:
   ```yaml
   # config/packages/calendar.yaml
   calendar:
       cache_max_age: 300
       json_max_depth: 4
   ```

4. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

## No Changes Required

The following remain fully backward compatible:

- Event subscriber pattern (`SetDataEvent`)
- API endpoint path (`/fc-load-events`)
- Event entity constructor and basic usage
- Frontend JavaScript integration
- Service IDs and aliases
