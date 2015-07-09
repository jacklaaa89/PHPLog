<?php

namespace PHPLog\Renderer;

use PHPLog\RendererInterface;

/**
 * This renderer is used to convert an array instance into a string.
 * it attempts to call json_encode on the array to convert it to a string.
 * @version 1
 * @author Jack Timblin
 */
class ArrayRenderer implements RendererInterface
{

    /**
     * @see PHPLog\RendererInterface::render()
     */
    public function render($object, $options = 0) 
    {
        if (!is_array($object)) {
            return '';
        }

        $result = json_encode($object, $options);

        if ($result === false) {
            return '';
        }

        //die(var_dump($result));

        return preg_replace('/""/', '","', stripslashes($result));

    }

}