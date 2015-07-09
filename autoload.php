<?php

require __DIR__.'/src/PHPLog/Utilities/AutoLoader.php';

$autoLoader = new PHPLog\Utilities\AutoLoader();
$autoLoader->registerNamespaces(
	array(
		'PHPLog' 		   =>  __DIR__.'/src/PHPLog',
		'PHPLog\Exception' =>  __DIR__.'/src/PHPLog/Exception',
		'PHPLog\Filter'    =>  __DIR__.'/src/PHPLog/Filter',
		'PHPLog\Layout'    =>  __DIR__.'/src/PHPLog/Layout',
		'PHPLog\Renderer'  =>  __DIR__.'/src/PHPLog/Renderer',
		'PHPLog\Writer'    =>  __DIR__.'/src/PHPLog/Writer',
		'Tests'            =>  __DIR__.'/tests/Tests',
	)
)->register();