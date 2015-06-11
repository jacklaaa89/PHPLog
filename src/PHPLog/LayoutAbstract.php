<?php

namespace PHPLog;

use PHPLog\Event;
use PHPLog\Extension;
use PHPLog\Renderer;

/**
 * Base class for all layout implementations.
 * @version 1
 * @author Jack Timblin
 */
abstract class LayoutAbstract extends Extension {

	protected $config;

	public function __construct($config) {
		parent::__construct();
		$this->config = $config;
	}
	
	/** 
	 * Attempts to parse an event into a layout which can then be used by the writer.
	 * @param Event $event the event to parse.
	 * @return mixed the parsed event in the structure of the layout itself.
	 */
	public abstract function parse(Event $event);

	/**
	 * attempts to render a variable to push into a log.
	 * this allows for custom renderers for classes passed into the event.
	 * @param mixed the object to render
	 * @return string the renderered object.
	 */
	public function render($object) {
		$renderer = Logger::getRenderer();
		return $renderer->render($object);
	}

}