<?php

namespace Tests;

use PHPLog\Logger;
use PHPLog\LoggerHierarchy;
use PHPLog\Writer\EchoWriter;
use PHPLog\Filter\LevelMatch;
use PHPLog\Filter\DenyAll;

/**
 * Test case to test that the logging functionality is working 
 * correctly, so we can add a filter, writer and that the hierarchy is
 * correctly initialized.
 * @version 1
 * @author Jack Timblin
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{

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

		//would usually be null, i.e no output, but ob_start returns an empty
		//string because thats the empty output buffer.
		ob_start();
		$logger->info('Tester Message');
		$message = ob_get_clean();
		$this->assertEquals('', $message);
	}


}