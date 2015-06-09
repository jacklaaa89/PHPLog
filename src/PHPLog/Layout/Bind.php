<?php

namespace PHPLog\Layout;

use PHPLog\Layout\Pattern;
use PHPLog\Event;

/**
 * This class uses the parser in Pattern to generate a key => value array of values
 * which represent the current log event.
 * @version 1
 * @author Jack Timblin
 */
class Bind extends Pattern {
	
	/* the pattern used in this event. A pattern needs to have a delimiter */
	protected $pattern = '%level,%message,$date{Y-m-d H:i:s}';

	/* the delimiter used to seperate the values in the pattern, cannot be the same as the identifier */
	protected $delimiter = ',';

	/* the arryay of keys to use in the completed bind. */
	private $keys = array();

	/**
	 * @override
	 * Constructor - initializes the layout and parses the keys from the pattern.
	 * @param array $config the config for this layout.
	 * @version 1.1 - added sanity checks on the provided config and made a pattern manditory.
	 */
	public function __construct($config) {
		parent::__construct($config);

		$this->delimiter = (isset($config['delimiter']) && strlen($config['delimiter']) == 1) ? $config['delimiter'] : $this->delimiter;

		if($this->delimiter == $this->getIdentifier()) {
			throw new \Exception('identifier cannot match delimiter');
		}

		if(!isset($config['pattern'])) {
			throw new \Exception('a pattern must be defined');
		}

		$this->pattern = $config['pattern'];
		$this->keys = explode($this->delimiter, $this->pattern);
		foreach($this->keys as &$value) {
			$value = trim($value); //remove any whitespace from either side.
			preg_match('/'.$this->getIdentifier().'(\w+)/', $value, $matches);
			$value = $matches[1];
		}
	}

	/**
	 * @override
	 * parses the pattern into a bind array.
	 * @param Event $event the event to parse.
	 * @return array the bind array for a database query.
	 */
	public function parse(Event $event) {
		//filter the events properties.
		$e = clone $event;
		$this->filterParams($e);
		$params = explode($this->delimiter, parent::parse($e));
		$bind = array();
		for($i = 0; $i < count($params); $i++) {
			$bind[':'.$this->keys[$i]] = $params[$i];
		}
		return $bind;
	}

	/**
	 * filters out the event object to not have the delimiter in any of the variables.
	 * @param Event &$event a reference to the event object.
	 */
	private function filterParams(Event &$event) {

		$reflect = new \ReflectionClass($event);
		$props = $reflect->getProperties();

		foreach($props as $prop) {
			$prop->setAccessible(true);
			$prop->setValue($event, str_replace($this->delimiter, '', $prop->getValue($event)));
		}

		$vars = get_object_vars($event);

		foreach($vars as $name => $val) {
			$event->{$name} = str_replace($this->delimiter, '', $this->render($val));
		}

	}

}