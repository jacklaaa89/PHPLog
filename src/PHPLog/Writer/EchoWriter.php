<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Layout\Pattern;
use PHPLog\Configuration;

/**
 * This writer writes a log to the screen using echo using a defined pattern.
 * @version 1
 * @author Jack Timblin
 */
class EchoWriter extends WriterAbstract {

	/**
	 * Constructor - initializes the writer and setsup the layout.
	 * @param array config the config for this writer.
	 */
	public function init(Configuration $config) {
		$this->getLayoutConfig()->set('pattern', 'LOG - %level - %message|u - %date', true);
		$this->setLayout(new Pattern());
	}
	
	/**
	 * generates the log and then echos it to the screen.
	 * @param Event $event the event to log.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	public function append(Event $event) {
		//generate the log using the layout.
		$log = '';
		if($this->getLayout() !== null) {
			$log = $this->getLayout()->parse($event);
		}

		echo $log;
		return (strlen($log) > 0);
	}

}