<?php

namespace PHPLog;

/**
 * A container for classes that contain a extra variable scope, i.e classes that
 * hold a set of variables which get passed through to logs with each request.
 * @version 1
 * @author Jack Timblin 
 */
abstract class ExtraAbstract
{
    
    /* the array of extra variables that will be passed to logs. */
    private $extras = array();

    /**
     * returns the current extra variables in the current scope.
     * @return array the array of extra variables for the current scope.
     */
    public function getExtras() 
    {
        return $this->extras;
    }

    /** 
     * Adds an extra variable to pass to message in this loggers scope.
     * @param string $name  the name of the extra value.
     * @param mixed  $value the value to store.
     */
    public function addExtra($name, $value) 
    {
        $this->extras[$name] = $value;
    }

    /**
     * removes an extra variable.
     * @param string $name the name of the variable to remove.
     */
    public function removeExtra($name) 
    {
        if(!isset($this->extras[$name])) {
            return;
        }
        unset($this->extras[$name]);
    }

    /**
     * clears all of the extra variables stored in this logger.
     */
    public function clearExtras() 
    {
        $this->extras = array();
    }

}