# Webpack Encore

Install Encore with `composer` and `npm`
```sh
composer require symfony/webpack-encore-bundle
npm install
```

Install FullCalendar then add plugins https://fullcalendar.io/docs/plugin-index
```sh
npm install @fullcalendar/core
npm install @fullcalendar/interaction @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/list
```

Your calendar template should look like that
```twig
{# templates/booking/calendar.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <a href="{{ path('app_booking_new') }}">Create new booking</a>

    <div
        id="calendar-holder"
        data-events-url="{{ path('fc_load_events') }}"
    ></div>
{% endblock %}

{% block stylesheets %}
    {{ encore_entry_link_tags('calendar') }}
{% endblock %}

{% block javascripts %}
    {{ encore_entry_script_tags('calendar') }}
{% endblock %}
```

Register the calendar component
```diff
# webpack.config.js

.addEntry('app', './assets/js/app.js')
- //.addEntry('page1', './assets/js/page1.js')
+ .addEntry('calendar', './assets/js/calendar/index.js')
//.addEntry('page2', './assets/js/page2.js')
```

Create the calendar component
```js
// assets/js/calendar/index.js
import { Calendar } from '@fullcalendar/core';
import interactionPlugin from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

import './index.css'; // this will create a calendar.css file reachable to 'encore_entry_link_tags'

document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar-holder');

  const { eventsUrl } = calendarEl.dataset;

  const calendar = new Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    editable: true,
    eventSources: [
      {
        url: eventsUrl,
        method: 'POST',
        extraParams: {
          filters: JSON.stringify({}) // pass your parameters to the subscriber
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
    plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
  });

  calendar.render();
});
```

Modify the calendar with css
```css
/* assets/js/calendar/index.css */

@import url('https://fonts.googleapis.com/css?family=Muli:400,900&display=swap');

#calendar-holder {
  font-family: 'Muli', sans-serif;
  width: 800px;
  margin: 0 auto;
}

.fc-toolbar h2,
.fc th {
  font-weight: inherit;
}
```


To apply changes after any modification to the `js` or `css` run
```sh
npm run dev # or npm run watch
```
