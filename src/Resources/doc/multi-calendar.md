# Add multiple calendar

In the front template, add a parameter, if the parameter is set to 'booking-calendar' show bookings
```js
extraParams: {
    filters: JSON.stringify({ 'calendar-id': 'booking-calendar' })
},
```

In the other front template, add the same parameter, with an other value 'other-calendar' to show others
```js
extraParams: {
    filters: JSON.stringify({ 'calendar-id': 'other-calendar' })
},
```

Then use this kind of logic
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
        private BookingRepository $bookingRepository,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $event): void
    {
        $start = $event->getStart();
        $end = $event->getEnd();
        $filters = $event->getFilters();

        match ($filters['calendar-id']) {
            'booking-calendar' => $this->fillCalendarWithBookings($event, $start, $end, $filters),
            'other-calendar' => $this->fillCalendarWithOthers($event, $start, $end, $filters),
        }
    }

    public function fillCalendarWithBookings(SetDataEvent $event, \DateTime $start, \DateTime $end, array $filters)
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

            // finally, add the event to the SetDataEvent to fill the calendar
            $event->addEvent($bookingEvent);
        }
    }
}
```
