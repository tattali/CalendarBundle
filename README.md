CalendarBundle - FullCalendar.js integration
===========================================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tattali/CalendarBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tattali/CalendarBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tattali/CalendarBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tattali/CalendarBundle/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/tattali/CalendarBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/tattali/CalendarBundle/build-status/master)
[![Total Downloads](https://poser.pugx.org/tattali/calendar-bundle/downloads)](https://packagist.org/packages/tattali/calendar-bundle)
[![Latest Stable Version](https://poser.pugx.org/tattali/calendar-bundle/v/stable)](https://packagist.org/packages/tattali/calendar-bundle)

This bundle allow you to integrate [FullCalendar.js](http://fullcalendar.io/) library in your Symfony 4 project.

<p align="center">
  <img src="https://user-images.githubusercontent.com/10502887/56835704-47687080-6875-11e9-9102-0533d2bbbf18.png" alt="Calendar image">
</p>

* Symfony 3.4+ or Symfony 4.0+
* PHP v7.1+
* No storage dependencies (Compatible with: Doctrine, MongoDB, CouchDB...)

Documentation
-------------

The source of the documentation is stored in the `src/Resources/doc/` folder in this bundle

- [Link the calendar to a CRUD and allow create, update, delete & show events](src/Resources/doc/doctrine-crud.md)
- [Webpack Encore and fullcalendar.js](src/Resources/doc/es6-encore.md)

### Installation

1. [Download CalendarBundle using composer](#1-download-calendarbundle-using-composer)
2. [Create the subscriber](#2-create-the-subscriber)
3. [Add styles and scripts in your template](#3-add-styles-and-scripts-in-your-template)

#### 1. Download CalendarBundle using composer

```sh
$ composer require tattali/calendar-bundle
```
The recipe will import the routes for you

Check the existence of the file `config/routes/calendar.yaml` or create it
```yaml
# config/routes/calendar.yaml
calendar:
    resource: "@CalendarBundle/Resources/config/routing.yaml"
```

#### 2. Create the subscriber
You need to create a subscriber class to load your data into the calendar.

This subscriber must be registered only if autoconfigure is false.
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

use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();

        // You may want to make a custom query from your database to fill the calendar

        $calendar->addEvent(new Event(
            'Event 1',
            new \DateTime('Tuesday this week'),
            new \DateTime('Wednesdays this week')
        ));

        // If the end date is null or not defined, it creates a all day event
        $calendar->addEvent(new Event(
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
    {% include '@Calendar/calendar.html' %}
{% endblock %}
```

Add styles and js. Click [here](https://fullcalendar.io/download) to see other css and js download methods, you can also found the [plugins list](https://fullcalendar.io/docs/plugin-index)

```twig
{% block stylesheets %}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.1.0/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.1.0/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.1.0/main.min.css">
{% endblock %}

{% block javascripts %}
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.1.0/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@4.1.0/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.1.0/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.1.0/main.min.js"></script>
{% endblock %}
```

### Basic functionalities

You will probably want to customize the Calendar javascript to fit the needs of your application.
To do this, you can copy the following settings and modify them by consulting the [fullcalendar.js documentation](https://fullcalendar.io/docs). You can also look at the [options.ts](https://github.com/fullcalendar/fullcalendar/blob/master/src/core/options.ts) file as an option reference.
```js
document.addEventListener('DOMContentLoaded', () => {
    var calendarEl = document.getElementById('calendar-holder');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        defaultView: 'dayGridMonth',
        editable: true,
        eventSources: [
            {
                url: "/fc-load-events",
                method: "POST",
                extraParams: {
                    filters: JSON.stringify({})
                },
                failure: () => {
                    // alert("There was an error while fetching FullCalendar!");
                },
            },
        ],
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        plugins: [ 'interaction', 'dayGrid', 'timeGrid' ], // https://fullcalendar.io/docs/plugin-index
        timeZone: 'UTC',
    });
    calendar.render();
});
```

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
