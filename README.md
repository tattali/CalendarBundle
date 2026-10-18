CalendarBundle - FullCalendar.js integration
===========================================

[![CI](https://img.shields.io/github/actions/workflow/status/tattali/CalendarBundle/ci.yml?branch=main&label=CI)](https://github.com/tattali/CalendarBundle/actions/workflows/ci.yml?query=branch%3Amain)
[![Quality Gate](https://img.shields.io/sonar/quality_gate/tattali_CalendarBundle?server=https%3A%2F%2Fsonarcloud.io)](https://sonarcloud.io/summary/new_code?id=tattali_CalendarBundle)
[![Coverage](https://img.shields.io/sonar/coverage/tattali_CalendarBundle?server=https%3A%2F%2Fsonarcloud.io)](https://sonarcloud.io/summary/new_code?id=tattali_CalendarBundle)
[![Latest Version](https://img.shields.io/packagist/v/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle)
[![Downloads](https://img.shields.io/packagist/dt/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle/stats)
[![PHP](https://img.shields.io/packagist/dependency-v/tattali/calendar-bundle/php)](https://packagist.org/packages/tattali/calendar-bundle)
[![Symfony](https://img.shields.io/packagist/dependency-v/tattali/calendar-bundle/symfony/framework-bundle?label=symfony)](https://packagist.org/packages/tattali/calendar-bundle)
[![License](https://img.shields.io/packagist/l/tattali/calendar-bundle)](LICENSE)

This bundle allow you to integrate [FullCalendar.js](https://fullcalendar.io/) library in your Symfony 8 project.

<p align="center">
  <img src="https://user-images.githubusercontent.com/10502887/56835704-47687080-6875-11e9-9102-0533d2bbbf18.png" alt="Calendar image">
</p>

Versions
--------

Each major version of the bundle supports one Symfony major version:

| Bundle | Branch                                                     | Symfony    | PHP  |
|--------|------------------------------------------------------------|------------|------|
| 8.x    | [main](https://github.com/tattali/CalendarBundle/tree/main) | 8          | 8.4+ |
| 7.x    | [7.x](https://github.com/tattali/CalendarBundle/tree/7.x)   | 7          | 8.2+ |
| 6.x    | [6.x](https://github.com/tattali/CalendarBundle/tree/6.x)   | 6.4        | 8.1+ |
| 5.x    | [5.x](https://github.com/tattali/CalendarBundle/tree/5.x)   | 5.4        | 8.0+ |
| 1.x    | tags `v1.*`                                                | 3.4 to 7   | 7.1+ |

New features go to `main`. The `7.x` and `6.x` branches get bug fixes, and `5.x` gets security fixes only.

Documentation
-------------

The source of the documentation is stored in the `docs/` folder in this bundle

### Installation

```sh
composer require tattali/calendar-bundle
```

Import the routes:
```yaml
# config/routes/calendar.yaml
calendar:
    resource: '@CalendarBundle/config/routing.yaml'
```

### Quick Start

Create a subscriber to populate calendar data:

```php
// src/EventSubscriber/CalendarSubscriber.php
<?php

namespace App\EventSubscriber;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent): void
    {
        $start = $setDataEvent->getStart();
        $end = $setDataEvent->getEnd();
        $filters = $setDataEvent->getFilters();

        // You may want to make a custom query from your database to fill the calendar

        $setDataEvent->addEvent(new Event(
            'Event title',
            new \DateTime('Tuesday this week'),
            new \DateTime('Wednesday this week')
        ));

        // If the end date is null or not defined, it creates a all day event
        $setDataEvent->addEvent(new Event(
            'All day event',
            new \DateTime('Friday this week')
        ));
    }
}
```

Add the calendar to your template:
```twig
{% block body %}
    <div id="calendar-holder"></div>
{% endblock %}

{% block javascripts %}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new FullCalendar.Calendar(document.getElementById('calendar-holder'), {
                initialView: 'dayGridMonth',
                eventSources: [{
                    url: '/fc-load-events',
                    method: 'POST',
                    extraParams: { filters: JSON.stringify({}) }
                }]
            }).render();
        });
    </script>
{% endblock %}
```

### Documentation

- [Configuration](docs/configuration.md) - Caching, JSON depth limit, routing
- [Doctrine CRUD](docs/doctrine-crud.md) - Create, update, delete events with Doctrine
- [Webpack Encore](docs/es6-encore.md) - ES6 module setup
- [Multi-calendar](docs/multi-calendar.md) - Multiple calendars on one page
- [Security](docs/configuration.md#security) - Securing the endpoint
- [Time zones](docs/timezone.md) - FullCalendar `timeZone` parameter and all-day dates

#### Upgrade Guides

- [Migrating to v8.3](docs/upgrade-to-83.md)
- [Migrating to v8.2](docs/upgrade-to-82.md)
- [Migrating to v8.1](docs/upgrade-to-81.md)

### Troubleshooting

Debug AJAX requests using browser Network monitor (`Ctrl+Shift+E` Firefox, `Ctrl+Shift+I` Chrome).

### License

MIT - See [LICENSE](LICENSE)
