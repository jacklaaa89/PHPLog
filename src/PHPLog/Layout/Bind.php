<?php

namespace PHPLog\Layout;

use PHPLog\Layout\Pattern;
use PHPLog\Event;
use PHPLog\Configuration;
use PHPLog\Exception\CompilerException;

/**
 * This class uses the parser in Pattern to generate a key => value array of values
 * which represent the current log event.
 * @version 1
 * @author Jack Timblin
 */
class Bind extends Pattern
{
    
    /* the pattern used in this event. A pattern needs to have a delimiter */
    protected $pattern = '%level,%message,$date{Y-m-d H:i:s}';

    /* the delimiter used to seperate the values in the pattern, cannot be the same as the identifier */
    protected $delimiter = ',';

    /* the arryay of keys to use in the completed bind. */
    private $keys = array();

    /**
     * initializes the layout using the configuration.
     * @param Configuration $config the configuration for this layout.
     * @see PHPLog\LayoutAbstract::init()
     */
    public function init(Configuration $config) 
    {

        parent::init($config);

        $this->delimiter = $config->get('delimiter', $this->delimiter);

        if($this->delimiter == $this->getIdentifier()) {
            throw new \Exception('identifier cannot match delimiter');
        }

        if(!isset($config->pattern)) {
            throw new \Exception('a pattern must be defined');
        }

        $this->pattern = $config->pattern;
        $this->keys = $this->parseValueNames();
    }

    /**
     * parses the names of the variables from the pattern and
     * stores them as the keys to use in the bind.
     * @return array an array of keys used in the pattern.
     * @throws Exception if the parse failed or we found no keys.
     */
    private function parseValueNames() 
    {
        $regexes = $this->getRegex();

        if(!preg_match_all($regexes[0], $this->pattern, $matches)) {
            throw new \Exception('invalid pattern defined.');
        }

        //check have matches.
        if(count($matches) == 0 || !isset($matches[2]) || count($matches[2]) == 0) {
            throw new \Exception('invalid pattern defined.');
        }

        $keys = array();
        for($i = 0; $i < count($matches[2]); $i++) {
            $keys[] = $matches[2][$i];
        }

        return $keys;

    }

    /**
     * @override
     * parses the pattern into a bind array.
     * @param Event $event the event to parse.
     * @return array the bind array for a database query.
     */
    public function parse(Event $event) 
    {
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
    private function filterParams(Event &$event) 
    {

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