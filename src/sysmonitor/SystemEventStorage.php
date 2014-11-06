<?php

namespace staabm\sysmonitor;

use \Exception;

class SystemEventStorage
{
    const CACHE_NAMESPACE = 'rocket/sysmonitor/events/';

    /**
     * @var CacheInterface
     */
    private $dataStore;

    /**
     * @var CacheApc
     */
    protected $dataStatistics;

    public function __construct()
    {
        $this->dataStore = \CacheMemcached::factory();
        $this->dataStatistics = new \CacheApc();
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

        $events = $this->findByHash($evt->hash);

        // make sure we only hold at most X event-occurences, but keep the oldest
        $maxOccurences = 5;
        if (count($events) > $maxOccurences) {
            $oldest = $events[0];
            $events = array_slice($events, -($maxOccurences-1));
            $events[0] = $oldest;
        }
        $events[] = $evt;

        // events which don't happen at least once per X hours, will be dropped
        $this->dataStore->set(self::CACHE_NAMESPACE . $evt->hash, $events, strtotime("+2 hours"));
        // we remember how often an error occured a bit longer than the actual even-data
        // because this info might help us later on to decide which events are more important than others.
        return $this->dataStatistics->increment(self::CACHE_NAMESPACE . $evt->hash, 1, strtotime("+3 hours"));
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

        return $this->dataStatistics->get(self::CACHE_NAMESPACE . $evt->hash, 0);
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