<?php

namespace staabm\sysmonitor\events;

use staabm\sysmonitor\SerializableException;

class RequestExceptionEvent extends AbstractEvent
{
    /**
     * @var \Error|\Exception|SerializableException
     */
    public $exception;
}
