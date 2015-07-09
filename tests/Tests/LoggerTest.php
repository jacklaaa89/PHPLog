<?php

namespace Tests;

use PHPLog\Logger;
use PHPLog\LoggerHierarchy;
use PHPLog\Writer\EchoWriter;
use PHPLog\Filter\LevelMatch;
use PHPLog\Filter\DenyAll;

class LoggerTest extends \PHPUnit_Framework_TestCase
{

	public function testInitializeLogger()
	{

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
		$logger = Logger::getLogger('test_logger');
		$writer = new EchoWriter();

		$logger->addWriter($writer);

		$this->assertEquals(1, count($logger->getWriters()));
		$this->assertArrayHasKey($writer->getName(), $logger->getWriters());

	}

	public function testAddFilter()
	{
		$logger = Logger::getLogger('test_logger');

		//we will test add two filters to test the filter chain functionality.
		$logger->addFilter(new LevelMatch());
		$logger->addFilter(new DenyAll());
	}

	public function testAddRenderer()
	{

	}

	public function testAddByConfiguration() 
	{
		
	}

	public function testWriteNoFilters() 
	{

	}

	public function testWriteWithFilters() 
	{

	}


}