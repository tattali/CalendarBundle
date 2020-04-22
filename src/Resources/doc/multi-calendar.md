# Add multiple calendar

In the front template, add a parameter, if the parameter is set to "booking-calendar" show bookings
```js
extraParams: {
    filters: JSON.stringify({ "calendar-id": "booking-calendar" })
},
```

In the other front template, add the same parameter, with an other value "other-calendar" to show others
```js
extraParams: {
    filters: JSON.stringify({ "calendar-id": "other-calendar" })
},
```

Then use this kind of logic
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

       switch($filters['calendar-id']) {
            case 'booking-calendar':
                $this->fillCalendarWithBookings($calendar, $start, $end, $filters);
                break;
            case 'other-calendar':
                $this->fillCalendarWithOthers($calendar, $start, $end, $filters);
                break;
       }
    }

    public function fillCalendarWithBookings(CalendarEvent $calendar, \DateTimeInterface $start, \DateTimeInterface $end, array $filters)
    {
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
