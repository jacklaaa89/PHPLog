<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Layout\Pattern;
use PHPLog\Level;
use PHPLog\Configuration;

/**
 * This writer logs to the system logger.
 * @version 1
 * @author Jack Timblin
 */
class Syslog extends WriterAbstract {

	/* the identifier used in the logger to identify the application in the log. */
	protected $applicationIdentifier = 'PHPLog';

	/* the default options is LOG_PID|LOG_CONS {@see http://php.net/manual/en/function.openlog.php#option} to 
	   all of the available options. This is the options to apply to the logger.
	 */
	protected $options = 'PID|CONS';

	/* the default facility is LOG_USER {@see http://php.net/manual/en/function.openlog.php#facility} to 
	   all of the available options. This is the type of program that is logging.
	 */
	protected $facility = 'USER';

	/* the default pattern for the log message. */
	protected $pattern = '[%level|u] - [%date{Y-m-d H:i:s}|u] - [%logger] - %message';

	/* the int representation of the options to provide the syslog. */
	private $opt;

	/* the int representation of the facility to provide the syslog. */
	private $fac;

	/**
	 * Constructor - initializes the writer and parses the options into the syslog equivilents
	 * @param array $config the configuration for this writer.
	 */
	public function __construct(Configuration $config) {
		parent::__construct($config);

		$this->options = $this->getConfig()->get('options', 'PID|CONS');
		$this->facility = $this->getConfig()->get('facility', 'USER');
		$this->applicationIdentifier = $this->getConfig()->get('ident', $this->applicationIdentifier);
		if(!isset($this->getConfig()->layout->pattern)) {
			$this->getConfig()->layout->pattern = $this->pattern;
		}

		$this->setLayout(new Pattern());

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
		if(!syslog($this->getSyslogEquivilentLevel($event->getLevel()), $value)) {
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
	 * @param Level $logLevel the level that we are parsing.
	 * @return int the syslog level that resembles the $levels level.
	 */
	private function getSyslogEquivilentLevel(Level $logLevel) {

		$level = LOG_DEBUG;

		switch($logLevel->getIntLevel()) {
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