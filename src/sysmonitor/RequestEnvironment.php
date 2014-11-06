<?php

namespace staabm\sysmonitor;

interface RequestEnvironment {
    /**
     * Returns all request information
     *
     * @return string[]
     */
    public function asArray();

    /**
     * Returns a representational name for this request.
     * (e.g. a url for http-requests and a script-name for cli invoked requests)
     *
     * @return string
     */
    public function getResourceName();

    /**
     * Returns the name of the host
     *
     * @return string
     */
    public function getHost();
}