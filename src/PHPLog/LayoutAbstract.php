<?php

namespace PHPLog;

use PHPLog\Event;
use PHPLog\Extension;

/**
 * Base class for all layout implementations.
 * @version 1
 * @author Jack Timblin
 */
abstract class LayoutAbstract extends Extension {
	
	/** 
	 * Attempts to parse an event into a layout which can then be used by the writer.
	 * @param Event $event the event to parse.
	 * @return mixed the parsed event in the structure of the layout itself.
	 */
	public abstract function parse(Event $event);

}