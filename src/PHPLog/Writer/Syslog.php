<?php

namespace PHPLog\Writer;

use PHPLog\Event;
use PHPLog\Level;
use PHPLog\WriterAbstract;
use PHPLog\Layout\Pattern;

class Syslog extends WriterAbstract {

	protected $applicationIdentifier = 'PHPLog';
	protected $options = 'PID|CONS';
	protected $facility = 'USER';

	protected $pattern = '[%level|u] - [%date{Y-m-d H:i:s}|u] - %message';

	private $opt;
	private $fac;

	/**
	 * Constructor - initializes the writer and parses the options into the syslog equivilents
	 * @param array $config the configuration for this writer.
	 */
	public function __construct($config) {
		parent::__construct($config);
		$this->options = (isset($config['options'])) ? $config['options'] : 'PID|CONS';
		$this->facility = (isset($config['options'])) ? $config['facility'] : 'USER';
		$this->applicationIdentifier = (isset($config['ident'])) ? $config['ident'] : $this->applicationIdentifier;
		if(!isset($config['pattern'])) {
			$config['pattern'] = $this->pattern;
		}

		$this->setLayout(new Pattern($config));

		$this->opt = $this->getOption($this->options);
		$this->fac = $this->getOption($this->facility);

	}

	/**
	 * @override
	 * closes the connection to the log if we are not already closed.
	 */
	public function close() {
		if(!$this->isClosed()) {
			parent::close();
			closelog();
		}
	}

	/**
	 * @override
	 * attempts to append a message to the systems logger.
	 * @param Event $event the event to log.
	 * @return boolean returns <b>TRUE</b> on success, <b>FALSE</b> on failure.
	 */
	public function append(Event $event) {
		$value = '';
		if($this->getLayout() !== null) {
			$value = $this->getLayout()->parse($event);
		}
		if(strlen($value) == 0) {
			return false;
		}
		if(!openlog($this->applicationIdentifier, $this->opt, $this->fac)) {
			return false;
		}
		if(!syslog($this->getSyslogEquivilentLevel($event), $value)) {
			return false;
		}
		return closelog();
	}

	/**
	 * parses string options into int options that will be provided to syslog.
	 * @param string $options the options to parse.
	 * @return int the integer value to those options.
	 */
	private function getOption($options) {
		$value = 0;
			
		$opts = (strpos($options, '|') !== false) ? explode('|', $options) : array($options);

		foreach($opts as $opt) {
			if(isset($opt) && strlen($opt) > 0) {
				$c = 'LOG_' . trim($opt);
				if(defined($c)) {
					$value |= constant($c);
				} else {
					$this->warn('invalid option defined.');
				}
			}
		}
		return $value;
	}

	/**
	 * determines the syslog level to use based on the level in the log
	 * event.
	 * @param Level $level the level that we are parsing.
	 * @return int the syslog level that resembles the $levels level.
	 */
	private function getSyslogEquivilentLevel(Level $level) {

		$level = LOG_DEBUG;

		switch($level->getIntLevel()) {
			case Level::FATAL:
				$level = LOG_ALERT;
				break;
			case Level::ERROR:
				$level = LOG_ERR;
				break;
			case Level::WARN:
				$level = LOG_WARNING;
				break;
			case Level::INFO:
				$level = LOG_INFO;
				break;
			default:
				$level = LOG_DEBUG;
				break;
		}

		return $level;

	}

}