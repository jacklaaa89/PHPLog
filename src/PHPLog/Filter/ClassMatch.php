<?php

namespace PHPLog\Filter;

use PHPLog\Event;
use PHPLog\FilterAbstract;
use PHPLog\Configuration;

/**
 * Filter to apply to Loggers which determines whether to allow the
 * log event based on the class that the logger was called from.
 * Obviously, the logger has to be called from a class for this to apply.
 * @version 1
 * @author Jack Timblin
 */
class ClassMatch extends FilterAbstract
{

    /* the class name to match, this is namespaced case sensitive. */
    private $className;

    /* whether to accept the logging event on match. */
    private $acceptOnMatch = true;

    /**
     * Constructor - initializes the filter.
     * @param array $config the configuration for this filter.
     */
    public function init(Configuration $config) 
    {

        $this->className = $config->get('class', null);
        $this->className = (isset($this->className)) ? trim($this->className, ' \\') : null;

        $this->acceptOnMatch = $config->get('acceptOnMatch', true);
    }

    /**
     * @see \PHPLog\FilterAbstract::decide()
     */
    public function decide(Event $event) 
    {

        if($this->className === null) {
            return FilterAbstract::NEUTRAL;
        }

        if($event->getClass() === null) {
            return FilterAbstract::NEUTRAL;
        }

        if($event->getClass() == $this->className) {
            return ($this->acceptOnMatch) ? FilterAbstract::ACCEPT : FilterAbstract::DENY;
        }

        return FilterAbstract::NEUTRAL;

    }

}