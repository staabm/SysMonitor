<?php

namespace staabm\sysmonitor;

use staabm\sysmonitor\events\AbstractEvent;

class SystemEvent
{
    // the bigger the value of the severity, the more important is it
    const SEVERITY_NORMAL = 5, SEVERITY_URGENT = 10;

    /**
     *
     * @var string
     */
    public $title;

    /**
     * One of the SystemEvent::SEVERITY_* constants
     *
     * @var int
     */
    public $severity;

    /**
     * Unixtimestamp, when this event occured
     *
     * @var int
     */
    public $time;

    /**
     * A hash representing this event.
     * Event with this same origin must have the same hash.
     *
     * @var string
     */
    public $hash;

    /**
     *
     * @var RequestEnvironment
     */
    public $env;

    /**
     *
     * @var AbstractEvent the event which caused the SystemEvent
     */
    public $origin;

    public function __construct()
    {
        $this->severity = self::SEVERITY_NORMAL;
        $this->time = time();
    }
}
