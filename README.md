SysMonitor
==========

Monitors a php app and sends notifications on certain error/exception/resource-exhausting/custom/etc. events.

The monitor checks the data you provide and decides with a naive default implementation (see SystemMonitor#rateAndStore) when things get urgent/severe. SystemEvents are compared using a hash so times of occurence can also be based on similarity.
Notifications are beeing send in such cases, depending on your used Notifier.

The default implementation of SystemEventStorage stores your data in a mix of APC and Memcached. Therefore it requires both php extensions.

Usage
=====

init all the things. All classes prefixed with `My` need to be provided by the application/framework beeing monitored.

```php
// sends notificaitons on urgent events
$notifier = new SeverityNotifier(new MyCustomNotifier(), SystemEvent::SEVERITY_URGENT);
// main class which collects all the data
$monitor = new SystemMonitor(new SystemEventStorage(), new MyRequestEnvImpl(), $notifier);
```

report performance-data from somewhere in your app (e.g. on request shutdown)

```php
register_shutdown_function(function() {
    $requestStats = new RequestStatsEvent();
    // data from your db class
    $requestStats->usedQueries = DB::$num_of_queries;
    $requestStats->usedConnections = DB::$num_of_connections;
    // data from your runtime
    $requestStats->peakMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024);
    
    // retrieve the monitor instance, e.g. via a DIC/a registry/singleton/whatever
    // $monitor = .. 
    $monitor->collectStats($requestStats);
});
```

let the monitor collect data about exceptions occured

```php
set_exception_handler(function() {
    $event = new RequestExceptionEvent();
    $event->exception = $exception;
    
    // retrieve the monitor instance, e.g. via a DIC/a registry/singleton/whatever
    // $monitor = .. 
    $monitor->collectException($event);
});
```

you could do the same for errors. To collect data of fatal errors there are some known workarounds which can be used (checking for error_get_last() in a shutdown function)
