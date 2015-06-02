<?php

namespace PHPLog;

use PHPLog\Logger;
use PHPLog\Level;
use PHPLog\Writer\EchoWriter;

/**
 * An instance of a Root logger, which is effectively just a normal
 * Logger instance but with a default threshold and writer set, incase 
 * no other logger can handle a log event.
 * @version 1
 * @author Jack Timblin
 */
class Root extends Logger {
	
	/**
	 * Constructor - initializes this logger with a level and a
	 * default EchoWriter writer (which means all logs are printed on screen)
	 * @param Level $level [optional] the level in which to set the root logger.
	 * defaulted to ALL.
	 */
	public function __construct(Level $level = null) {
		parent::__construct('root');
		if($level == null) {
			$level = Level::all();
		}
		$this->setLevel($level);
		$this->addWriter(new EchoWriter(array()));
	}

}