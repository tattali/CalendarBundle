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

The event stores its dates as `\DateTimeImmutable`. An immutable date is stored as is, the same instance, except the start of an all-day event, which is stored at midnight. A `\DateTime` is copied with the same date, time and time zone. See [Event Dates Are Immutable](#event-dates-are-immutable).

### DateTimeInterface Dates in setStart() and setEnd()

`Event::setStart()` now accepts any `\DateTimeInterface $start` and `Event::setEnd()` any `?\DateTimeInterface $end`:

```php
$event
    ->setEnd($booking->getEndAt())      // \DateTimeImmutable
    ->setStart($booking->getBeginAt()); // \DateTimeImmutable
```

As in the constructor, an immutable date is stored as is and a `\DateTime` is copied. Overriding these setters in a subclass needs a change: see [Breaking Changes](#setstart-and-setend-accept-datetimeinterface).

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

### GET Requests on the Events Feed

`/fc-load-events` now reads `start`, `end` and `filters` from the query string on GET and HEAD requests, which is what FullCalendar's JSON feed sends by default. POST requests still read the body.

### 304 Not Modified

A GET request whose `If-None-Match` header matches the feed's ETag now gets an empty `304 Not Modified` response.

### FullCalendar timeZone Parameter

The endpoint reads the `timeZone` parameter that FullCalendar sends when its `timeZone` option is not `local`, parses `start` and `end` in that zone and passes it to listeners as `SetDataEvent::getTimeZone()` (`null` when none was sent). See [timezone.md](timezone.md).

## Breaking Changes

### Events Feed Is Private by Default

The default `Cache-Control` of `/fc-load-events` changes from `public, max-age=300` to `private, no-cache`. Browsers revalidate on every request (and get a 304 when nothing changed), and shared caches or CDNs no longer store the feed, which is often different for each user.

**Migration**: for a feed that is the same for every user, restore the 8.2 behaviour with the new `cache_public` option:

```yaml
# config/packages/calendar.yaml
calendar:
    cache_public: true
```

`cache_max_age` then sets the `max-age` as before.

### All-Day Events Are Serialised as Dates

`Event::toArray()`, and so the JSON feed, gives the `start` and `end` of an all-day event as `Y-m-d` (`2026-10-15`) instead of an ATOM date-time (`2026-10-15T00:00:00+02:00`), so the event stays on its day whatever the calendar's time zone. Timed events are unchanged. FullCalendar accepts both forms.

**Migration**: code that reads `toArray()` or the feed and parses these values as a date-time must accept a plain date for all-day events, or check `allDay` first:

```php
$start = $data['allDay']
    ? \DateTime::createFromFormat('!Y-m-d', $data['start'])
    : new \DateTime($data['start']);
```

### Listener Dates Follow the Requested Time Zone

When a client sends `timeZone`, listeners receive `start` and `end` in that zone instead of the server's. Without it, nothing changes.

**Migration**: listeners that compare these dates with stored ones should compare instants (`getTimestamp()` or `DateTime` comparison), not formatted wall-clock strings.

### Unknown Time Zones Answer 400

An unknown `timeZone` value now gives a `400 Bad Request` through the new `InvalidTimeZoneException`, which implements `CalendarExceptionInterface`. Before, the parameter was ignored.

### Event Dates Are Immutable

`Event::getStart()` now returns `\DateTimeImmutable` and `Event::getEnd()` returns `?\DateTimeImmutable`. The protected `$start` and `$end` properties are typed the same way. A `\DateTime` passed to the constructor, `setStart()` or `setEnd()` is copied, so changing it afterwards no longer changes the event. `toArray()` and the JSON feed are unchanged.

Before, the getters returned the event's own `\DateTime`, so `$event->getStart()->modify('+1 day')` moved the event. Now `modify()`, `setTime()`, `setDate()`, `setTimezone()` and the other modifiers return a new date and leave the event as it was, without any error.

**Migration**:

- Code that changes a date of the event in place must pass the new date to the setter:

  ```php
  // 8.2
  $event->getStart()->modify('+1 day');
  $event->getEnd()->setTime(18, 0);

  // 8.3
  $event->setStart($event->getStart()->modify('+1 day'));
  $event->setEnd($event->getEnd()->setTime(18, 0));
  ```

  `setEnd()` also resets `allDay` (all-day without an end, timed with one). For a multi-day all-day event, call `setAllDay(true)` after it.

- On an all-day event, `getStart()` is at midnight and the event remembers the time it was given, to restore it if the event becomes timed. `setStart()` replaces that time with the time of the date you pass, so `$event->setStart($event->getStart()->modify('+1 day'))` keeps midnight. Changing the day in place used to keep the remembered time. If you need it, pass the start with its time of day.

- Code that receives these dates as `\DateTime` gets a `TypeError`. Accept `\DateTimeInterface`, or convert with `\DateTime::createFromImmutable($event->getStart())`.

- A subclass that overrides `getStart()` or `getEnd()` with a `\DateTime` return type now stops with:

  ```
  PHP Fatal error:  Declaration of App\Calendar\MyEvent::getStart(): DateTime must be compatible with CalendarBundle\Entity\Event::getStart(): DateTimeImmutable
  ```

  Change the return types to `\DateTimeImmutable` and `?\DateTimeImmutable`.

- A subclass that assigns a `\DateTime` to `$this->start` or `$this->end` gets `Cannot assign DateTime to property CalendarBundle\Entity\Event::$start of type DateTimeImmutable`. Assign a `\DateTimeImmutable` (`\DateTimeImmutable::createFromMutable($date)`), or call the setters.

Comparing the dates with `<`, `>` or `==` works as before, with either `\DateTime` or `\DateTimeImmutable` on the other side.

### Listener Dates Are Immutable

`SetDataEvent::getStart()` and `SetDataEvent::getEnd()` now return `\DateTimeImmutable`, and the `start` and `end` properties of `QueryParameters` are `\DateTimeImmutable`. `RequestParser` builds immutable dates. The `SetDataEvent` and `QueryParameters` constructors still accept any `\DateTimeInterface`: a `\DateTime` is copied and an immutable date is kept as is.

Before, every listener received the same `\DateTime` objects, so a listener that changed them in place, for example with `setTimezone()`, changed the dates the next listeners received. Now the modifiers return a new date and the event keeps its own.

**Migration**:

- A listener, or a method it passes the dates to, with a `\DateTime` parameter gets a `TypeError`. Accept `\DateTimeInterface` (or `\DateTimeImmutable`):

  ```php
  // 8.2
  private function fillCalendar(SetDataEvent $setDataEvent, \DateTime $start, \DateTime $end): void

  // 8.3
  private function fillCalendar(SetDataEvent $setDataEvent, \DateTimeInterface $start, \DateTimeInterface $end): void
  ```

- Code that changes the dates in place must use the returned date:

  ```php
  // 8.2
  $start = $setDataEvent->getStart();
  $start->setTimezone($storageZone);

  // 8.3
  $start = $setDataEvent->getStart()->setTimezone($storageZone);
  ```

  `(clone $setDataEvent->getStart())->setTimezone($storageZone)` keeps working.

- A subclass of `SetDataEvent` that overrides `getStart()` or `getEnd()` with a `\DateTime` return type now stops with a fatal error (`Declaration of ...::getStart(): DateTime must be compatible with CalendarBundle\Event\SetDataEvent::getStart(): DateTimeImmutable`). Change the return types to `\DateTimeImmutable`.

- Code that reads `QueryParameters::$start` or `$end`, for example in a decorated request parser, gets `\DateTimeImmutable`. Custom parsers that build `QueryParameters` with `\DateTime` values need no change.

### setStart() and setEnd() Accept \DateTimeInterface

`Event::setStart()` now takes a `\DateTimeInterface $start` and `Event::setEnd()` a `?\DateTimeInterface $end`. Calls keep working. A subclass that overrides one of them with a `\DateTime` parameter now stops with:

```
PHP Fatal error:  Declaration of App\Calendar\MyEvent::setStart(DateTime $start): App\Calendar\MyEvent must be compatible with CalendarBundle\Entity\Event::setStart(DateTimeInterface $start): CalendarBundle\Entity\Event
```

**Migration**: widen the parameter types in your overrides:

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

The constructor passes its `$start` and `$end` to these setters as given, so an override receives a `\DateTimeImmutable` when one is passed to the constructor. An override that changes the date in place gets no error and no effect on an immutable date: use the date the modifier returns instead.

```php
// 8.2
public function setStart(\DateTime $start): self
{
    $start->setTime(9, 0);

    return parent::setStart($start);
}

// 8.3
public function setStart(\DateTimeInterface $start): self
{
    return parent::setStart(\DateTimeImmutable::createFromInterface($start)->setTime(9, 0));
}
```

Overridden constructors are not affected.

## Migration Steps

1. **Update the bundle**:
   ```bash
   composer update tattali/calendar-bundle
   ```

2. **If you subclass `Event`**: widen the `setStart()`/`setEnd()` overrides to `\DateTimeInterface`/`?\DateTimeInterface`, change the `getStart()`/`getEnd()` overrides to return `\DateTimeImmutable`/`?\DateTimeImmutable`, and assign `\DateTimeImmutable` values to `$start` and `$end`, if any.

3. **If you change event dates in place**: replace `$event->getStart()->modify(...)` and similar calls with `$event->setStart($event->getStart()->modify(...))`, and the same for the end. Accept `\DateTimeInterface` where you pass `getStart()` or `getEnd()` to code typed `\DateTime`.

4. **If you have `SetDataEvent` listeners**: accept `\DateTimeInterface` where they pass `getStart()` or `getEnd()` to code typed `\DateTime`, and use the date returned by `setTimezone()`, `modify()` and the other modifiers instead of changing the dates in place.

5. **Optional**: pass `\DateTimeImmutable` values to the `Event` constructor, `setStart()` and `setEnd()` directly and remove any `\DateTime::createFromImmutable()` conversions.

## No Changes Required by the DateTimeInterface Dates

- Existing constructor calls and argument order
- Existing `setStart()` and `setEnd()` calls
- These changes do not alter the `toArray()` output
