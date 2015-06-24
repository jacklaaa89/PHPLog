<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file contains the Logger class which control the logging process to writers. 
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 2.0 of the Apache Software Licence
 * that is available through the world-wide-web at the following URI:
 * http://www.apache.org/licenses/LICENSE-2.0/.
 *
 * @category Logging
 * @package  PHPLog
 * @author   Jack Timblin <jacktimblin@gmail.com>
 * @license  Apache Software Licence 2.0
 * @version  GIT: 083c738c68
 * @link     https://bitbucket.org/jtimblin/phplog.git
 * @see      ExtraAbstract
 * @since    File available since GIT: f71ec76411
 */

namespace PHPLog;

use PHPLog\WriterAbstract;
use PHPLog\Level;
use PHPLog\ExtraAbstract;
use PHPLog\FilterAbstract;
use PHPLog\Writer\EchoWriter;
use PHPLog\Renderer;

/**
 * The Logger class which is the class what will start the logging process.
 *
 * It holds a series of writers which are appended to when a log is attempted.
 * A Logger also has a Level set as the threshold of what this logger will allow.
 * A Logger is also part of a hierarchy of Loggers which are effectively namespaced, 
 * in which the log gets propogated down the heirarchy untill we find a logger that
 * can handle that log, but we 
 * always have a default root logger, which is the logger we will write to if 
 * all else fails.
 *
 * @category Logging
 * @package  PHPLog
 * @author   Jack Timblin <jacktimblin@gmail.com>
 * @license  Apache Software Licence 2.0
 * @version  Release: 1.2
 * @link     https://bitbucket.org/jtimblin/phplog.git
 * @see      ExtraAbstract
 * @since    Class available since Release 1.0
 */
class Logger extends ExtraAbstract
{
    
    /* the static instance of the current LoggerHierarchy */
    private static $_hierarchy;

    /* the name of this logger instance, used in the hierarchy to allow
	   namespaced loggers etc. */
    protected $name;

    /* the threshold for this logger, i.e the lowest level this logger will log. */
    protected $level;

    /* the writers this logger should attempt to append to. */
    protected $writers = array();

    protected $renderer;

    /* boolean flag whether to allow propogation to the parent 
	   if this logger cannot handle the log, this would be the case if:
	   
	   1: the logs level is below this loggers threshold.
	   2: this logger has no writers attached.
	   3: all of the attached writers failed to log the event.
    */
    protected $propogation = true;

    /* any filter that will be applied to the log event. */
    protected $filter;

    /**
     * Constructor - sets the name and the default logging level.
     * @param string $name the name of this logger.
     */
    public function __construct($name) 
    {
        $this->name = $name;
        //default to all logs.
        $this->setLevel(Level::all());
    }

    /**
     * Gets the global renderer instance.
     * @return Renderer the global renderer instance.
     */
    public function getRenderer() 
    {
        if (!isset($this->renderer)) {
            $this->renderer = Renderer::newInstance();
        }
        return $this->renderer;
    }

    /**
     * Helper function to log a message at the debug log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass
     * to this single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function debug($message, $extras = array()) 
    {
        $this->log(Level::debug(), $message, $extras);
    }

    /**
     * Helper function to log a message at the info log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass to this
     *  single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function info($message, $extras = array()) 
    {
        $this->log(Level::info(), $message, $extras);
    }

    /**
     * Helper function to log a message at the warn log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass to this
     * single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function warn($message, $extras = array()) 
    {
        $this->log(Level::warn(), $message, $extras);
    }

    /**
     * Helper function to log a message at the error log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass to this
     * single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function error($message, $extras = array()) 
    {
        $this->log(Level::error(), $message, $extras);
    }

    /**
     * Helper function to log a message at the fatal log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass to this
     * single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function fatal($message, $extras = array()) 
    {
        $this->log(Level::fatal(), $message, $extras);
    }

    /**
     * Helper function to log a message at the trace log level.
     * @param string $message the message to log.
     * @param array  $extras  [optional] the array of extra variables to pass to this
     * single log entry. to this single log entry to this single log entry.
     * @return void
     */
    public function trace($message, $extras = array()) 
    {
        $this->log(Level::trace(), $message, $extras);
    }

    /**
     * Attempts to log a message to the defined writers and if this
     * logger cannot handle the log, then (if enabled) the log event is
     * propogated down the logger chain.
     * Extra variables can be passed to the log event to
     * customise the log, i.e adding a userID to a database log to link to 
     * a users table. All extras are defined at 3 scope levels:
     *
     * 1: The hierarchys scope, these extras are added to all logs from all loggers.
     * 2: This logger instances scope, these extras are added to all logs 
     *    from this logger instance.
     * 3: This single log event, these extras are only added to this single
     *    log event.
     *
     * If any variables are overidden, the order is 3, 2, 1. so anything down
     * the scope chain will overide variables set further up.
     *
     * @param  Level  $level   the level of this log event.
     * @param  string $message the logs message.
     * @param  array  $extras  [optional] Any optional variables to append
     * @return void
     */
    public function log(Level $level, $message, $extras = array()) 
    {
        $handled = false;

        if ($this->isEnabledFor($level) && strlen($message) != 0) {

            $event = new Event($this, $level, $message);

            //check any filters applied to this logger.
            $filter = $this->getFilter();
            while ($filter !== null) {
                switch ($filter->decide($event)) {
                case FilterAbstract::DENY: 
                    return;
                case FilterAbstract::ACCEPT: $filter = null; 
                    break;
                case FilterAbstract::NEUTRAL: $filter = $filter->getNext(); 
                    break; 
                }
            }

            //add global extras.
            $global = self::getHierarchy()->getExtras();
            if (count($global) > 0) {
                foreach ($global as $key => $value) {
                    $event->{$key} = $value;
                }
            }

            //add logger extras.
            if (count($this->getExtras()) > 0) {
                foreach ($this->getExtras() as $key => $value) {
                    $event->{$key} = $value;
                }
            }

            //then add this log extras, these have precidence.
            if (is_array($extras) && count($extras) > 0) {
                foreach ($extras as $key => $value) {
                    $event->{$key} = $value;
                }
            }

            //account for errors, if none of the writers could log, then propogate.
            $errors = array();

            if ($this->getLevel() !== null && count($this->writers) > 0) {
                foreach ($this->writers as $writer) {
                    if ($writer instanceof WriterAbstract) {
                        $success = $writer->log($event);
                        if (!$success) {
                            $errors[] = $success;
                        } 
                        $handled = true;
                    }
                }
            }

            if (count($errors) == count($this->writers)) {
                $handled = false; //all writers failed to write.
            }

        }

        //now the log event only pops down the chain if its not been handled
        //by this logger and propogation is enabled and this logger has a parent.
        if (isset($this->parent) && $this->propogation && !$handled) {
            $this->parent->log($level, $message, $extras);
        }

    }

    /**
     * Gets the filter chain currently attached to this logger
     * @return FilterAbstract|null the filter attached to this logger or null
     * if there is no filter chain on this logger.
     */
    public function getFilter() 
    {
        return $this->filter;
    }

    /**
     * Clears the filter chain.
     * @return void
     */
    public function clearFilter() 
    {
        $this->filter = null;
    }

    /**
     * Adds a new filter to the filter chain.
     * @param FilterAbstract|Closure $filter the filter to add to the filter chain.
     * @return void
     */
    public function addFilter($filter) 
    {
        if (!($filter instanceof FilterAbstract) && !($filter instanceof \Closure)) {
            return;
        }

        if ($filter instanceof \Closure) {
            $filter = $filter();
            if (!($filter) instanceof FilterAbstract) {
                return;
            }
        }

        if (!($this->filter) instanceof FilterAbstract) {
            $this->filter = $filter;
        } else {
            $this->filter->addNext($filter);
        }

    }

    /**
     * Adds a new filter chain from an array
     * @param array $filters an array of filters.
     * @return void
     */
    public function addFilterChain($filters) 
    {
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                if ($filter instanceof FilterAbstract) {
                    $this->addFilter($filter);
                }
            }
        }
    }

    /**
     * Adds a new writer for this logger to append to when dealing with log events.
     * @param WriterAbstract|Closure $writer the writer to add to this logger.
     * @return void
     */
    public function addWriter($writer) 
    {


        if (!($writer instanceof WriterAbstract) && !($writer instanceof \Closure)) {
            return;
        }

        if ($writer instanceof \Closure) {
            $writer = $writer();
            if (!($writer) instanceof WriterAbstract) {
                return;
            }
        }

        $name = $writer->getName();
        $this->writers[$name] = $writer;
    }

    /**
     * Gets the name of this logger instance.
     * @return string the name of this logger instance.
     */
    public function getName() 
    {
        return $this->name;
    }

    /**
     * Sets the parent of this logger if its a valid Logger instance.
     * @param Logger $parent the parent of this logger instance.
     * @return void
     */
    public function setParent(Logger $parent) 
    {
        if ($parent instanceof Logger) {
            $this->parent = $parent;
        }
    }

    /**
     * Returns this loggers parent.
     * @return Logger this loggers parent logger.
     */
    public function getParent() 
    {
        return $this->parent;
    }

    /**
     * Determines if this logger can handle the requested log level.
     * @param Level $level the log level to test.
     * @return boolean <b>TRUE</b> if this logger can handle the requested level
     * <b>FALSE</b> otherwise.
     */
    public function isEnabledFor(Level $level) 
    {
        return $level->isGreaterOrEqualTo($this->getPropogatedLevel());
    }

    /**
     * Goes through the hierarchy to find the first valid level entry, i.e the
     * threshold that this logger can handle. If this logger has no level set, it goes
     * down the chain to find the first logger level (as loggers inherit parents threshold
     * if one is not set for this logger)
     * @return Level the threshold for this logger, whether its from this logger or inherited 
     * from its parent instance.
     */
    public function getPropogatedLevel() 
    {
        for ($logger = $this; $logger !== null; $logger = $logger->getParent()) {
            if ($logger->getLevel() !== null) {
                return $logger->getLevel();
            }
        }
    }

    /**
     * Gets the threshold for this logger instance.
     * @return Level the lowest level this logget can handle.
     */
    public function getLevel() 
    {
        return $this->level;
    }

    /**
     * Sets the threshold for this logger instance, the default is all.
     * @param Level $level the threshold to set this logger to.
     * @return void
     */
    public function setLevel(Level $level) 
    {
        $this->level = $level;
    }

    /**
     * Conveince method to convert this logger into a string.
     * used in logs when the pattern contains the %logger keyword.
     * @return string a string representation of this logger instance (just its name)
     */
    public function __toString() 
    {
        return $this->getName();
    }
    
    /**
     * Convienience method to either get or create a new logger based on the name.
     * This method also handles adding the logger to the LoggerHierarchy and maintaining
     * its parent logger.
     * @param string $name   the name of the logger.
     * @param array  $config [optional] initial config for this writer.
     * @return Logger either a new or existing logger with the {$name} provided.
     */
    public static function getLogger($name, $config = array()) 
    {
        return self::getHierarchy()->getLogger($name, $config);
    }

    /**
     * Returns the current instance of the logger hierarchy (or creates
     * a new one if it doesnt exist.)
     * @return LoggerHierarchy the current hierarchy for loggers.
     */
    public static function getHierarchy() 
    {
        if (!isset(self::$_hierarchy)) {
            self::$_hierarchy = new LoggerHierarchy();
        }
        return self::$_hierarchy;
    }

    /**
     * Adds an extra variable to the global scope.
     * @param string $name  the name of the extra variable.
     * @param mixed  $value the value to store.
     * @return void
     */
    public static function addGlobalExtra($name, $value) 
    {
        self::getHierarchy()->addExtra($name, $value);
    }

    /**
     * Removes a global extra from the global scope.
     * @param string $name the name of the variable to remove.
     * @return void
     */
    public static function removeGlobalExtra($name) 
    {
        self::getHierarchy()->removeExtra($name);
    }

    /**
     * Clears the global variables.
     * @return void
     */
    public static function clearGlobalExtras() 
    {
        self::getHierarchy()->clearExtras();
    }

    /**
     * Adds a new global renderer to use for a specific class
     * renderers are shared between all loggers in the current
     * hierarchy.
     * @param mixed             $class                    the object/class name to run the renderer against.
     * @param RendererInterface $renderer                 the renderer interface to use on the $class.
     * @param boolean           $disableInheritanceRender [optional] whether to allow string 
     *                                                    concatination of multiple renderers which handle
     *                                                    the same $class type (due to inheritance)
     * @return void
     */
    public function addRenderer($class, $renderer, $disableInheritanceRender = false) 
    {
        $this->getRenderer()->addRenderer(
            $class, 
            $renderer, 
            $disableInheritanceRender
        );
    }

    /**
     * Removes a new global renderer.
     * @param mixed $class the class to remove the renderer for.
     * @return void
     */
    public function removeRenderer($class) 
    {
        $this->getRenderer()->removeRenderer($class);
    }

    /**
     * Sets the default renderer to use if we dont have a 
     * specific renderer to use for an object.
     * @param RendererInterface $default the renderer to use as
     * the default renderer.
     * @return void
     */
    public function setDefaultRenderer($default) 
    {
        $this->getRenderer()->setDefaultRenderer($default);
    }

    /**
     * Resets the global default renderer.
     * @return void
     */
    public function resetDefaultRenderer() 
    {
        $this->getRenderer()->resetDefaultRenderer();
    }

    /**
     * Generates a unique id for a layout or writer to attach to its logger instance.
     * @return void
     */
    public static function generateUniqueID() 
    {
        return self::getHierarchy()->generateUniqueID();
    }

    /**
     * Retrieves a system service.
     * @param string $uniqueID          the unique identifier for the
     *                                  object requesting the service.
     * @param string $serviceIdentifier the service required.
     * @return mixed the required system service.
     */
    public static function getSystemService($uniqueID, $serviceIdentifier) 
    {
        return self::getHierarchy()->getSystemService($uniqueID, $serviceIdentifier);
    }

}