<?php

namespace PHPLog;

use PHPLog\Logger;
use PHPLog\Root;
use PHPLog\Level;
use PHPLog\ExtraAbstract;
use PHPLog\Configuration;
use PHPLog\WriterAbstract;
use PHPLog\FilterAbstract;
use PHPLog\Renderer;

/**
 * This class maintains the hierarchy of loggers that are currently in use.
 * There is always a 'Root' logger to fall back on if there is no other logger that can
 * handle the current request. Loggers are namespaced for example 'home' is the parent of 'home.test'
 * and the root is the parent of 'home'. This way we can assign differnet logging levels and
 * just propogate down the parent chain for each log event and handle different log events in different ways.
 * @version 1
 * @author Jack Timblin 
 */
class LoggerHierarchy extends ExtraAbstract
{
    
    /* the loggers in the current hierarchy */
    protected $loggers = array();

    /* an instance of the root logger. */
    protected $root;

    /* the threshold in which this heirarchy is limited too.
       @todo - currently not in use.
    */
    protected $threshold;

    /* a map of uniquely mapped ids, so layouts and writers can get access to system services. */
    protected $uniqueIDs = array();

    /**
     * retrieves or creates a new logger by its name. A logger is added to the
     * hierarchy and its parent is assigned during the retrieval.
     * @param string $name the name of the logger.
     * @return Logger the new or existing Logger instance.
     */
    public function getLogger($name, $config) 
    {

        if(!isset($this->root)) {
            $this->root = new Root();
            $this->setThreshold(Level::all());
        }

        $this->root->init();

        if(!isset($this->loggers[$name])) {
            $logger = new Logger($name);

            $entities = explode('.', $name);
            $first = array_shift($entities);

            $parent = ($first != $name && isset($this->loggers[$first])) ? $this->loggers[$first] : $this->root;
            $logger->setParent($parent);

            if(count($entities) > 0) {
                foreach($entities as $e) {
                    $p = $first.'.'.$e;
                    if(isset($this->loggers[$p]) && $p != $name) {
                        $logger->setParent($this->loggers[$p]);
                    }
                    $first .= '.'.$e;
                }
            }

            $this->loggers[$name] = $logger;
            $this->setInitialConfiguration($this->loggers[$name], $config);

        }

        return $this->loggers[$name];

    }

    /**
     * generates a unique id which links a logger to an element attached to it.
     * @return string the uniqueID or null if one could not be generated.
     */
    public function generateUniqueID() 
    {
        $logger = $this->getLatestLoggerInstance();
        if($logger instanceof Logger) {
            $id = substr(base64_encode(hash('sha256', 'PHPLog-'.mt_rand(1, 10).'-'.time())), 0, 10);
            if(array_key_exists($id, $this->uniqueIDs)) {
                $id = $this->generateUniqueID();
            }
            $this->uniqueIDs[$id] = $logger->getName();
            return $id;
        }
        return null;
    }

    /**
     * gets the latest logger instance to be added to the heirarchy.
     * @return Logger the latest Logger instance.
     */
    public final function getLatestLoggerInstance() 
    {
        if(count($this->loggers) == 0) {
            return $this->root;
        }
        return end($this->loggers);
    }

    /**
     * returns the amount of currently registered loggers.
     * @return int the amount of registered loggers in this hierarchy.
     */
    public function countLoggers() 
    {
        return is_array($this->loggers) ? count($this->loggers) : 0;
    }

    /**
     * attempts to get a system service from a defined logger instance.
     * @param string uniqueID the id of the object attempting to get the service.
     * @param string serviceIdentifier the service required.
     * @return mixed the found service, or null if not found.
     */
    public function getSystemService($uniqueID, $serviceIdentifier) 
    {
        if($uniqueID == null || strlen($uniqueID) == 0) {
            return;
        }

        if(!array_key_exists($uniqueID, $this->uniqueIDs)) {
            return;
        }

        $loggerName = $this->uniqueIDs[$uniqueID];

        if(!array_key_exists($loggerName, $this->loggers)) {
            return;
        }

        $logger = $this->loggers[$loggerName];

        if(!($logger instanceof Logger)) {
            return;
        }

        $method = 'get'.ucwords(strtolower($serviceIdentifier));

        if(!method_exists($logger, $method)) {
            return;
        }

        return $logger->$method();

    }

    /**
     * clears the current logger hierarchy
     */
    public function clear() 
    {
        $this->loggers = array();
        $this->uniqueIDs = array();
    }

    /**
     * sets this hierarchys level threshold
     * @param Level $level the level to limit this hierarchy too.
     * @todo this functionality is not currently in use.
     */
    public function setThreshold(Level $threshold) 
    {
        $this->threshold = $threshold;
    }

    /**
     * sets any initial configuration for a logger.
     * @param Logger $logger the logger to configure.
     * @param array  $config the config to configure the logger with.
     * @return Logger the configured logger.
     */
    private function setInitialConfiguration(&$logger, $config) 
    {

        if(isset($config['writers'])) {
            //set all of the valid writers.
            foreach($config['writers'] as $name => $writerConf) {
                $className = '\\PHPLog\\Writer\\'.$name;
                if(class_exists($className)) {
                    $writer = new $className($writerConf);
                    $logger->addWriter($writer);
                }
            }
        }

        if(isset($config['filters'])) {
            //set all valid filters.
            foreach($config['filters'] as $name => $filterConf) {
                $className = '\\PHPLog\\Filter\\'.$name;
                if(class_exists($className)) {
                    $filter = new $className($writerConf);
                    $logger->addFilter($filter);
                }
            }
        }

        if(isset($config['renderers'])) {
            foreach($config['renderers'] as $class => $renderer) {
                if(!($renderer instanceof RendererInterface)) {
                    if(!is_string($renderer)) {
                        return;
                    }
                    if(!class_exists($renderer)) {
                        $renderer = '\\PHPLog\\Renderer\\'.$renderer;
                        if(!class_exists($renderer)) {
                            return;
                        }
                    }

                    $renderer = new $renderer();
                }

                $logger->addRenderer($class, $renderer);

            }
        }

        return $logger;
    }
}