<?php

namespace staabm\sysmonitor\events;

class RequestExceptionEvent extends AbstractEvent {
    /**
     * @var Exception
     */
    public $exception;
}