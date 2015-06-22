<?php

namespace PHPLog;

use PHPLog\Event;
use PHPLog\Extension;
use PHPLog\LayoutAbstract;
use PHPLog\Configuration;

/**
 * Base class for all writer implementations.
 * @version 1
 * @author Jack Timblin
 */
abstract class WriterAbstract extends Extension {

	/* the layout for this writer. */
	protected $layout;

	/* the configuration passed to this writer. */
	protected $config;

	/* the logger associated with this writer. */
	protected $logger;

	/**
	 * Constructor - base initialisation for a writer instance.
	 * @param array $config the configuration for this writer instance.
	 */
	public final function __construct($config = array()) {
		parent::__construct();

		if(!is_array($config) && !($config instanceof Configuration)) {
			$config = array();
		}

		if(!($config instanceof Configuration)) {
			$config = new Configuration($config);
		}

		if(!isset($config['layout']) && !($config['layout'] instanceof Configuration)) {
			$config->layout =  new Configuration(array()); //empty config.
		}
		$this->config = $config;

	}

	/**
	 * used to initialize the writer adapter with some configuration.
	 * @param Configuration $config the configuration for this writer.
	 */
	public abstract function init(Configuration $config);

	/**
	 * returns the configuration passed to this writer.
	 * @return array the writers configuration.
	 */
	public final function getConfig() {
		return $this->config;
	}

	/**
	 * gets the logger assocuated with this layout.
	 */
	public final function getLogger() {
		return $this->logger;
	}

	/**
	 * sets the logger instance with this layout is associated with.
	 * @param Logger $logger the logger instance.
	 */
	public final function setLogger(Logger $logger) {
		$this->logger = $logger;
		$this->init($this->config);
	}

	/** 
	 * attempts to log an event if the writer is not closed and
	 * has a layout attached.
	 * @param Event $event the log event to log.
	 */
	public final function log(Event $event) {
		if($this->isClosed()) {
			return false;
		}

		if(!($this->getLayout() instanceof LayoutAbstract)) {
			return false;
		}

		return $this->append($event);
	}

	/**
	 * method all writer implementations need to implement.
	 * called in the log method to append the $event to the writers output.
	 * @param Event $event the event to append to the writer.
	 * @return boolean <b>TRUE</b> if the writer successfully appended the log, <b>FALSE</b> otherwise.
	 */
	public abstract function append(Event $event);

	/**
	 * sets the layout to use in this writer.
	 * @param LayoutAbstract the layout to use in this writer.
	 */
	public final function setLayout(LayoutAbstract $layout) {
		$this->layout = $layout;
		$this->layout->setLogger($this->logger);
		$this->layout->init($this->config->layout);
	}

	/**
	 * returns the layout for this writer.
	 * @return LayoutAbstract the layout.
	 */
	public final function getLayout() {
		return $this->layout;
	}

	/**
	 * gets the configuration for the layout.
	 * @return Configuration the layout configuration.
	 */
	public function getLayoutConfig() {
		return $this->config->layout;
	}
}