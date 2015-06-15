<?php

namespace PHPLog;

/**
 * An encapsulation of a log level as levels are ordered by severity.
 * @version 1
 * @author Jack Timblin
 */
class Level {
	
	/* LEVEL OFF - no logging at all. */
	const OFF = 21454584;

	/* LEVEL FATAL - only for the most severe of errors. */
	const FATAL = 50000;

	/* LEVEL ERROR - a severe error, which could possibly be recovered. */
	const ERROR = 40000;

	/* LEVEL WARN - an undesirable result, but the system can recover. */
	const WARN = 30000;

	/* LEVEL INFO - no error, just information. */
	const INFO = 20000;

	/* LEVEL DEBUG - no error, just logs to see code execution flow. */
	const DEBUG = 10000;

	/* LEVEL TRACE - no error, logs all system output. */
	const TRACE = 5000;

	/* LEVEL ALL - no error, logs every single log passed to the logger. */
	const ALL = -21454584;

	/* the integer representation of the level */
	private $level;

	/* the string representation of the level */
	private $stringLevel;

	/**
	 * Constructor - private stores the final variables.
	 * @param int $level the int level.
	 * @param string $name the string representation of the level.
	 */
	private function __construct($level, $name) {
		$this->level = $level;
		$this->stringLevel = $name;
	}
	/**
	 * determines if another level is more severe than another.
	 * @param Level $level the level to test against.
	 */
	public function isGreaterOrEqualTo($level) {
		return $this->level >= $level->level;
	}

	/**
	 * determines if another level is equal to this level.
	 * @param Level $level the level to compare with
	 * @return bool <b>TRUE</b> if $level matches this level, <b>FALSE</b> otherwise.
	 */
	public function equals($level) {
		return ($level instanceof Level && ($this->level == $level->getIntLevel()));
	}

	/**
	 * returns the int representation of this level object.
	 * @return int the int reprentation of this level object.
	 */
	public function getIntLevel() {
		return $this->level;
	}

	/**
	 * @override
	 * returns the string representation of this level object.
	 * @return string the string level.
	 */
	public function __toString() {
		return $this->stringLevel;
	}

	/**
	 * generates a new debug level object
	 * @return Level a new level with the debug severity.
	 */
	public static function debug() {
		return new Level(Level::DEBUG, 'DEBUG');
	}

	/**
	 * generates a new info level object
	 * @return Level a new level with the info severity.
	 */	
	public static function info() {
		return new Level(Level::INFO, 'INFO');
	}

	/**
	 * generates a new warn level object
	 * @return Level a new level with the warn severity.
	 */
	public static function warn() {
		return new Level(Level::WARN, 'WARN');
	}

	/**
	 * generates a new error level object
	 * @return Level a new level with the error severity.
	 */
	public static function error() {
		return new Level(Level::ERROR, 'ERROR');
	}

	/**
	 * generates a new fatal level object.
	 *
	 */
	public static function fatal() {
		return new Level(Level::FATAL, 'FATAL');
	}

	/**
	 * generates a new off level object.
	 * @return Level a new level with the off severity.
	 */
	public static function off() {
		return new Level(Level::OFF, 'OFF');
	}

	/**
	 * generates a new all level object.
	 * @return Level a new level with the all severity.
	 */
	public static function all() {
		return new Level(Level::ALL, 'ALL');
	}

	/**
	 * generates a new trace level object.
	 * @return Level a new level with the trace severity.
	 */
	public static function trace() {
		return new Level(Level::TRACE, 'TRACE');
	}



}