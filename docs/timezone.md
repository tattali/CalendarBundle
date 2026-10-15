# Time zones

## What FullCalendar sends

When the FullCalendar [`timeZone`](https://fullcalendar.io/docs/timeZone) option is anything other than `'local'`, every request to the events endpoint carries a `timeZone` parameter next to `start` and `end`:

| FullCalendar `timeZone` | `start` / `end` sent                     | `timeZone` sent    |
|-------------------------|------------------------------------------|--------------------|
| `'local'` (default)     | `2026-10-01T00:00:00+02:00` (with offset) | not sent           |
| `'UTC'`                 | `2026-10-01T00:00:00Z`                    | `UTC`              |
| `'America/New_York'`    | `2026-10-01T00:00:00` (no offset)         | `America/New_York` |

The parameter name follows the FullCalendar [`timeZoneParam`](https://fullcalendar.io/docs/timeZoneParam) option; the bundle reads `timeZone`, the default.

## How the bundle parses it

- No `timeZone` (or an empty one): `start` and `end` are parsed in the server default time zone, as before.
- A valid `timeZone`: `start` and `end` are parsed in that zone. A date without an offset is read as local time there (`2026-10-01T00:00:00` with `America/New_York` is midnight in New York). A date with an offset keeps its instant and is converted to the requested zone.
- An unknown `timeZone`: the endpoint answers `400 Bad Request` (`Query parameter "timeZone" is not a valid time zone`).

The zone is available to listeners through `SetDataEvent::getTimeZone()`, which returns a `\DateTimeZone`, or `null` when the client did not send one.

## Querying with Doctrine

Doctrine's `datetime` type stores the wall-clock time and ignores the zone of the `\DateTime` you pass. Convert the range to the zone your dates are stored in before querying:

```php
public function onCalendarSetData(SetDataEvent $setDataEvent): void
{
    $storageZone = new \DateTimeZone(date_default_timezone_get()); // the zone your dates are stored in

    $start = (clone $setDataEvent->getStart())->setTimezone($storageZone);
    $end = (clone $setDataEvent->getEnd())->setTimezone($storageZone);

    $bookings = $this->bookingRepository
        ->createQueryBuilder('booking')
        ->where('booking.beginAt < :end')
        ->andWhere('booking.endAt > :start OR booking.endAt IS NULL')
        ->setParameter('start', $start->format('Y-m-d H:i:s'))
        ->setParameter('end', $end->format('Y-m-d H:i:s'))
        ->getQuery()
        ->getResult()
    ;

    // The calendar's zone, e.g. to compute the day of an all-day event as the user sees it.
    $timeZone = $setDataEvent->getTimeZone() ?? $storageZone;

    // ...
}
```

Clone the dates before changing them: `getStart()` and `getEnd()` return the event's own `\DateTime` objects.

## All-day events

All-day events are serialised as plain dates (`"start": "2026-10-15"`, and `"end"` too when set), which is what FullCalendar expects for all-day events. A date carries no offset, so FullCalendar does not shift the event to the previous or next day when the calendar's zone differs from the server's.

Timed events keep an ISO 8601 date-time with offset (`2026-10-15T09:00:00+02:00`), which FullCalendar converts to the calendar's zone.
