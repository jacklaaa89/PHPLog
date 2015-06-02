<?php

namespace PHPLog;

/**
 * class which adds default functionality to all writer and layout classes.
 * @version 1
 * @author Jack Timblin
 */
class Extension {

	/* whether this extension has been shutdown or not. */
	protected $closed = false;
	
	/**
	 * gets the name of the extension.
	 * @return string the name of the extension.
	 */
	public function getName() {
		return get_class($this);
	}

	/**
	 * called in the constructor, should be overriden by subclasses to add
	 * extra functionality on construction.
	 */
	public function init() {
		$this->closed = false;
	}

	/**
	 * Constructor - calls the init() method to allow subclasses to initialize correctly.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * shutdowns the extension, should be overriden by subclasses to allow
	 * to close down successfully.
	 */
	public function close() {
		$this->close = true;
	}

	/**
	 * Destructor - closes the extension when the object is destructed.
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * checks to see if the extension has been closed.
	 * @return boolean <b>TRUE</b> if the extension has been closed, <b>FALSE</b> otherwise.
	 */
	public function isClosed() {
		return $this->closed;
	}

	/**
	 * generates a E_USER_WARNING with a message.
	 * @param string the message to send with the error.
	 */
	public function warn($message) {
		trigger_error($message, E_USER_WARNING);
	}

}