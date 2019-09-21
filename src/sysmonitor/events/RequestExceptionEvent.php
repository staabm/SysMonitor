<?php

namespace staabm\sysmonitor\events;

use staabm\sysmonitor\SerializableException;

class RequestExceptionEvent extends AbstractEvent
{
    /**
     * @var \Exception|SerializableException
     */
    public $exception;
}
