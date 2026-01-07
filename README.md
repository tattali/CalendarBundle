CalendarBundle - FullCalendar.js integration
===========================================

[![Build Status](https://github.com/tattali/CalendarBundle/actions/workflows/code_checks.yaml/badge.svg)](https://github.com/tattali/CalendarBundle/actions)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tattali_CalendarBundle&metric=coverage)](https://sonarcloud.io/summary/new_code?id=tattali_CalendarBundle)
[![Packagist Downloads](https://img.shields.io/packagist/dm/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle)
[![Packagist Version](https://img.shields.io/packagist/v/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle)

This bundle allow you to integrate [FullCalendar.js](https://fullcalendar.io/) library in your Symfony 5.4 to 8 project.

<p align="center">
  <img src="https://user-images.githubusercontent.com/10502887/56835704-47687080-6875-11e9-9102-0533d2bbbf18.png" alt="Calendar image">
</p>

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

#### Upgrade Guides

- [Migrating to v8.2](docs/upgrade-to-82.md)
- [Migrating to v8.1](docs/upgrade-to-81.md)

### Troubleshooting

Debug AJAX requests using browser Network monitor (`Ctrl+Shift+E` Firefox, `Ctrl+Shift+I` Chrome).

### License

MIT - See [LICENSE](LICENSE)
