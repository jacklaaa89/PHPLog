<?php

namespace RMA\Core\Utilities\Logger\Renderer;

use RMA\Core\Utilities\Logger\RendererInterface;

/**
 * Used to renderer any \Exception class into a readable string.
 * @version 1
 * @author Jack Timblin
 */
class Throwable implements RendererInterface {

	/**
	 * @see RMA\Core\Utilities\Logger\RendererInterface::render()
	 */
	public function render($object) {

		if($object instanceof \Exception) {
			return 'Exception - ' . $object->getMessage() . ' - Thrown on Line: ' . $object->getLine() . ' in file: ' . $object->getFile();
		}

		return $string;

	}

}