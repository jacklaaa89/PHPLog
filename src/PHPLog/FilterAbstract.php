<?php

namespace PHPLog;

use PHPLog\Event;

/**
 * Base class for filters used in the logger.
 * allows for a filter chain, in which a event is passed down the chain to
 * determine if the filter should deny or allow the event.
 *
 * we also have a third response which is NEUTRAL which means the event will be passed
 * down the logging chain if a certain logger could not determine a firm ACCEPT or DENY based
 * on the event and params provided.
 *
 * An example: The filter chain is LevelMatch(fatal, acceptOnMatch=true) --> DenyAll
 * Event 1: info - doesnt match the LevelMatch - passes NEUTRAL --> passed to DenyAll which returns DENY.
 * Event 2: fatal - matches the LevelMatch -> returns ACCEPT
 *
 * Event 1 is thus blocked and Event 2 is logged to each of the writers.
 *
 * @version 1
 * @author Jack Timblin
 */
abstract class FilterAbstract {
	
	/* the next filter in the filter chain. */
	protected $next;

	/* ACCEPT - means that the filter passed and the log should be written. */
	const ACCEPT = 1;

	/* NEUTRAL - means the filter could not ACCEPT or DENY the log and the 
       log event should be passed down the filter chain.
	 */
	const NEUTRAL = 0;

	/* DENY - means that the filter failed and the log should not be written. */
	const DENY = -1;

	/**
	 * initializes the filter, this is required as 9/10 filters will
	 * need some sort of configuration.
	 * @param array $config the configuration for the filter.
	 */
	public abstract function __construct($config = array());

	/**
	 * This is the function filters should override to determine if a
	 * log event either is ACCEPT or DENY or it could not be determined and
	 * passed down the filter chain. This method defaultly returns NEUTRAL if the
	 * filter that extends this class does not override this method.
	 * @param Event $event the event to filter.
	 * @return int either ACCEPT/DENY/NEUTRAL based on the outcome of the filtering.
	 */
	public function decide(Event $event) {
		return self::NEUTRAL;
	}

	/**
	 * adds a new filter to the filter chain.
	 * @param FilterAbstract $next the filter to add to the chain. 
	 */
	public function addNext(FilterAbstract $next) {
		if($next instanceof FilterAbstract) {
			if($this->next !== null) {
				$this->next->addNext($next);
			} else {
				$this->next = $next;
			}
		}
	}

	/**
	 * gets the next filter in the chain.
	 * @return FilterAbstract|null the next filter in the chain or null if
	 * is no other filter in the filter chain.
	 */
	public function getNext() {
		return $this->next;
	}

} 