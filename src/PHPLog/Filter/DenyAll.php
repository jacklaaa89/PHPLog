<?php

namespace PHPLog\Filter;

use PHPLog\Event;
use PHPLog\FilterAbstract;

/**
 * Filter which denys all logging events, good at the end of the filter chain when
 * applying other filters to accept on match.
 * @version 1
 * @author Jack Timblin
 */
class DenyAll extends FilterAbstract {

	/**
	 * Constructor - initializes the filter.
	 * @param array $config the configuration for this filter.
	 */
	public function __construct($config = array()) {}

	/**
	 * @see \RMA\Core\Utilities\Logger\FilterAbstract::decide()
	 */
	public function decide(Event $event) {
		return FilterAbstract::DENY;
	}

}