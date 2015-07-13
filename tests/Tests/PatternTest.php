<?php

namespace Tests;

use PHPLog\Logger;
use PHPLog\LoggerHierarchy;
use PHPLog\Writer\EchoWriter;
use PHPLog\Writer\Console;
use PHPLog\Filter\LevelMatch;
use PHPLog\Filter\DenyAll;
use PHPLog\Level;

/**
 * Test case to test that all of the writers do infact write to thier specified location.
 * @version 1
 * @author Jack Timblin
 */
class PatternTest extends \PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests that we can write using the Console writer. This writer will be set so 
	 * that all output is passed to STDOUT so it can be captured using output buffering.
	 */
	public function testIfStatement()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();
		$logger = Logger::getLogger(
			'test_logger', 
			array(
				'writers' => array(
					'Console' => array(
						'useMinErrorLevel' => false,
						'target'           => Console::OUTPUT,
						'layout'           => array(
							'pattern' => '%if ifValue > 2% %trueValue %endif%'
						),
					)
				),
				'extras' => array(
					'local' => array(
						'ifValue'   => 3,
						'trueValue' => 'It was more than three'
					)
				)  
			)
		);
		ob_start();
		$logger->info('Tester Message');

		$logs = ob_get_clean();

		$this->assertNotEquals('', $logs);

	}

	/**
	 * Tests that we can write using the Console writer. This writer will be set so 
	 * that all output is passed to STDOUT so it can be captured using output buffering.
	 */
	public function testIfElseStatement()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();
		$logger = Logger::getLogger(
			'test_logger', 
			array(
				'writers' => array(
					'Console' => array(
						'useMinErrorLevel' => false,
						'target'           => Console::OUTPUT,
						'layout'           => array(
							'pattern' => '%if ifValue > 4% %trueValue %else% %elseValue %endif%'
						),
					)
				),
				'extras' => array(
					'local' => array(
						'ifValue'   => 3,
						'trueValue' => 'It was more than four',
						'elseValue' => 'It was less than four'
					)
				)  
			)
		);
		ob_start();
		$logger->info('Tester Message');

		$logs = ob_get_clean();

		$this->assertNotEquals('', $logs);

	}

}