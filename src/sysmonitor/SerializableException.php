<?php

namespace staabm\sysmonitor;

class SerializableException {
    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    public $line;

    /**
     * @var string
     */
    public $trace;

    /**
     * @var SerializedException
     */
    public $previous;

    public static function fromException(\Exception $e) {
        $ex = new SerializableException();
        $ex->message = $e->getMessage();
        $ex->code = $e->getCode();
        $ex->file = $e->getFile();
        $ex->line = $e->getLine();
        $ex->trace = $e->getTraceAsString();
        $ex->previous = $e->getPrevious() ? self::fromException($e->getPrevious()) : null;
        return $ex;
    }
}