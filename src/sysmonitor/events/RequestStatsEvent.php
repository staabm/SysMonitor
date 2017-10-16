<?php

namespace staabm\sysmonitor\events;

class RequestStatsEvent extends AbstractEvent
{
    /**
     * @var int
     */
    public $usedQueries;

    /**
     * @var int
     */
    public $usedConnections;

    /**
     * @var float
     */
    public $peakMemory;

    /**
     * @var float
     */
    public $requestTime;
}
