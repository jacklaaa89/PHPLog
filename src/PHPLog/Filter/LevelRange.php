<?php

namespace PHPLog\Filter;

use PHPLog\Event;
use PHPLog\Level;
use PHPLog\FilterAbstract;
use PHPLog\Configuration;

/**
 * filter used to match a level from a level range.
 * @version 1
 * @author Jack Timblin
 */
class LevelRange extends FilterAbstract
{

    /* the minimum logging level to match */
    private $levelMin;

    /* the maximum logging level to match */
    private $levelMax;

    /* whether to accept the logging event on match. */
    private $acceptOnMatch = true;

    /**
     * Constructor - initializes the filter.
     * @param array $config the configuration for this filter.
     */
    public function init(Configuration $config) 
    {

        $this->levelMin = $config->get(
            'levelMin', Level::trace(), function ($levelMin) {
                return ($levelMin instanceof Level);
            }
        );

        $this->levelMax = $config->get(
            'levelMax', Level::fatal(), function ($levelMax) {
                return ($levelMax instanceof Level);
            }
        );

        $this->acceptOnMatch = $config->get('acceptOnMatch', true);
    }

    /**
     * @see \PHPLog\FilterAbstract::decide()
     */
    public function decide(Event $event) 
    {

        //if level max is not a Level.
        if(!($this->levelMax instanceof Level)) {
            return FilterAbstract::NEUTRAL;
        }

        //if level min is not a level.
        if(!($this->levelMin instanceof Level)) {
            return FilterAbstract::NEUTRAL;
        }

        //if level min is equal to all or is greater than max.
        if($this->levelMin->equals(Level::all())  
            || ($this->levelMin->isGreaterOrEqualTo($this->levelMax))
        ) {
            return FilterAbstract::NEUTRAL;
        }

        //if max if equal to off.
        if($this->levelMax->equals(Level::off())) {
            return FilterAbstract::NEUTRAL;
        }

        //check that the event level is between the range.
        $lmi = $this->levelMin->getIntLevel();
        $lma = $this->levelMax->getIntLevel();
        $e = $event->getLevel()->getIntLevel();

        if($e >= $lmi && $e <= $lma) {
            //in the range.
            return ($this->acceptOnMatch) ? FilterAbstract::ACCEPT : FilterAbstract::DENY;
        }

        return FilterAbstract::NEUTRAL;

    }

}