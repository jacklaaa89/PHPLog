<?php

namespace RMA\Core\Utilities\Logger\Writer;

use RMA\Core\Utilities\Logger\WriterAbstract;
use RMA\Core\Utilities\Logger\Event;
use RMA\Core\Utilities\Logger\Layout\Pattern;

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
	public function __construct($config) {
		parent::__construct($config);
		$pattern = 'LOG - %level - %message|u - %date';
		$config['pattern'] = (isset($config['pattern']) && strlen($config['pattern']) > 0) ? $config['pattern'] : $pattern;
		$this->setLayout(new Pattern($config));
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