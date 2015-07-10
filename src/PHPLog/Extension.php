<?php

namespace PHPLog;

use PHPLog\Logger;

/**
 * class which adds default functionality to all writer and layout classes.
 * @version 1
 * @author Jack Timblin
 */
class Extension
{

    /* whether this extension has been shutdown or not. */
    protected $closed = false;

    /* the name of the logger that this writer is associated with. */
    protected $loggerName;

    /**
     * sets the logger name for this extension to get system services.
     * @param string $loggerName the name of the logger this extension is 
     *                           associated with.
     */
    public function setLoggerName($loggerName)
    {
        $this->loggerName = $loggerName;
    }

    /**
     * gets the loggers name that this extension is associated with.
     * @return string the name of the logger this extension is associated with.
     */
    public function getLoggerName()
    {
        return $this->loggerName;
    }
    
    /**
     * gets the name of the extension.
     * @return string the name of the extension.
     */
    public function getName() 
    {
        return get_class($this);
    }

    /**
     * gets the short name of the extension with no namespace.
     * @return string the short name of this extension with no namespace.
     */
    public function getShortName() 
    {
        $rc = new \ReflectionClass($this);
        return $rc->getShortName();
    }

    /**
     * called in the constructor, should be overriden by subclasses to add
     * extra functionality on construction.
     */
    public function start() 
    {
        $this->closed = false;
    }

    /**
     * Constructor - calls the init() method to allow subclasses to initialize correctly.
     */
    public function __construct() 
    {
        $this->start();
    }

    /** 
     * gets a system service from this instances logger.
     * @param string $serviceIndentifer the service required.
     * @return mixed the service requested.
     */
    public final function getSystemService($serviceIdentifer) 
    {
        return Logger::getSystemService($this->getLoggerName(), $serviceIdentifer);
    }

    /**
     * shutdowns the extension, should be overriden by subclasses to allow
     * to close down successfully.
     */
    public function close() 
    {
        $this->close = true;
    }

    /**
     * Destructor - closes the extension when the object is destructed.
     */
    public function __destruct() 
    {
        $this->close();
    }

    /**
     * checks to see if the extension has been closed.
     * @return boolean <b>TRUE</b> if the extension has been closed, <b>FALSE</b> otherwise.
     */
    public function isClosed() 
    {
        return $this->closed;
    }

    /**
     * generates a E_USER_WARNING with a message.
     * @param string the message to send with the error.
     */
    public function warn($message) 
    {
        trigger_error($message, E_USER_WARNING);
    }

}