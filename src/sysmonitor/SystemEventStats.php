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

    /**
     * @param int $count
     */
    public function __construct(SystemEvent $sysEvt, $count)
    {
        $this->count = $count;
        $this->sysEvt = $sysEvt;
    }

    /**
     * @return SystemEvent
     */
    public function getEvent()
    {
        return $this->sysEvt;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
