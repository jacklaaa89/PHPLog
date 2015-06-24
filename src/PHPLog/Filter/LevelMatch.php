<?php

namespace PHPLog\Filter;

use PHPLog\Event;
use PHPLog\Level;
use PHPLog\FilterAbstract;
use PHPLog\Configuration;

/**
 * filter used to match an exact level from an event.
 * @version 1
 * @author Jack Timblin
 */
class LevelMatch extends FilterAbstract
{

    /* the logging level to match */
    private $levelToMatch;

    /* whether to accept the logging event on match. */
    private $acceptOnMatch = true;

    /**
     * Constructor - initializes the filter.
     * @param array $config the configuration for this filter.
     */
    public function init(Configuration $config) 
    {

        $this->levelToMatch = $config->get(
            'levelToMatch', null, function ($levelToMatch) {
                return ($levelToMatch instanceof Level);
            }
        );

        $this->acceptOnMatch = $config->get('acceptOnMatch', true);
    }

    /**
     * @see \PHPLog\FilterAbstract::decide()
     */
    public function decide(Event $event) 
    {
        if(!($this->levelToMatch instanceof Level)) {
            return FilterAbstract::NEUTRAL;
        }

        if($this->levelToMatch->equals($event->getLevel())) {
            return ($this->acceptOnMatch) ? FilterAbstract::ACCEPT : FilterAbstract::DENY;
        }

        return FilterAbstract::NEUTRAL;

    }

}