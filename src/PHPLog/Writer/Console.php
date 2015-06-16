<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Level;
use PHPLog\Configuration;
use PHPLog\Layout\Pattern;

/**
 * 
 *
 */
class Console extends WriterAbstract {
	
	const STDOUT = 'php://stdout';
	const STDERR = 'php://stderr';

	protected $useMinErrorLevel = true;
	protected $minErrorLevel;
	protected $target;
	protected $handle;
	protected $pattern = '[PHPLog] - [LOG] - [%level] - [%date{\'Y-m-d H:i:s\'}] - %message%newline';

	public function init(Configuration $config) {
		$this->useMinErrorLevel = $config->get('useMinErrorLevel', true, function($useMinErrorLevel){
			return is_bool($useMinErrorLevel);
		});

		$uml = $this->useMinErrorLevel;

		$this->minErrorLevel = $config->get('minErrorLevel', Level::error(), function($level) use ($uml) {
			return ($level instanceof Level && $uml);
		});

		$this->target = $config->get('target', self::STDOUT, function($target) use ($uml) {
			return isset($target) && in_array($target, 
				array(
					self::STDOUT, 
					self::STDERR
				)) && !$uml;
		});

		if(!isset($this->getConfig()->layout->pattern)) {
			$this->getConfig()->layout->pattern = $this->pattern;
		}

		$this->setLayout(new Pattern());

	}

	public function append(Event $event) {
		//generate the layout.
		$log = '';
		if($this->getLayout() !== null) {
			$log = $this->getLayout()->parse($event);
		}

		if(strlen($log) == 0) {
			return false;
		}

		$this->setTarget($event);

		return $this->write($log);

	}

	public function write($log) {
		if(is_resource($this->handle) && strlen($log) > 0) {
			fwrite($this->handle, $log);
		}
	}

	private function setTarget(Event $event) {
		if($this->useMinErrorLevel) {
			$level = $event->getLevel();
			$this->target = ($level->isGreaterOrEqualTo($this->minErrorLevel)) 
				? self::STDERR : self::STDOUT;
		}

		if(is_resource($this->handle)) {
			fclose($this->handle);
		}
		$this->handle = fopen($this->target, 'w');

		if(!is_resource($this->handle)) {
			$this->close();
		}

	}

	public function close() {
		if(!$this->isClosed()) {
			if(is_resource($this->handle)) {
				fclose($this->handle);
			}
			parent::close();
		}
	}

}