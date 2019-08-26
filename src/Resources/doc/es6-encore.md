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
    <a href="{{ path('booking_new') }}">Create new booking</a>

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

import "@fullcalendar/core/main.css";
import "@fullcalendar/daygrid/main.css";
import "@fullcalendar/timegrid/main.css";

import "./index.css"; // this will create a calendar.css file reachable to 'encore_entry_link_tags'

document.addEventListener("DOMContentLoaded", () => {
    var calendarEl = document.getElementById("calendar-holder");

    var eventsUrl = calendarEl.dataset.eventsUrl;

    var calendar = new Calendar(calendarEl, {
        defaultView: "dayGridMonth",
        editable: true,
        eventSources: [
            {
                url: eventsUrl,
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
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin], // https://fullcalendar.io/docs/plugin-index
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
