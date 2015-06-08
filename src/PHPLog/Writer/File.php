<?php

namespace RMA\Core\Utilities\Logger\Writer;

use RMA\Core\Utilities\Logger\WriterAbstract;
use RMA\Core\Utilities\Logger\Event;
use RMA\Core\Utilities\Logger\Layout\Pattern;

/**
 * a writer which writes to a file file location.
 * @version 1
 * @author Jack Timblin
 *
 */
class File extends WriterAbstract {

	/* the location to store the file. */
	private $fileLocation;

	/* boolean flag, whether to append to the file or overwrite it. */
	private $append = true;

	/* the current resource to the file to write to. */
	protected $handle;

	/* whether to lock the file while writing takes place. */
	private $locking = true;

	/**
	 * Constructor - initializes the pattern and set the required variables. 
	 * @param array $config the configutation for this writer.
	 */
	public function __construct($config) {
		parent::__construct($config);
		$this->fileLocation = (isset($config['file'])) ? $config['file'] : '';
		$this->append = (isset($config['append'])) ? $config['append'] : true;
		$this->locking = (isset($config['locking'])) ? $config['locking'] : true;
		$this->setLayout(new Pattern($config));
	}

	/**
	 * returns the current file location to log to.
	 * @return string the file location.
	 */
	public function getFileLocation() {
		return $this->fileLocation;
	}

	/**
	 * sets the current file location.
	 * @param string $fileLocation the new file location.
	 */
	protected function setFileLocation($fileLocation) {
		$this->fileLocation = $fileLocation;
	}

	/**
	 * @override
	 * closes the resource handle to stop memory leakage.
	 */
	public function close() {
		if(is_resource($this->handle)) {
			fclose($this->handle);
		}
		$this->handle = null;
		parent::close();
	}

	/**
	 * attempts to open/create the log file at the specified location.
	 * @throws Exception if the file location is not set.
	 * triggers E_USER_WARNING if we could not either create or write to the file.
	 */
	public function open() {

		if($this->getFileLocation() === null) {
			throw new \Exception('no file location is set.');
		}

		if(!is_file($this->getFileLocation())) {
			$dir = dirname($this->getFileLocation());

			if(!is_dir($dir)) {
				$success = mkdir($dir, 0777, true);
				if($success === false) {
					$this->close();
					$this->warn('could not create file.');
					return false;
				}
			}

		}

		$mode = ($this->append) ? 'a' : 'w';
		$this->handle = fopen($this->getFileLocation(), $mode);
		if($this->handle === false) {
			$this->close();
			$this->warn('could not open file.');
			return false;
		}

		if($this->append) {
			fseek($this->handle, 0, SEEK_END);
		}

	}

	/**
	 * attempts to write the value to the file location.
	 * if the file location has not been opened, then this will be done.
	 * @param string $value the value to write to the log file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	protected function write($value) {
		if(!isset($this->handle)) {
			if($this->open() === false) {
				return false;
			}
		}

		if($this->locking) {
			return $this->writeLock($value);
		} else {
			return $this->writeNoLock($value);
		}

	}

	/**
	 * attempts to write a value to the log file WITHOUT locking the file.
	 * i.e concurrent access is allowed.
	 * @param string $value the value to write to the file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 *
	 */
	protected function writeNoLock($value) {
		if(fwrite($this->handle, $value) === false) {
			$this->warn('could not write to file.');
			$this->close();
			return false;
		}
		return true;
	}

	/**
	 * attempts to write a value to the log file WITH locking the file
	 * i.e no other process can modify the file while this process is writing to it.
	 * @param string $value the value to write to the log file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	protected function writeLock($value) {
		if(flock($this->handle, LOCK_EX)) {
			if(fwrite($this->handle, $value) === false) {
				$this->warn('could not write to file.');
				$this->close();
				return false;
			}
			flock($this->handle, LOCK_UN);
		} else {
			$this->warn('could not lock file for writing');
			$this->close();
			return false;
		}
		return true;
	}

	/** 
	 * @override
	 * attempts to parse the event and write the output to the log file.
	 * @param Event $event the event to write to the log file.
	 * @return boolean <b>TRUE</b> if no errors occured, <b>FALSE</b> otherwise.
	 */
	public function append(Event $event) {
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
