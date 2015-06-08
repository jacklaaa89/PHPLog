<?php

namespace RMA\Core\Utilities\Logger\Writer;


use RMA\Core\Utilities\Logger\Writer\File;
use RMA\Core\Utilities\Logger\Event;
use RMA\Core\Utilities\Logger\Layout\Bind;

/**
 * an extension of a file writer which is specifically defined to write logs to a csv file.
 * @version 1
 * @author Jack Timblin
 */
class CSV extends File {

	/* the file location in which we should save the csv file. */	
	private $fileLocation;

	/* the delimiter we should use when appending to the csv file. */
	private $delimiter;

	/**
	 * Constructor - initializes the writer.
	 * @param array $config the configuration for this writer.
	 */
	public function __construct($config) {
		parent::__construct($config);
		$this->fileLocation = (isset($config['file'])) ? $config['file'] : null;
		$this->delimiter = (isset($config['csvDelimiter'])) ? $config['csvDelimiter'] : ',';
		$this->setLayout(new Bind($config));
	}

	/** 
	 * @override
	 * attempts to parse the event and write the output to a csv file.
	 * @param Event $event the event to write to the log file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	public function append(Event $event) {
		$bind = array();
		if($this->getLayout() !== null) {
			$bind = $this->getLayout()->parse($event);
		}

		$bind = array_values($bind); //remove the keys.

		if(count($bind) == 0) {
			return false;
		}
		return $this->write($bind);
	}

	/**
	 * @override
	 * @see \RMA\Core\Utilities\Logger\Writer\File::writeNoLock()
	 * writes to a csv using fputcsv instead of fwrite.
	 */
	protected function writeNoLock($value) {
		if(fputcsv($this->handle, $value, $this->delimiter) === false) {
			$this->warn('could not write csv');
			$this->close();
			return false;
		}
		return true;
	}

	/**
	 * @override
	 * @see \RMA\Core\Utilities\Logger\Writer\File::writeLock()
	 * writes to a csv using fputcsv instead of fwrite.
	 */
	protected function writeLock($value) {
		if(flock($this->handle, LOCK_EX)) {
			if(fputcsv($this->handle, $value, $this->delimiter) === false) {
				$this->warn('could not write to file.');
				$this->close();
				return false;
			}
			flock($this->handle, LOCK_UN);
		} else {
			$this->warn('could not lock file for writing.');
			$this->close();
			return false;
		}
		return true;
	}

}