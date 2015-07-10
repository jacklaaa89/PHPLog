<?php

namespace Tests;

use PHPLog\Logger;
use PHPLog\LoggerHierarchy;
use PHPLog\Writer\EchoWriter;
use PHPLog\Filter\LevelMatch;
use PHPLog\Filter\DenyAll;
use PHPLog\Level;

/**
 * Test case to test that the logging functionality is working 
 * correctly, so we can add a filter, writer and that the hierarchy is
 * correctly initialized.
 * @version 1
 * @author Jack Timblin
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * Tests that a Logger instance is initialized correctly and that
	 * it gets added to the hierarchy and that the parent gets set correctly.
	 */
	public function testInitializeLogger()
	{

		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger('test_logger');
		$hierarchy = Logger::getHierarchy();

		//check the amount of loggers and that the last entry was 
		//the one we just added.
		$this->assertEquals(1, $hierarchy->countLoggers());

		$lastInstance = $hierarchy->getLatestLoggerInstance();
		$this->assertEquals($logger->getName(), $lastInstance->getName());

		//check that this loggers instance has a root logger.
		$this->assertNotNull($logger->getParent());
		$this->assertInstanceOf('PHPLog\Root', $logger->getParent());

	}

	/**
	 * Tests that we can use namespaces to specify parents to loggers.
	 */
	public function testParentHierarchy()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$test = Logger::getLogger('test');
		$logger = Logger::getLogger('test.test_logger');
		$hierarchy = Logger::getHierarchy();

		//because of the test namespace, the test logger should be the parent of test.test_logger.
		//check that this loggers instance has a root logger.
		$this->assertNotNull($logger->getParent());
		$this->assertEquals($test->getName(), $logger->getParent()->getName());

		//also test that the test logger has a root logger.
		$this->assertNotNull($test->getParent());
		$this->assertInstanceOf('PHPLog\Root', $test->getParent());
	}

	/**
	 * tests that we can add a writer to a logger instance.
	 */
	public function testAddWriter() 
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger('test_logger');
		$writer = new EchoWriter();

		$logger->addWriter($writer);

		$this->assertEquals(1, count($logger->getWriters()));
		$this->assertArrayHasKey($writer->getName(), $logger->getWriters());

	}

	/**
	 * tests that we can add a filter chain to a logger instance.
	 */
	public function testAddFilter()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger('test_logger');

		//we will test add two filters to test the filter chain functionality.
		$logger->addFilter(new LevelMatch());
		$logger->addFilter(new DenyAll());

		//test the filter chain has been added in that order.
		$filter = $logger->getFilter(); $i = 0;
		while ($filter !== null) {
			if ($i == 0) {
				$this->assertInstanceOf('PHPLog\Filter\LevelMatch', $filter);
			} else if ($i > 0) {
				$this->assertInstanceOf('PHPLog\Filter\DenyAll', $filter);
			}
			$filter = $filter->getNext(); $i++;
		}
	}

	/**
	 * Mainly tests that we can set a logger to a threshold.
	 * Also confirms the functionality of disabling propogation and setting
	 * level and 'enablePropogation' in the configuration array.
	 */
	public function testSetLoggerThreshold()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();
		$logger = Logger::getLogger(
			'test_logger', 
			array(
				'enablePropogation' => false,
				'level'				=> Level::warn(),
				'writers'           => array(
					'EchoWriter' => array()
				)  
			)
		);

		$tMessage = 'Tester Message';

		//attempt an info message, should be no output.
		ob_start();
		$logger->info($tMessage);
		$message = ob_get_clean();

		$this->assertEquals('', $message);

		//attempt a fatal message, should succeed.
		ob_start();
		$logger->fatal($tMessage);
		$message = ob_get_clean();

		$this->assertNotEquals('', $message);

	}

	/**
	 * tests that we can add a renderer to a logger instance.
	 */
	public function testAddRenderer()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger('test_logger');
		$renderer = new TestRenderer();
		$logger->addRenderer('int', $renderer, false);

		$renderers = $logger->getRenderer()->getRenderers();
		$this->assertArrayHasKey('int', $renderers);
		$this->assertArrayHasKey('renderer', $renderers['int']);

		$this->assertInstanceOf('Tests\TestRenderer', $renderers['int']['renderer']);

	}

	/**
	 * tests that we can initialize a logger using a configuration array.
	 */
	public function testAddByConfiguration() 
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger(
			'test_logger',
			array(
				'writers' => array(
					'EchoWriter' => array()
				),
				'filters' => array(
					'LevelMatch' => array()
				)
			)
		);

		$this->assertInstanceOf('PHPLog\Filter\LevelMatch', $logger->getFilter());
		$this->assertEquals(1, count($logger->getWriters()));
		$this->assertArrayHasKey('PHPLog\Writer\EchoWriter', $logger->getWriters());

	}

	/**
	 * tests that we can write using a logger with no filters.
	 */
	public function testWriteNoFilters() 
	{	

		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger(
			'test_logger',
			array(
				'writers' => array(
					'EchoWriter' => array()
				)
			)
		);

		ob_start();
		$logger->info('Tester Message');
		$message = ob_get_clean();
		$this->assertNotEquals('', $message);

	}

	/**
	 * tests that we can write using a logger with filters.
	 */
	public function testWriteWithFilters() 
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		$logger = Logger::getLogger(
			'test_logger',
			array(
				'writers' => array(
					'EchoWriter' => array()
				),
				'filters' => array(
					'DenyAll' => array()
				)
			)
		);

		//would usually be null, i.e no output, but ob_get_clean returns an empty
		//string because thats the empty output buffer.
		ob_start();
		$logger->info('Tester Message');
		$message = ob_get_clean();
		$this->assertEquals('', $message);
	}

	/**
	 * Tests that we can add extra values to a log and they will be used.
	 * We will test all three extra scope levels. Global, Logger and Individual Log Event.
	 * This test also confirms the functionality of changing the pattern used when logging events.
	 */
	public function testAddExtrasToLog()
	{
		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();

		//add a standard echo writer, and a string global value and an array local value as
		// extras.

		$global = 'Tester Global'; $local = array('key' => 'value');
		$logEvent = 1;

		$logger = Logger::getLogger(
			'test_logger',
			array(
				'writers' => array(
					'EchoWriter' => array(
						'layout' => array(
							'pattern' => 'TESTER-LOG - [%level] - %testerGlobal - %testerLocal - %logEvent'
						)
					)
				),
				'extras' => array(
					'global' => array(
						'testerGlobal' => $global
					),
					'local' => array(
						'testerLocal' => $local
					)
				)
			)
		);

		ob_start();
		$logger->info('Tester Message', array('logEvent' => $logEvent));
		$message = ob_get_clean();

		//assert that the log contains the global extra.
		$this->assertRegExp('/'.$global.'/', $message);

		//assert that the log contains the local extra, the default array renderer
		//performs a recursive json_encode on the value.
		$this->assertRegExp('/'.json_encode($local).'/', $message);

		//assert that the log contains the logEvent extra.
		$this->assertRegExp('/'.$logEvent.'/', $message);

	}


}