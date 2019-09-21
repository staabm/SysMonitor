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
     * @var string
     */
    private $line;

    /**
     * @var string
     */
    private $trace;

    /**
     * @var SerializableException
     */
    private $previous;

    public function getMessage()
    {
        return $this->message;
    }
    public function getCode()
    {
        return $this->code;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function getLine()
    {
        return $this->line;
    }
    public function getTraceAsString()
    {
        return $this->trace;
    }
    public function getPrevious()
    {
        return $this->previous;
    }
    public function getOriginClass()
    {
        return $this->originClass;
    }

    /**
     * @param \Exception|\Throwable $e
     * @return $this
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
