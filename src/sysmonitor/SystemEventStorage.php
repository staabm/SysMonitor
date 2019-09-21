<?php

namespace staabm\sysmonitor;

use \Exception;
use staabm\sysmonitor\events\RequestExceptionEvent;

class SystemEventStorage
{
    const CACHE_NAMESPACE = 'rocket/sysmonitor/events/';
    const STATS_NAMESPACE = 'rocket/sysmonitor/stats/';

    /**
     * @var \CacheInterface
     */
    private $dataStore;

    /**
     * @var \CacheInApc|\CacheMemcached
     */
    protected $dataStatistics;

    public function __construct()
    {
        $this->dataStore = \CacheMemcached::factory();
        // only memcache"d" supports the increment-operation, therefore don't use the factory here!
        $memcached = new \CacheMemcached();
        if ($memcached->supported()) {
            $this->dataStatistics = $memcached;
        } else {
            $this->dataStatistics = new \CacheInApc();
        }
    }

    /**
     * @param SystemEvent $evt
     * @throws Exception
     *
     * @return int The number of equal-hashed events
     */
    public function store(SystemEvent $evt)
    {
        if (empty($evt->hash)) {
            throw new Exception("Hash is not expected to be empty!");
        }

        // work on a defensive copy, so we won't influence the given arg
        $evt2Store = clone $evt;

        if ($evt2Store->origin instanceof RequestExceptionEvent) {
            // wrap exception to prevent endless-recursion caused by args in stacktraces
            $evt2Store->origin->exception = SerializableException::fromException($evt2Store->origin->exception);
        }

        $events = $this->findByHash($evt2Store->hash);

        // make sure we only hold at most X event-occurences, but keep the oldest
        $maxOccurences = 5;
        if (count($events) > $maxOccurences) {
            $oldest = $events[0];
            $events = array_slice($events, -($maxOccurences-1));
            $events[0] = $oldest;
        }
        $events[] = $evt2Store;

        // events which don't happen at least once per X hours, will be dropped
        $this->dataStore->set(self::CACHE_NAMESPACE . $evt2Store->hash, $events, strtotime("+2 hours"));

        if ($this->dataStatistics->supported()) {
            // we remember how often an error occured a bit longer than the actual even-data
            // because this info might help us later on to decide which events are more important than others.
            return $this->dataStatistics->increment(self::STATS_NAMESPACE . $evt2Store->hash, 1, strtotime("+3 hours"));
        }
        // when APC is not supported, return approx. value
        return count($events);
    }

    /**
     * Count how often the given event occured, within the current cache-timeframe.
     *
     * @param SystemEvent $evt
     * @throws Exception
     *
     * @return int The number of equal-hashed events
     */
    public function count(SystemEvent $evt)
    {
        if (empty($evt->hash)) {
            throw new Exception("Hash is not expected to be empty!");
        }

        if ($this->dataStatistics->supported()) {
            return $this->dataStatistics->get(self::STATS_NAMESPACE . $evt->hash, 0);
        }
        // when APC is not supported, return approx. value
        return count($this->findByHash($evt->hash));
    }

    /**
     * Returns all available detail data for SystemEvents with the given hash.
     *
     * @param string $hash
     * @return SystemEvent[]
     */
    public function findByHash($hash)
    {
        if (empty($hash)) {
            throw new Exception("Hash is not expected to be empty!");
        }

        return $this->dataStore->get(self::CACHE_NAMESPACE . $hash, array());
    }
}
