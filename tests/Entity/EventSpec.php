<?php

namespace Tests\CalendarBundle\Entity;

use PhpSpec\ObjectBehavior;
use CalendarBundle\Entity\Event;

class EventSpec extends ObjectBehavior
{
    private $title = 'Title';
    private $start;
    private $end = null;
    private $options = [];

    public function let()
    {
        $this->start = new \DateTime('2019-03-18 08:41:31');
        $this->end = new \DateTime('2019-03-18 08:41:31');
        $this->options = ['textColor' => 'blue'];

        $this->beAnInstanceOf(Event::class);
        $this->beConstructedWith($this->title, $this->start, $this->end, $this->options);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Event::class);
    }

    public function it_has_require_values()
    {
        $this->getTitle()->shouldReturn($this->title);
        $this->getStart()->shouldReturn($this->start);
        $this->getEnd()->shouldReturn($this->end);
        $this->getOptions()->shouldReturn($this->options);
    }

    public function it_should_convert_its_values_in_to_array()
    {
        $url = 'url';
        $urlValue = 'www.url.com';

        $options = [
            $url => $urlValue,
        ];

        $allDay = false;

        $this->setAllDay($allDay);

        $this->addOption('be-removed', 'value');
        $this->removeOption('be-removed');

        $this->removeOption('no-found-key')->shouldReturn(null);

        $this->setOptions($options);
        $this->getOptions()->shouldReturn($options);

        $this->getOption($url, $urlValue)->shouldReturn($urlValue);

        $this->toArray()->shouldReturn(
            [
                'title' => $this->title,
                'start' => $this->start->format('Y-m-d\\TH:i:sP'),
                'allDay' => $allDay,
                'end' => $this->end->format('Y-m-d\\TH:i:sP'),
                $url => $urlValue,
            ]
        );
    }
}
