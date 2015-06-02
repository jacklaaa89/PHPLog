<?php

namespace PHPLog;

use PHPLog\Logger;

/**
 * this class encapsulates an event that is to be logged.
 * @version 1
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