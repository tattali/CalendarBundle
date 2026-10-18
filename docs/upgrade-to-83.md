# Migrating from v8.2 to v8.3

This guide explains how to upgrade your application from CalendarBundle 8.2 to 8.3.

## New Features

### GET Requests on the Events Feed

`/fc-load-events` now reads `start`, `end` and `filters` from the query string on GET and HEAD requests, which is what FullCalendar's JSON feed sends by default. POST requests still read the body.

### 304 Not Modified

A GET request whose `If-None-Match` header matches the feed's ETag now gets an empty `304 Not Modified` response.

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
