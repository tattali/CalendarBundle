# Migrating from v8.2 to v8.3

This guide explains how to upgrade your application from CalendarBundle 8.2 to 8.3.

## New Features

### DateTimeInterface Dates

The `Event` constructor, `setStart()` and `setEnd()` now accept any `\DateTimeInterface`, so `\DateTimeImmutable` values, such as Doctrine `datetime_immutable` columns, can be passed directly:

```php
$event = new Event(
    $booking->getTitle(),
    $booking->getBeginAt(), // \DateTimeImmutable
    $booking->getEndAt(),   // \DateTimeImmutable
);
```

`getStart()` and `getEnd()` still return a mutable `\DateTime`. An immutable date is copied into a new `\DateTime` (same date, time and time zone); the immutable you passed is never changed. A `\DateTime` passed for a timed event is still stored as the same instance.

### Multi-Day All-Day Events

The constructor takes a new last argument, `?bool $allDay = null`:

| Value | Result |
|-------|--------|
| `null` (default) | Same as before: all-day when there is no `end`, timed when there is one |
| `true` | All-day, even with an `end` (multi-day all-day event) |
| `false` | Timed, even without an `end` |

```php
$conference = new Event(
    'Conference',
    new \DateTimeImmutable('2024-01-15'),
    new \DateTimeImmutable('2024-01-18'),
    allDay: true,
);
```

`setEnd()` still resets `allDay` (all-day without an end, timed with one). When you build the event with setters, call `setAllDay(true)` after `setEnd()`:

```php
$event->setEnd(new \DateTime('2024-01-18'))->setAllDay(true);
```

### The Start Time Is Kept

An all-day event still exposes a start at midnight, but the time you gave is kept. It comes back when the event stops being all-day, with `setAllDay(false)` or `setEnd($date)`:

**Before:**
```php
$event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
$event->setAllDay(false);
// start was 2024-01-15 00:00:00
```

**After:**
```php
$event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
$event->setAllDay(false);
// start is 2024-01-15 14:30:00
```

`setEnd(null)` now also sets the start to midnight, like `setAllDay(true)` does.

## Breaking Changes

### Subclasses Overriding setStart() or setEnd()

If your code extends `Event` and overrides `setStart()` or `setEnd()` with a `\DateTime` parameter, PHP stops with:

```
PHP Fatal error:  Declaration of App\Calendar\MyEvent::setStart(DateTime $start): App\Calendar\MyEvent must be compatible with CalendarBundle\Entity\Event::setStart(DateTimeInterface $start): CalendarBundle\Entity\Event
```

**Before:**
```php
class MyEvent extends Event
{
    public function setStart(\DateTime $start): self
    {
        // ...
        return parent::setStart($start);
    }

    public function setEnd(?\DateTime $end): self
    {
        // ...
        return parent::setEnd($end);
    }
}
```

**After:**
```php
class MyEvent extends Event
{
    public function setStart(\DateTimeInterface $start): self
    {
        // ...
        return parent::setStart($start);
    }

    public function setEnd(?\DateTimeInterface $end): self
    {
        // ...
        return parent::setEnd($end);
    }
}
```

**Migration**: Widen the parameter types to `\DateTimeInterface`. The widened signatures also work with 8.2, so you can change them before upgrading. An overridden `__construct()` with `\DateTime` parameters keeps working.

## Migration Steps

1. **Update the bundle**:
   ```bash
   composer update tattali/calendar-bundle
   ```

2. **Widen the `setStart()`/`setEnd()` overrides** in your `Event` subclasses, if any

3. **Optional**: Pass Doctrine `\DateTimeImmutable` values directly and remove any `\DateTime::createFromImmutable()` conversions

4. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

## No Changes Required

The following remain fully backward compatible:

- `getStart()` and `getEnd()` return types (`\DateTime` and `?\DateTime`)
- Existing constructor calls and argument order
- `toArray()` output for events that do not switch between all-day and timed
- Event subscriber pattern (`SetDataEvent`)
