CalendarBundle - FullCalendar.js integration
===========================================

[![CI](https://img.shields.io/github/actions/workflow/status/tattali/CalendarBundle/ci.yml?branch=7.x&label=CI)](https://github.com/tattali/CalendarBundle/actions/workflows/ci.yml?query=branch%3A7.x)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tattali_CalendarBundle&metric=coverage)](https://sonarcloud.io/summary/new_code?id=tattali_CalendarBundle)
[![Packagist Downloads](https://img.shields.io/packagist/dm/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle)
[![Packagist Version](https://img.shields.io/packagist/v/tattali/calendar-bundle)](https://packagist.org/packages/tattali/calendar-bundle)

This bundle allow you to integrate [FullCalendar.js](https://fullcalendar.io/) library in your Symfony 7 project.

This is the 7.x branch: Symfony 7.0 to 7.4 and PHP 8.2 or later. It gets bug fixes.

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

The source of the documentation is stored in the `src/Resources/doc/` folder in this bundle

- [Link the calendar to a CRUD and allow create, update, delete & show events](src/Resources/doc/doctrine-crud.md)
- [Webpack Encore and fullcalendar.js](src/Resources/doc/es6-encore.md)
- [Multi calendar](src/Resources/doc/multi-calendar.md)

### Installation

1. [Download CalendarBundle using composer](#1-download-calendarbundle-using-composer)
2. [Create the subscriber](#2-create-the-subscriber)
3. [Add styles and scripts in your template](#3-add-styles-and-scripts-in-your-template)

#### 1. Download CalendarBundle using composer

```sh
composer require tattali/calendar-bundle
```
The recipe will import the routes for you

Check the existence of the file `config/routes/calendar.yaml` or create it
```yaml
# config/routes/calendar.yaml
calendar:
    resource: '@CalendarBundle/Resources/config/routing.yaml'
```

#### 2. Create the subscriber
You need to create a subscriber class to load your data into the calendar.

This subscriber must be registered **only if autoconfigure is false**.
```yaml
# config/services.yaml
services:
    # ...

    App\EventSubscriber\CalendarSubscriber:
```

Then, create the subscriber class to fill the calendar

See the [doctrine subscriber example](src/Resources/doc/doctrine-crud.md#full-subscriber)

```php
// src/EventSubscriber/CalendarSubscriber.php
<?php

namespace App\EventSubscriber;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent)
    {
        $start = $setDataEvent->getStart();
        $end = $setDataEvent->getEnd();
        $filters = $setDataEvent->getFilters();

        // You may want to make a custom query from your database to fill the calendar

        $setDataEvent->addEvent(new Event(
            'Event 1',
            new \DateTime('Tuesday this week'),
            new \DateTime('Wednesdays this week')
        ));

        // If the end date is null or not defined, it creates a all day event
        $setDataEvent->addEvent(new Event(
            'All day event',
            new \DateTime('Friday this week')
        ));
    }
}
```

#### 3. Add styles and scripts in your template

Include the html template were you want to display the calendar:

```twig
{% block body %}
    <div id="calendar-holder"></div>
{% endblock %}
```

Add styles and js. Click [here](https://fullcalendar.io/download) to see other css and js download methods, you can also found the [plugins list](https://fullcalendar.io/docs/plugin-index)

```twig
{% block javascripts %}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
{% endblock %}
```

### Basic functionalities

You will probably want to customize the Calendar javascript to fit the needs of your application.
To do this, you can copy the following settings and modify them by consulting the [fullcalendar.js documentation](https://fullcalendar.io/docs).
```js
document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar-holder');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        defaultView: 'dayGridMonth',
        editable: true,
        eventSources: [
            {
                url: '/fc-load-events',
                method: 'POST',
                extraParams: {
                    filters: JSON.stringify({})
                },
                failure: () => {
                    // alert('There was an error while fetching FullCalendar!');
                },
            },
        ],
        headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        timeZone: 'UTC',
    });

    calendar.render();
});
```

You can use [Plugins](https://fullcalendar.io/docs/plugin-index) to reduce loadtime.

## Troubleshoot AJAX requests

* To debug AJAX requests, show the Network monitor, then reload the page. Finally click on `fc-load-events` and select the `Response` or `Preview` tab
    - Firefox: `Ctrl + Shift + E` ( `Command + Option + E` on Mac )
    - Chrome: `Ctrl + Shift + I` ( `Command + Option + I` on Mac )

Contribute and feedback
-----------------------

Any feedback and contribution will be very appreciated.

License
-------

This bundle is under the MIT license. See the complete [license](LICENSE) in the bundle
