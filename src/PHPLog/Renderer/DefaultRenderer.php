<?php

namespace RMA\Core\Utilities\Logger\Renderer;

use RMA\Core\Utilities\Logger\RendererInterface;

/** 
 * This is the implementation of the default renderer class
 * this is the renderer that is used whenever there is pre-defined
 * renderer available for a particular object.
 * @version 1
 * @author Jack Timblin
 */
class DefaultRenderer implements RendererInterface {

	/**
	 * @see RMA\Core\Utilities\Logger\RendererInterface::render()
	 */
	public function render($object) {

		//attempt to cast the object to a string.
		$object = ($object !== null) ? $object : '';
		$object = (!is_string($object)) ? (string) $object : $object;

		return $object;

	}

}