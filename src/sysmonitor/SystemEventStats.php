<?php

namespace staabm\sysmonitor;

class SystemEventStats
{

    /**
     * @var SystemEvent
     */
    private $sysEvt;

    /**
     * @var int
     */
    private $count;

    public function __construct(SystemEvent $sysEvt, $count)
    {
        $this->count = $count;
        $this->sysEvt = $sysEvt;
    }

    public function getEvent()
    {
        return $this->sysEvt;
    }

    public function getCount()
    {
        return $this->count;
    }
}
