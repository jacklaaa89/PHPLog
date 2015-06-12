<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Configuration;

/**
 * A writer which completely ignores all incoming log events. Mainly for use in 
 * conjunction with ignoring logs below a certain threshold, instead of passing them to the
 * root logger.
 * @version 1
 * @author Jack Timblin
 */
class Null extends WriterAbstract {

	public function init(Configuration $config) {}

	/**
	 * This renderer completely disables logging, and just returns true.
	 * Is good to firmly ignore below a certain threshold and also for the Root Logger.
	 */
	public function append(Event $event) {
		return true;
	}

}