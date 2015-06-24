<?php

namespace PHPLog;

/**
 * Global Configuration class used for configuration.
 * variables can be accessed with either the '->' operator or as an array key i.e ($obj['key']).
 * @version 1
 * @version 2 - Configuration now implements \Iterator so configuration can be iterated over.
 * @author Jack Timblin
 */
class Configuration implements \ArrayAccess, \Countable, \Iterator
{

    private $position = 0;
    
    /**
     * Constructor - assigns all of the variables to this configuration object
     * from the array.
     * @param array $config the configuration to give this configuration class.
     */
    public function __construct($config) 
    {
        foreach($config as $key => $value) {
            $this->offsetSet($key, $value);
        }
        $this->position = 0;
    }

    /**
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($index) 
    {
        $index = strval($index);
        return isset($this->{$index});
    }

    /**
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($index) 
    {
        $index = strval($index);

        if(!$this->offsetExists($index)) {
            return null;
        }

        return $this->{$index};
    }

    /**
     * gets a variable assigned to the config, if its not defined
     * the default value is returned.
     * @param mixed         $index        the value to get.
     * @param mixed         $defaultValue the value to return if the $index value is not defined.
     * @param \Closure|null $comparable   a function which is ran on the var to determine its validility. The closure must return a boolean value to determine if the value is correct.
     * validility. The closure must return a boolean value to determine if the value is correct.
     * @return mixed the $index value or if that not defined the $defaultValue
     */
    public function get($index, $defaultValue = null, $comparable = null) 
    {
        if(!$this->offsetExists($index)) {
            return $defaultValue;
        }

        $var = $this->offsetGet($index);

        //get the result from the comparable if its an instanceof a closure.
        if($comparable instanceof \Closure) {
            $result = $comparable($var);
            if(!is_bool($result) || !$result) {
                return $defaultValue;
            }
        }

        return $var;
    }

    /**
     * sets a variable in the config.
     * @param mixed   $index          the index of the variable.
     * @param mixed   $value          the value to assign to the key.
     * @param boolean $onlySetOnEmpty [optional] whether to set the variable regardless or
     * only if the variable isnt already defined in the config.
     */
    public function set($index, $value, $onlySetOnEmpty = false) 
    {
        if($onlySetOnEmpty) {
            if(!$this->offsetExists($index)) {
                $this->offsetSet($index, $value);
            }
            return;
        }
        $this->offsetSet($index, $value);
    }

    /**
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($index, $value) 
    {
        $index = strval($index);
        $this->{$index} = (is_array($value)) ? new Configuration($value) : $value;
    }

    /**
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($index) 
    {
        $index = strval($index);
        $this->{$index} = null;
    }

    /**
     * @see \Countable::count()
     */
    public function count() 
    {
        return count(get_object_vars($this));
    }

    /**
     * @see \Iterator::rewind()
     */
    public function rewind() 
    {
        $this->position = 0;
    }

    /**
     * @see \Iterator::current()
     */
    public function current() 
    {
        $vars = get_object_vars($this);
        $keys = (count($vars) > 0) ? array_keys($vars) : array();
        return (isset($vars[$keys[$this->position]])) ? $vars[$keys[$this->position]] : null;
    }

    /**
     * @see \Iterator::key()
     */
    public function key() 
    {
        $vars = get_object_vars($this);
        $keys = (count($vars) > 0) ? array_keys($vars) : array();
        return (isset($keys[$this->position])) ? $keys[$this->position] : null;
    }

    /**
     * @see \Iterator::next()
     */
    public function next() 
    {
        ++$this->position;
    }

    /**
     * @see \Iterator::valid()
     */
    public function valid() 
    {
        $vars = get_object_vars($this);
        $keys = (count($vars) > 0) ? array_keys($vars) : array();
        return (isset($keys[$this->position]));
    }

}