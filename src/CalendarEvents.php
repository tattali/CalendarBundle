<?php

declare(strict_types=1);

namespace CalendarBundle;

class CalendarEvents
{
    /**
     * The SET_DATA event occurs before serializing events.
     *
     * This event allows you to fill the calendar with your events.
     *
     * @Event("CalendarBundle\Event\CalendarEvent")
     */
    const SET_DATA = 'calendar.set_data';
}
