<?php

namespace PHPLog\Renderer;

use PHPLog\RendererInterface;

/** 
 * This is the implementation of the default renderer class
 * this is the renderer that is used whenever there is pre-defined
 * renderer available for a particular object.
 * @version 1
 * @author Jack Timblin
 */
class DefaultRenderer implements RendererInterface {

	/**
	 * @see PHPLog\RendererInterface::render()
	 */
	public function render($object) {

		//attempt to cast the object to a string.
		$object = ($object !== null) ? $object : '';
		//check to see if this variable is an array or object.
		if(is_array($object) || is_object($object)) {
			ob_start();
			var_dump($object);
			$object = ob_get_clean();
		}
		$object = (!is_string($object)) ? (string) $object : $object;

		return $object;

	}

}