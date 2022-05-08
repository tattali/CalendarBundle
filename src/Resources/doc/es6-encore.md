# Webpack Encore

Install Encore with `composer` and `yarn`
```sh
composer require symfony/webpack-encore-bundle
yarn install
```

Install FullCalendar then add plugins https://fullcalendar.io/docs/plugin-index
```sh
yarn add @fullcalendar/core
yarn add @fullcalendar/interaction @fullcalendar/daygrid @fullcalendar/timegrid
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
import { Calendar } from "@fullcalendar/core";
import interactionPlugin from "@fullcalendar/interaction";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";

import "./index.css"; // this will create a calendar.css file reachable to 'encore_entry_link_tags'

document.addEventListener("DOMContentLoaded", () => {
  let calendarEl = document.getElementById("calendar-holder");

  let { eventsUrl } = calendarEl.dataset;

  let calendar = new Calendar(calendarEl, {
    editable: true,
    eventSources: [
      {
        url: eventsUrl,
        method: "POST",
        extraParams: {
          filters: JSON.stringify({}) // pass your parameters to the subscriber
        },
        failure: () => {
          // alert("There was an error while fetching FullCalendar!");
        },
      },
    ],
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek"
    },
    initialView: "dayGridMonth",
    navLinks: true, // can click day/week names to navigate views
    plugins: [ interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin ],
    timeZone: "UTC",
  });

  calendar.render();
});
```

Modify the calendar with css
```css
/* assets/js/calendar/index.css */

@import url("https://fonts.googleapis.com/css?family=Muli:400,900&display=swap");

#calendar-holder {
  font-family: "Muli", sans-serif;
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
yarn dev # or yarn dev --watch
```
