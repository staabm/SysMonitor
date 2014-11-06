<?php
namespace staabm\sysmonitor;

interface Notifier
{
    /**
     * Sends a notifications for the given SystemEvent.
     *
     * @param SystemEvent $e
     * @return bool returns true on success or false when no message was sent (not only a error case)
     */
    public function notifiy(SystemEvent $e);
}
