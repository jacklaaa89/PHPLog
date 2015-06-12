<?php

namespace PHPLog\Writer;

use PHPLog\Writer\File;
use PHPLog\Event;
use PHPLog\Layout\FileNamePattern;
use PHPLog\Configuration;

/**
 * An extended File writer, with the added functionality of generating a new file
 * after a given timespan.
 * @version 1
 * @author Jack Timblin
 */
class TimeSpanFile extends File {

	/* generates a new file every hour */
	const HOURLY = 'Y-m-d-G';

	/* generates a new file daily */
	const DAILY = 'Y-m-d';

	/* generates a new file weekly */
	const WEEKLY = 'Y-W';

	/* generates a new file monthly */
	const MONTHLY = 'Y-m';

	/* generates a new file yearly */
	const YEARLY = 'Y';

	/* the selected timespan const */
	private $timespan;

	/* the date used in the last log event, this is compared
       to the date now in order to determine if we need to 
       close the current file handle and open a new file.
	*/
	private $date;

	/**
	 * Constructor - initializes the file writer, layout and sets
	 * the timespan
	 * @param array $config the configuration for this writer.
	 */
	public function __construct(Configuration $config) {
		parent::__construct($config); //sort out the layout and file location etc.
		//set timespan, default is daily.

		$timespan = $this->getConfig()->timespan;
		$this->timespan = (isset($timespan)) ? $timespan : TimeSpanFile::DAILY;
	}

	/**
	 * generates the date that will be used in the file name.
	 * mainly used to determine if the file has changed.
	 * @param Event $event the event that will be logged.
	 * @return string the date converted into the correct format.
	 */
	public function getDate(Event $event) {
		return date($this->timespan, $event->getDate());
	}

	/**
	 * @override
	 * attempts to parse the event and write the output to the log file.
	 * @param Event $event the event to write to the log file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	public function append(Event $event) {

		$date = $this->getDate($event);
		$file = $this->getFileLocation();

		$info = pathinfo($file);
		if(!isset($info['extension'])) {
			return false;
		}

		$pa = new FileNamePattern(array(
			'dateFormat' => $this->timespan,
			'file' => $this->getFileLocation()
		));

		if(!isset($this->date)) {
			$this->date = $date;
			$this->setFileLocation($pa->parse($event));
		} elseif($this->date != $date) {
			$this->date = $date;
			if(is_resource($this->handle)) {
				fclose($this->handle);
			}
			$this->handle = null;
			$this->setFileLocation($pa->parse($event));
		}

		$value = '';
		if($this->getLayout() !== null) {
			$value = $this->getLayout()->parse($event);
		}

		if(strlen($value) == 0) {
			return false;
		}

		return $this->write($value);

	}

}