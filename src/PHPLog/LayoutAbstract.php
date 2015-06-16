<?php

namespace PHPLog;

use PHPLog\Event;
use PHPLog\Extension;
use PHPLog\Renderer;
use PHPLog\Configuration;

/**
 * Base class for all layout implementations.
 * @version 1
 * @author Jack Timblin
 */
abstract class LayoutAbstract extends Extension {

	protected $config;

	/**
	 * returns the configuration for this layout.
	 * @return Configuration the layouts configuration.
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/** 
	 * Attempts to parse an event into a layout which can then be used by the writer.
	 * @param Event $event the event to parse.
	 * @return mixed the parsed event in the structure of the layout itself.
	 */
	public abstract function parse(Event $event);

	/**
	 * initializes the layout, by passing the configuration through from the writer.
	 * @param Configuration $config the configuration to pass through.
	 */
	public function init(Configuration $config) {
		$this->config = $config;
	}

	/**
	 * attempts to render a variable to push into a log.
	 * this allows for custom renderers for classes passed into the event.
	 * @param mixed the object to render
	 * @return string the renderered object.
	 */
	public function render($object) {
		$renderer = Logger::getRenderer();
		try {
		 	$renderer->render($object);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
		return $value;

	}

}