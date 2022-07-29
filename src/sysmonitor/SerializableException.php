<?php

namespace staabm\sysmonitor;

class SerializableException
{
    /**
     * @var string
     */
    private $originClass;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $file;

    /**
     * @var int
     */
    private $line;

    /**
     * @var string
     */
    private $trace;

    /**
     * @var self
     */
    private $previous;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getTraceAsString()
    {
        return $this->trace;
    }

    /**
     * @return SerializableException
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * @return string
     */
    public function getOriginClass()
    {
        return $this->originClass;
    }

    /**
     * @param \Exception|\Throwable $e
     * @return self
     */
    public static function fromException($e)
    {
        $ex = new SerializableException();
        $ex->originClass = get_class($e);
        $ex->message = $e->getMessage();
        $ex->code = $e->getCode();
        $ex->file = $e->getFile();
        $ex->line = $e->getLine();
        $ex->trace = $e->getTraceAsString();
        $ex->previous = $e->getPrevious() ? self::fromException($e->getPrevious()) : null;
        return $ex;
    }
}
