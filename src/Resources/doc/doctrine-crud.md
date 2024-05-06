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

> **NOTE:** `composer req doctrine` then update the database url in your `.env` and run `bin/console d:d:c`

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

### 2. Create the entity

Generate or create an entity with at least a *start date* and a *title*. You also can add an *end date*

```sh
# Symfony flex (Need the maker: `composer req --dev maker`)
php bin/console make:entity
```

In this example we call the entity `Booking`
```php
// src/Entity/Booking.php
<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $beginAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $endAt = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeginAt(): ?\DateTime
    {
        return $this->beginAt;
    }

    public function setBeginAt(\DateTime $beginAt): static
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?\DateTime
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTime $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
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
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }
}
```

Then, update your database schema
```
php bin/console doctrine:migration:diff
php bin/console doctrine:migration:migrate -n
```


### 3. Create the CRUD
You can now create or generate the CRUD of your entity

The following command will generate a `BookingController` with `index()`, `new()`, `show()`, `edit()` and `delete()` actions

And also the according `templates` and `form` (You may need to install additional packages)
```sh
php bin/console make:crud Booking
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

#[Route(path: '/booking')]
class BookingController extends AbstractController
{
    // ...

    #[Route(path: '/calendar', name: 'app_booking_calendar')]
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

We now have to link the CRUD to the calendar by adding the `app_booking_show` route in each events

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
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly UrlGeneratorInterface $router
    ) {}

    // ...
}
```

Then use `setUrl()` on each created event to link them to their own show action
```php
$bookingEvent->addOption(
    'url',
    $this->router->generate('app_booking_show', [
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
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly UrlGeneratorInterface $router
    ) {}

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
             */
            $bookingEvent->setOptions([
                'backgroundColor' => 'red',
                'borderColor' => 'red',
            ]);
            $bookingEvent->addOption(
                'url',
                $this->router->generate('app_booking_show', [
                    'id' => $booking->getId(),
                ])
            );

            // finally, add the event to the CalendarEvent to fill the calendar
            $setDataEvent->addEvent($bookingEvent);
        }
    }
}
```

### 5. Display your calendar

Then create the calendar template

add a link to the `app_booking_new` form

```twig
<a href="{{ path('app_booking_new') }}">Create new booking</a>
```

and include the `calendar-holder`

```twig
<div id="calendar-holder"></div>
```

Full template:

```twig
{# templates/booking/calendar.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <a href="{{ path('app_booking_new') }}">Create new booking</a>

    <div id="calendar-holder"></div>
{% endblock %}

{% block javascripts %}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', () => {
            const calendarEl = document.getElementById('calendar-holder');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: true,
                eventSources: [
                    {
                        url: "{{ path('fc_load_events') }}",
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
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                timeZone: 'UTC',
            });

            calendar.render();
        });
    </script>
{% endblock %}
```

You can use [Plugins](https://fullcalendar.io/docs/plugin-index) to reduce loadtime.

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
