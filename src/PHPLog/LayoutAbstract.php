<?php

namespace PHPLog;

use PHPLog\Event;
use PHPLog\Extension;
use PHPLog\Renderer;
use PHPLog\Configuration;
use PHPLog\Logger;

/**
 * Base class for all layout implementations.
 * @version 1
 * @author Jack Timblin
 */
abstract class LayoutAbstract extends Extension
{

    /**
     * the config for this layout.
     */
    protected $config;

    /**
     * returns the configuration for this layout.
     * @return Configuration the layouts configuration.
     */
    public final function getConfig() 
    {
        return $this->config;
    }

    /**
     * gets the default renderer for this layout.
     * @return Renderer the default renderer for this layout.
     */
    public function getRenderer() 
    {
        return $this->getSystemService('renderer');
    }
    
    /** 
     * Attempts to parse an event into a layout which can then be used by the writer.
     * @param Event $event the event to parse.
     * @return mixed the parsed event in the structure of the layout itself.
     */
    public abstract function parse(Event $event);

    /**
     * initializes the layout, by passing the configuration through from the writer.
     * @param Configuration $config the configuration to pass through.
     */
    public function init(Configuration $config) 
    {
        $this->config = $config;
    }

    /**
     * attempts to render a variable to push into a log.
     * this allows for custom renderers for classes passed into the event.
     * @param mixed the object to render
     * @return string the renderered object.
     */
    public final function render($object) 
    {
        $renderer = $this->getRenderer();

        if(!($renderer instanceof Renderer)) {
            throw new \Exception('Renderer could be determined.');
        }

        try {
            $value = $renderer->render($object);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $value;

    }

}