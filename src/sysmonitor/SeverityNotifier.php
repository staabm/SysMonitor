<?php

namespace staabm\sysmonitor;

/**
 * Notifies only when the given SystemEvent has a certain min-severity. All other events are dropped.
 */
class SeverityNotifier implements Notifier
{
    /**
     * @var int
     */
    private $minSeverity;
    /**
     * @var Notifier
     */
    private $wrapped;

    /**
     * @param int $minSeverity
     */
    public function __construct(Notifier $wrappedNotifier, $minSeverity)
    {
        $this->minSeverity = $minSeverity;
        $this->wrapped = $wrappedNotifier;
    }

    public function notifiy(SystemEvent $e)
    {
        if ($e->severity >= $this->minSeverity) {
            return $this->wrapped->notifiy($e);
        }

        return false;
    }
}
