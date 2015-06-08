<?php

namespace PHPLog;

use PHPLog\Logger;
use PHPLog\LocationInfo;

/**
 * this class encapsulates an event that is to be logged.
 * @version 1
 * @version 1.1 - the location info is now generated based on the
 * file and line in the debug, rather than the class. This means we ca
 * also account for times when the logger is used outside a class or a function.
 * @author Jack Timblin
 */
class Event {

	/* the message to be logged */
	private $message;

	/* the level of this log, i.e INFO, FATAL, ERROR etc */
	private $level;

	/* an instance of the logger that will attempt to log this event. */
	private $logger;

	/* the date that this event was created. */
	private $date;

	/* the line number which the log call was made. */
	private $line = '';

	/* the file in which the log call was made. */
	private $file = '';

	/* the class that called the function. */
	private $class = '';

	/* the function which the log call was made in. */
	private $function = '';

	/**
	 * Constructor - creates a new Event.
	 * @param Logger $logger the logger that will be used to log this event.
	 * @param Level $level the level that this event is at.
	 * @param string $message the message that is to be logged to the system.
	 */
	public function __construct($logger, $level, $message) {
		if($logger instanceof Logger) {
			$this->logger = $logger;
		}

		$this->level = $level;
		$this->message = $message;
		$this->date = time();

		//generate the location information for this log event.
		$trace = debug_backtrace();

		//need to get the first entry where the class is not a Logger/Event.
		$forbidden = array('event', 'logger');

		//look for the first file entry that does not contain our logger framework
		//class calls.
		foreach($trace as $i => $entry) {
			//check if the file is set.
			if(!isset($entry['file']) || strlen($entry['file']) == 0) {
				continue; //no file entry.
			}

			$e = explode('/', trim($entry['file']));
			$en = explode('.', end($e));
			if(count($en) == 0 || in_array(strtolower($en[0]), $forbidden)) {
				continue; //the file contains one of the logger classes.
			}

			$this->file = $entry['file'];
			$this->line = (isset($entry['line'])) ? $entry['line'] : -1;
			
			//determine if the class/function are set and they dont contain
			//our logger framework classes.
			if(isset($entry['class']) && strlen($entry['class']) > 0) {
				$ce = explode('\\', trim($entry['class']));
				if(!in_array(strtolower(end($ce)), $forbidden)) {
					$this->function = $entry['function'];
					$this->class = $entry['class'];
				}
			}

			if(!isset($this->class) || strlen($this->class) == 0) {
				//try one down.
				if(isset($trace[$i + 1])) {
					$oneDown = $trace[$i + 1];
					if(isset($oneDown['class']) && strlen($entry['class']) > 0) {
						$ce = explode('\\', trim($oneDown['class']));
						if(!in_array(strtolower(end($ce)), $forbidden)) {
							$this->function = $oneDown['function'];
							$this->class = $oneDown['class'];
						}
					}
				}
			}

			//if it gets to this point we have calculated the correct details.
			break;
		}

	}

	/**
	 * returns the line from this event for the
	 * class that triggered the log event.
	 * @return string the line from this event.
	 */
	public function getLine() {
		return $this->line;
	}

	/**
	 * returns the file from this event for the
	 * class that triggered the log event.
	 * @return string the file from this event.
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * returns the class from this event for the
	 * class that triggered the log event.
	 * @return string the class from this event.
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * returns the function from this event for the
	 * class that triggered the log event.
	 * @return string the function from this event.
	 */
	public function getFunction() {
		return $this->function;
	}	

	/**
	 * retrieve the message from this event.
	 * @return string the message.
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * retrieve the level of this event.
	 * @return Level the level.
	 */
	public function getLevel() {
		return $this->level;
	}

	/**
	 * retrieve the logger from this event.
	 * @return Logger the logger instance.
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * retrieve the date from this event as a unix timestamp.
	 * @return int the date timestamp.
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @override
	 * calls the __call method which is used to capture method calls that are
	 * not defined in the class. This method triggers an error if anything except a 
	 * 'getter' method is called. Its used to return extra values that have been passed at
	 * runtime to the event to allow for extra details in the log.
	 * @param string $method the method that was called.
	 * @param array $args any arguments that were passed to the method.
	 */
	public function __call($method, $args) {
		if(strtolower(substr($method, 0, 3)) != 'get') {
			trigger_error('undefined method called');
			return;
		}

		$name = lcfirst(substr($method, 3, strlen($method)));
		

		if(!isset($name)) {
			return null;
		}

		if(!isset($this->{$name})) {
			return null;
		}

		return $this->{$name};

	}

}