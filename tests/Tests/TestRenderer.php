<?php

namespace Tests;

use PHPLog\RendererInterface;

class TestRenderer implements RendererInterface 
{
	
	public function render($object, $options = 0) {
		return intval($object);
	}
	
}