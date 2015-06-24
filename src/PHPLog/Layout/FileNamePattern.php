<?php

namespace PHPLog\Layout;

use PHPLog\Layout\Pattern;
use PHPLog\Event;
use PHPLog\Configuration;

/** 
 * An expansion of the Pattern class to not only allow for parsing a file location
 * it also allows us to push log information into a file name.
 *
 * This extension gives access to more static variables which relative to a file name.
 * which are:
 *
 * - %dirname - the directory in which the file in the given file location resides.
 * - %basename - the base name of the file in the file location.
 * - %filename - the file name without the extension.
 * - %extension - the extension of the passed file name.
 * - %format - the date format required in the date (as the format can change frequently in file names)
 *
 * @version 1
 * @author  Jack Timblin
 */
class FileNamePattern extends Pattern
{
    
    /* the date format used in the returned filename if a date was required. */
    protected $dateFormat = 'Y-m-d';

    /* the pattern used to generate the file name. */
    protected $pattern = '%dirname/%filename-%date{%format}.%extension';

    /* the file location to update. */
    protected $fileLocation;

    /**
     * initializes the layout using the configuration.
     * @param Configuration $config the configuration for this layout.
     * @see PHPLog\LayoutAbstract::init()
     */
    public function init(Configuration $config) 
    {
        if(!isset($config->pattern)) {
            $config->pattern = $this->pattern;
        }
        parent::init($config);
        $this->fileLocation = $config->get('file', '');
        $this->dateFormat = $config->get('dateFormat', $this->dateFormat);
    }

    /**
     * @override
     * attempts to parse the given event into a useable filename. This is obviously dependant
     * on the provided pattern on how useful this is.
     * @param Event $event the event that needs parsing.
     * @return string the event parsed into a useable filename.
     * @version 1.1 - now utilizes the new functionality of the 2.0 pattern parser.
     */
    public function parse(Event $event) 
    {

        if(!is_string($this->fileLocation) || strlen($this->fileLocation) == 0) {
            return '';
        }

        //add additional information into the event.
        $e = clone $event;
        $info = pathinfo($this->fileLocation);
        if(!isset($info['extension'])) {
            return '';
        }

        foreach($info as $k => $v) {
            $e->{$k} = $v;
        }

        $e->{'format'} = $this->dateFormat;

        $value = parent::parse($e);
        return $this->sanitizeFileName($value);

    }

    /**
     * sanitizes a generated file name so that it doesnt contain any illegal characters.
     * @param string $file the file to be sanitized.
     * @return string the santized file name.
     */
    private function sanitizeFileName($file) 
    {
        $file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $file);
        $file = preg_replace("([\.]{2,})", '', $file);
        return $file;
    }

}