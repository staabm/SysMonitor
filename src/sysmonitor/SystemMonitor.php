<?php

namespace staabm\sysmonitor;

use staabm\sysmonitor\events\RequestStatsEvent;
use staabm\sysmonitor\events\RequestExceptionEvent;

class SystemMonitor
{

    /**
     * @var SystemEventStorage
     */
    private $storage;

    /**
     * @var RequestEnvironment
     */
    public $env;

    /**
     * @var Notifier
     */
    public $notifier;

    public function __construct(SystemEventStorage $storage, RequestEnvironment $env, Notifier $notifier)
    {
        $this->storage = $storage;
        $this->env = $env;
        $this->notifier = $notifier;

        if (!defined('MONITOR_DBCON_THRESHOLD')) {
            define('MONITOR_DBCON_THRESHOLD', 2);
        }
        if (!defined('MONITOR_DBQUERY_THRESHOLD')) {
            define('MONITOR_DBQUERY_THRESHOLD', 500);
        }
        if (!defined('MONITOR_MEMORY_THRESHOLD')) {
            define('MONITOR_MEMORY_THRESHOLD', 512);
        }
        if (!defined('MONITOR_REQUESTTIME_THRESHOLD')) {
            define('MONITOR_REQUESTTIME_THRESHOLD', 5);
        }
        if (!defined('RESOURCE_NOTIFICATION')) {
            define('RESOURCE_NOTIFICATION', true);
        }
    }

    /**
     * @return void
     */
    public function collectStats(RequestStatsEvent $evt)
    {
        // we won't even collect data for requests which don't hit either of these min-values, to prevent unnecessary garbage.
        $minQueries = 130;
        $minMemory = 80;
        $minTime = php_sapi_name() == 'cli' ? 10 : 3;

        if ($evt->usedQueries < $minQueries && $evt->peakMemory < $minMemory && $evt->requestTime < $minTime) {
            return;
        }

        $sysEvt = $this->createSystemEvent($evt);
        $this->rateAndStore($sysEvt);

        if (RESOURCE_NOTIFICATION) {
            $this->notifier->notifiy($sysEvt);
        }
    }

    /**
     * @return void
     */
    public function collectException(RequestExceptionEvent $evt)
    {
        $sysEvt = $this->createSystemEvent($evt);

        $this->rateAndStore($sysEvt);

        if ($this->notifyException($evt)) {
            $this->notifier->notifiy($sysEvt);
        }
    }

    /**
     * @param RequestExceptionEvent $evt
     *
     * @return boolean Returns true when a notification should be send, otherwise false.
     */
    protected function notifyException(RequestExceptionEvent $evt)
    {
        return !($evt->exception instanceof UnreportedException);
    }

    /**
     * @return void
     */
    private function rateAndStore(SystemEvent $sysEvt)
    {
        $evt = $sysEvt->origin;
        $count = $this->storage->count($sysEvt);

        // uplift severity..
        if ($count === 1) {
            // report the first occurence immediately, but don't report every single error
            $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
        } elseif ($count >= 10 && $evt instanceof RequestExceptionEvent) {
            // .. based on frequency of the same failure
            $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
        } elseif ($count >= 20 && $evt instanceof RequestStatsEvent) {
            // .. based on frequency of the same exhausted resource
            $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
        } elseif (php_sapi_name() == 'cli') {
            // .. for cron-jobs
            $sysEvt->severity = SystemEvent::SEVERITY_URGENT;

            // side note: APC is not available in CLI per default,
            // therefore sending it directly is a must do.
            // -> $count will not be accurate in CLI and cannot be relied on.
        } elseif ($this->expectsSoap()) {
            // .. for our soap-apis
            $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
        }

        // uplift severity for fatal errors
        if ($evt instanceof RequestExceptionEvent) {
            $exc = $evt->exception;
            if ($exc instanceof \ErrorException) {
                if ($this->isFatalError($exc->getCode())) {
                    $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
                }
            } elseif ($exc instanceof \Error) {
                $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
            } elseif ($exc instanceof SevereException) {
                $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
            }
        }

        if ($evt instanceof RequestStatsEvent) {
            // uplift the severity in case our page required..
            // ...more than X queries
            $maxQueries = MONITOR_DBQUERY_THRESHOLD;
            // ...more than Y db connections
            $maxConnections = MONITOR_DBCON_THRESHOLD;
            // ..more than Z MB per request
            $maxMemory = MONITOR_MEMORY_THRESHOLD;
            // ..more than W sec request time
            $maxRequestTime = MONITOR_REQUESTTIME_THRESHOLD;

            if ($evt->usedQueries > $maxQueries) {
                $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
                $sysEvt->title = 'Resource required ' . $evt->usedQueries . ' sql queries to execute!';
            } elseif ($evt->usedConnections > $maxConnections) {
                $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
                $sysEvt->title = 'Resource required ' . $evt->usedConnections . ' mysql connections to execute!';
            } elseif ($evt->requestTime > $maxRequestTime) {
                $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
                $sysEvt->title = 'Resource required ' . $evt->requestTime . ' seconds to execute!';
            } else {
                $memPeak = $evt->peakMemory;
                if ($memPeak > $maxMemory) {
                    $sysEvt->severity = SystemEvent::SEVERITY_URGENT;
                    $sysEvt->title = 'Resource required ' . $memPeak . ' MB memory to execute!';
                }
            }
        }

        $this->storage->store($sysEvt);
    }

    /**
     * @param RequestStatsEvent|RequestExceptionEvent $evt
     * @return SystemEvent
     */
    private function createSystemEvent($evt)
    {
        if ($evt instanceof RequestStatsEvent) {
            $title = 'Resource Report: (con# ' . $evt->usedConnections . ', qry# ' . $evt->usedQueries . ')';
            $hash = md5($this->env->getResourceName());
        } elseif ($evt instanceof RequestExceptionEvent) {
            $exc = $evt->exception;
            $title = sprintf('Exception: "%s" in %s:%s', $exc->getMessage(), $exc->getFile(), $exc->getLine());
            $hash = md5($title);
        } else {
            // @phpstan-ignore-next-line
            throw new \Exception(sprintf('Unsupported event type "%s"', is_object($evt) ? get_class($evt) : gettype($evt)));
        }

        $sysEvt = new SystemEvent();
        $sysEvt->title = $title;
        $sysEvt->hash = $hash;
        $sysEvt->origin = $evt;
        $sysEvt->env = $this->env;

        return $sysEvt;
    }

    /**
     * @return bool
     */
    private function expectsSoap()
    {
        // depending on the used framework, there are different was to check for soap requests
        return !empty($_SERVER['HTTP_SOAPACTION']) ||
               !empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/soap+xml') !== false;
    }

    /**
     * @param int $errno
     * @return bool
     */
    private function isFatalError($errno)
    {
        return in_array(
            $errno,
            array(
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_CORE_WARNING,
                E_COMPILE_ERROR,
                E_COMPILE_WARNING
            )
        );
    }
}
