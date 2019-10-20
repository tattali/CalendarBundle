# Basic CRUD example with Doctrine and CalendarBundle

This example allow you to create, update, delete & show events with `CalendarBundle`

## Installation

1. [Download CalendarBundle using composer](#1-download-calendarbundle-using-composer)
2. [Create the entity](#2-create-the-entity)
3. [Create the CRUD](#3-create-the-crud)
4. [Use an event subscriber to connect all of this together](#4-use-an-event-subscriber-to-connect-all-of-this-together)
5. [Display your calendar](#5-display-your-calendar)

### 1. Download CalendarBundle using composer

This documentation assumes that doctrine is already installed.

> **NOTE:** `composer req symfony/orm-pack` then update the database url in your `.env` and run `bin/console d:d:c`

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

### 2. Create the entity

Generate or create an entity with at least a *start date* and a *title*. You also can add an *end date*

```sh
# Symfony flex (Need the maker: `composer req --dev symfony/maker-bundle`)
$ php bin/console make:entity
```

In this example we call the entity `Booking`
```php
// src/Entity/Booking.php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookingRepository")
 */
class Booking
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $beginAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endAt = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeginAt(): ?\DateTimeInterface
    {
        return $this->beginAt;
    }

    public function setBeginAt(\DateTimeInterface $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt = null): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
```

```php
// src/Repository/BookingRepository.php
<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Booking::class);
    }
}
```

Then, update your database schema
```
$ php bin/console doctrine:migration:diff
$ php bin/console doctrine:migration:migrate -n
```


### 3. Create the CRUD
You can now create or generate the CRUD of your entity

The following command will generate a `BookingController` with `index()`, `new()`, `show()`, `edit()` and `delete()` actions

And also the according `templates` and `form` (You may need to install additional packages)
```sh
$ php bin/console make:crud Booking
```

Edit the `BookingController` by adding a `calendar()` action to display the calendar
```php
// src/Controller/BookingController.php
<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/booking")
 */
class BookingController extends AbstractController
{
    // ...

    /**
     * @Route("/calendar", name="booking_calendar", methods={"GET"})
     */
    public function calendar(): Response
    {
        return $this->render('booking/calendar.html.twig');
    }

    // ...
}
```

### 4. Use an event subscriber to connect all of this together

This subscriber must be registered only if autoconfigure is false.
```yaml
# config/services.yaml
services:
    # ...

    App\EventSubscriber\CalendarSubscriber:
```

We now have to link the CRUD to the calendar by adding the `booking_show` route in each events

[TL;DR](#full-subscriber)

To do this create a subscriber with access to the router component and your entity repository
```php
// src/EventSubscriber/CalendarSubscriber.php
<?php

namespace App\EventSubscriber;

// ...
use App\Repository\BookingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $bookingRepository;
    private $router;

    public function __construct(
        BookingRepository $bookingRepository,
        UrlGeneratorInterface $router
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->router = $router;
    }

    // ...
}
```

Then use `setUrl()` on each created event to link them to their own show action
```php
$bookingEvent->addOption(
    'url',
    $this->router->generate('booking_show', [
        'id' => $booking->getId(),
    ])
);
```

#### Full subscriber

Full subscriber with `Booking` entity. Modify it to fit your needs.

```php
// src/EventSubscriber/CalendarSubscriber.php
<?php

namespace App\EventSubscriber;

use App\Repository\BookingRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $bookingRepository;
    private $router;

    public function __construct(
        BookingRepository $bookingRepository,
        UrlGeneratorInterface $router
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->router = $router;
    }

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

        // Modify the query to fit to your entity and needs
        // Change booking.beginAt by your start date property
        $bookings = $this->bookingRepository
            ->createQueryBuilder('booking')
            ->where('booking.beginAt BETWEEN :start and :end OR booking.endAt BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult()
        ;

        foreach ($bookings as $booking) {
            // this create the events with your data (here booking data) to fill calendar
            $bookingEvent = new Event(
                $booking->getTitle(),
                $booking->getBeginAt(),
                $booking->getEndAt() // If the end date is null or not defined, a all day event is created.
            );

            /*
             * Add custom options to events
             *
             * For more information see: https://fullcalendar.io/docs/event-object
             * and: https://github.com/fullcalendar/fullcalendar/blob/master/src/core/options.ts
             */

            $bookingEvent->setOptions([
                'backgroundColor' => 'red',
                'borderColor' => 'red',
            ]);
            $bookingEvent->addOption(
                'url',
                $this->router->generate('booking_show', [
                    'id' => $booking->getId(),
                ])
            );

            // finally, add the event to the CalendarEvent to fill the calendar
            $calendar->addEvent($bookingEvent);
        }
    }
}
```

### 5. Display your calendar

Then create the calendar template

add a link to the `booking_new` form

```twig
<a href="{{ path('booking_new') }}">Create new booking</a>
```

and include the `calendar-holder`

```twig
{% include '@Calendar/calendar.html' %}
```

Full template:

```twig
{# templates/booking/calendar.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <a href="{{ path('booking_new') }}">Create new booking</a>

    <div id="calendar-holder"></div>
{% endblock %}

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

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', () => {
            var calendarEl = document.getElementById('calendar-holder');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                defaultView: 'dayGridMonth',
                editable: true,
                eventSources: [
                    {
                        url: "{{ path('fc_load_events') }}",
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
    </script>
{% endblock %}
```

* Now visit: <http://localhost:8000/booking/calendar>

* In the calendar when you click on an event it call the `show()` action that should contains an edit and delete link

* And when you create a new `Booking` (or your custom entity name) it appear on the calendar

* If you have created a custom entity don't forget to modify the subscriber:
    - Replace all `Booking` or `booking` by your custom entity name
    - In the query near the `where` modify `beginAt` to your custom start date property
    - Also when you create each `Event` in the `foreach` modify the getters to fit with your entity

### Next steps

* You may want to customize the fullcalendar.js settings to meet your application needs. To do this, see the [official fullcalendar documentation](https://fullcalendar.io/docs#toc).

<br>

* To debug AJAX requests, show the Network monitor, then reload the page. Finally click on `fc-load-events` and select the `Response` or `Preview` tab
    - Firefox: `Ctrl + Shift + E` ( `Command + Option + E` on Mac )
    - Chrome: `Ctrl + Shift + I` ( `Command + Option + I` on Mac )
