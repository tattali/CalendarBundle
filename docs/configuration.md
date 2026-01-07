# Configuration

CalendarBundle provides optional configuration for caching and security.

## Full Configuration Reference

```yaml
# config/packages/calendar.yaml
calendar:
    cache_max_age: 300    # HTTP cache max-age in seconds (default: 300, 0 to disable)
    json_max_depth: 4     # Max JSON nesting for filters (default: 4, range: 1-512)
```

## HTTP Caching

The bundle includes built-in HTTP caching using ETag headers for the `/fc-load-events` endpoint.

### How It Works

When `cache_max_age > 0`:
- Response includes `Cache-Control: public, max-age=X` header
- ETag header generated using xxh3 hash (fast, non-cryptographic)
- Clients sending `If-None-Match` header receive `304 Not Modified` when content unchanged

### Configuration

```yaml
calendar:
    # Cache for 5 minutes (default)
    cache_max_age: 300

    # Cache for 1 hour
    cache_max_age: 3600

    # Disable caching entirely
    cache_max_age: 0
```

### When to Disable Caching

Set `cache_max_age: 0` if:
- Events change frequently and must be real-time
- You implement your own caching strategy
- Testing/debugging requires fresh responses

## JSON Filters Security

The `filters` query parameter accepts JSON that is passed to your subscriber. The `json_max_depth` option limits nesting depth to prevent DoS attacks.

### Configuration

```yaml
calendar:
    # Default - sufficient for most use cases
    json_max_depth: 4

    # Allow deeper nesting if needed
    json_max_depth: 10
```

### Example

With `json_max_depth: 4`, these filters are valid:
```json
{"category": "meeting", "tags": ["work", "urgent"]}
```

But deeply nested structures will throw a `BadRequestHttpException`:
```json
{"a": {"b": {"c": {"d": {"e": "too deep"}}}}}
```

## Routing Configuration

The bundle provides a default route. Import it in your application:

```yaml
# config/routes/calendar.yaml
calendar:
    resource: '@CalendarBundle/config/routing.yaml'
```

Or define your own route with custom path:

```yaml
# config/routes/calendar.yaml
fc_load_events:
    path: /api/calendar/events
    controller: CalendarBundle\Controller\CalendarController::load
```

## Security

### Access Control

If your application uses Symfony's security firewall, configure access to the endpoint:

```yaml
# config/packages/security.yaml
security:
    access_control:
        # Public access
        - { path: ^/fc-load-events, roles: PUBLIC_ACCESS }

        # Or restrict to authenticated users
        - { path: ^/fc-load-events, roles: ROLE_USER }
```

### Custom Controller with Security Attributes

For fine-grained control, wrap the bundle's controller:

```php
// src/Controller/CalendarController.php
<?php

namespace App\Controller;

use CalendarBundle\Controller\CalendarController as BaseCalendarController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CalendarController
{
    public function __construct(
        private BaseCalendarController $calendarController,
    ) {}

    #[Route('/fc-load-events', name: 'fc_load_events', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function load(Request $request): JsonResponse
    {
        return $this->calendarController->load($request);
    }
}
```

Then remove or comment out `config/routes/calendar.yaml`.
