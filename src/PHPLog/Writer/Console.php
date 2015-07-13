<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Level;
use PHPLog\Configuration;
use PHPLog\Layout\Pattern;

/**
 * A writer which logs to phps stdout/stderr streams. This writer can
 * be configured to change the stream based on the severity of the log
 * (by providing the useMinErrorLevel/minErrorLevel config params.) if
 * useMinErrorLevel is false, then this writer will just write to the provided
 * target or STDOUT by default.
 * @version 1
 * @author Jack Timblin
 */
class Console extends WriterAbstract
{
    
    /* PHP's standard out stream */
    const STDOUT = 'php://stdout';

    /* PHP's standard error stream */
    const STDERR = 'php://stderr';

    /* PHP's output mechanism which is used by print() and echo() */
    const OUTPUT = 'php://output';

    /* boolean flag to determine whether to change stream based on
	   the provided minimum log level. defaults to true.
    */
    protected $useMinErrorLevel = true;

    /* the minimum error level to log on the STDERR stream (this and everything above)
	   everything below this level gets logged to the STDOUT stream.
    */
    protected $minErrorLevel;

    /* the current target stream, or the one set by the config if useMinErrorLevel is false */
    protected $target;

    /* the resource handle to the currently open stream. */
    protected $handle;

    /* the default pattern to use for logs, if one is not provided. */
    protected $pattern = '[PHPLog] - [LOG] - [%level] - [%date{\'Y-m-d H:i:s\'}] - %message%newline';

    /**
     * initializes this writer with the provided configuration.
     * @param Configuration $config the configuration provided to this writer.
     * @see WriterAbstract::init()
     */
    public function init(Configuration $config) 
    {

        //check to see if the config contains a set useMinErrorLevel and that its a boolean value.
        $this->useMinErrorLevel = $config->get('useMinErrorLevel', true);

        //check to see if the minErrorLevel is set and that its a level instance and useMinErrorLevel is true.
        $this->minErrorLevel = $config->get(
            'minErrorLevel', Level::error(), function ($level) {
                return ($level instanceof Level);
            }
        );

        //check that the user provided a valid target stream.
        $this->target = $config->get(
            'target', self::STDOUT, function ($target) {
                return isset($target) && in_array(
                    $target, 
                    array(
                    self::STDOUT, 
                    self::STDERR,
                    self::OUTPUT
                    )
                );
            }
        );

        $this->getLayoutConfig()->set('pattern', $this->pattern, true);

        //set the layout for this writer.
        $this->setLayout(new Pattern());

    }

    /**
     * @see WriterAbstract::append()
     */
    public function append(Event $event) 
    {
        //generate the layout.
        $log = '';
        if($this->getLayout() !== null) {
            $log = $this->getLayout()->parse($event);
        }

        if(strlen($log) == 0) {
            return false;
        }

        $this->setTarget($event);

        return $this->write($log);

    }

    /**
     * attempts to write the log entry to the currently open stream.
     * @param string $log the parsed log entry.
     * @return boolean if the log entry was successful.
     */
    private function write($log) 
    {
        if(!$this->isClosed() && is_resource($this->handle) && strlen($log) > 0) {
            return (fwrite($this->handle, $log) !== false);
        }
        return false;
    }

    /**
     * attempts to set the current stream target for this log entry.
     * @param Event $event the evetn we are attempting to log.
     */
    private function setTarget(Event $event) 
    {
        if($this->useMinErrorLevel) {
            $level = $event->getLevel();
            $this->target = ($level->isGreaterOrEqualTo($this->minErrorLevel)) 
            ? self::STDERR : self::STDOUT;
        }

        if(is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = fopen($this->target, 'w');

        if(!is_resource($this->handle)) {
            $this->close();
        }
    }

    /**
     * closes the resource handle and calls parent::close to 
     * close the writer.
     * @see Extension::close()
     */
    public function close() 
    {
        if(!$this->isClosed()) {
            if(is_resource($this->handle)) {
                fclose($this->handle);
            }
            parent::close();
        }
    }

}