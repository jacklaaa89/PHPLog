<?php

namespace PHPLog\Filter;

use PHPLog\Event;
use PHPLog\FilterAbstract;
use PHPLog\Configuration;

/**
 * Filter to apply to Loggers which determines whether to allow the
 * log event based on the function that the logger was called from.
 * Obviously, the logger has to be called from a function for this to apply.
 * @version 1
 * @author Jack Timblin
 */
class FunctionMatch extends FilterAbstract
{

    /* the function name to match, this is case sensitive */
    private $function;

    /* whether to accept this logging event on a match. */
    private $acceptOnMatch = true;

    /**
     * Constructor - initializes the filter.
     * @param array $config the configuration for this filter.
     */
    public function init(Configuration $config) 
    {

        $this->function = $config->get('function', null);
        $this->function = (isset($this->function)) ? trim($this->function, ' \\') : null;

        $this->acceptOnMatch = $config->get('acceptOnMatch', true);
    }

    /**
     * @see \PHPLog\FilterAbstract::decide()
     */
    public function decide(Event $event) 
    {

        if($this->function === null) {
            return FilterAbstract::NEUTRAL;
        }

        if($event->getFunction() === null) {
            return FilterAbstract::NEUTRAL;
        }

        if($event->getFunction() == $this->function) {
            return ($this->acceptOnMatch) ? FilterAbstract::ACCEPT : FilterAbstract::DENY;
        }

        return FilterAbstract::NEUTRAL;

    }

}