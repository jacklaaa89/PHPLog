<?php

namespace PHPLog;

use PHPLog\Logger;
use PHPLog\Root;
use PHPLog\Level;
use PHPLog\ExtraAbstract;

/**
 * This class maintains the hierarchy of loggers that are currently in use.
 * There is always a 'Root' logger to fall back on if there is no other logger that can
 * handle the current request. Loggers are namespaced for example 'home' is the parent of 'home.test'
 * and the root is the parent of 'home'. This way we can assign differnet logging levels and
 * just propogate down the parent chain for each log event and handle different log events in different ways.
 * @version 1
 * @author Jack Timblin 
 */
class LoggerHierarchy extends ExtraAbstract {
	
	/* the loggers in the current hierarchy */
	protected $loggers = array();

	/* an instance of the root logger. */
	protected $root;

	/* the threshold in which this heirarchy is limited too.
       @todo - currently not in use.
	*/
	protected $threshold;

	/**
	 * Constructor - initializes a new hierarchy and attaches the root.
	 * @param Root [optional] an instance of a root logger, or a new one if one
	 * is not provided.
	 */
	public function __construct($root = null) {
		$this->root = ($root !== null) ? $root : new Root();
		$this->setThreshold(Level::all());
	}

	/**
	 * retrieves or creates a new logger by its name. A logger is added to the
	 * hierarchy and its parent is assigned during the retrieval.
	 * @param string $name the name of the logger.
	 * @return Logger the new or existing Logger instance.
	 */
	public function getLogger($name) {
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
		}

		return $this->loggers[$name];

	}

	/**
	 * clears the current logger hierarchy
	 */
	public function clear() {
		$this->loggers = array();
	}

	/**
	 * sets this hierarchys level threshold
	 * @param Level $level the level to limit this hierarchy too.
	 * @todo this functionality is not currently in use.
	 */
	public function setThreshold(Level $threshold) {
		$this->threshold = $threshold;
	}

}