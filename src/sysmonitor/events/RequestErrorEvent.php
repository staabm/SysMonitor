<?php

namespace staabm\sysmonitor\events;

class RequestErrorEvent extends AbstractEvent {
    /**
     * @var string
     */
    public $message;

    /**
     * One of the E_*_ERROR, E_*_WARNING constants
     *
     * @var int
     */
    public $type;

    /**
     * @var string
     */
    public $file;

    /**
     * @var int
     */
    public $line;

    /**
     * @var string[]|null
     */
    public $trace;
}