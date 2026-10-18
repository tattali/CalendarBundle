# Migrating from v8.2 to v8.3

This guide explains how to upgrade your application from CalendarBundle 8.2 to 8.3.

## New Features

### DateTimeInterface Dates in the Event Constructor

The `Event` constructor now accepts any `\DateTimeInterface` for `$start` and `$end`, so `\DateTimeImmutable` values, such as Doctrine `datetime_immutable` columns, can be passed directly:

```php
$event = new Event(
    $booking->getTitle(),
    $booking->getBeginAt(), // \DateTimeImmutable
    $booking->getEndAt(),   // \DateTimeImmutable
);
```

An immutable date is copied into a `\DateTime` with the same date, time and time zone; the value you passed is never changed. A `\DateTime` is used as before. `getStart()`, `getEnd()`, `setStart()` and `setEnd()` keep their `\DateTime` types in 8.3.

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

`setEnd()` still resets `allDay` (all-day without an end, timed with one). When you build the event with setters, call `setAllDay(true)` after `setEnd()`.

FullCalendar treats the `end` of an all-day event as exclusive, so the conference above shows the 15th to the 17th. To include the last day, pass the day after it as `end`.

## Coming in 9.0

### setStart() and setEnd() Will Accept \DateTimeInterface

In 9.0, `Event::setStart()` will take a `\DateTimeInterface $start` and `Event::setEnd()` a `?\DateTimeInterface $end`. Calls keep working. A subclass that overrides one of them with a `\DateTime` parameter will then stop with:

```
PHP Fatal error:  Declaration of App\Calendar\MyEvent::setStart(DateTime $start): App\Calendar\MyEvent must be compatible with CalendarBundle\Entity\Event::setStart(DateTimeInterface $start): CalendarBundle\Entity\Event
```

Prepare now by widening the parameter types in your overrides. PHP already accepts the wider type in an override of the 8.3 methods:

```php
class MyEvent extends Event
{
    public function setStart(\DateTimeInterface $start): self
    {
        // ...
        return parent::setStart($start instanceof \DateTime ? $start : \DateTime::createFromInterface($start));
    }

    public function setEnd(?\DateTimeInterface $end): self
    {
        // ...
        return parent::setEnd(null === $end || $end instanceof \DateTime ? $end : \DateTime::createFromInterface($end));
    }
}
```

The conversion before calling `parent` is needed only while the bundle is on 8.x. Overridden constructors are not affected.

## Migration Steps

1. **Update the bundle**:
   ```bash
   composer update tattali/calendar-bundle
   ```

2. **Optional**: pass `\DateTimeImmutable` values to the `Event` constructor directly and remove any `\DateTime::createFromImmutable()` conversions.

3. **Optional, before 9.0**: widen the `setStart()`/`setEnd()` overrides in your `Event` subclasses, if any.

## No Changes Required

- Existing constructor calls and argument order
- `getStart()`, `getEnd()`, `setStart()` and `setEnd()` signatures
- `toArray()` output
