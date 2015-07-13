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
class WriterTest extends \PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests that we can write using the Console writer. This writer will be set so 
	 * that all output is passed to STDOUT so it can be captured using output buffering.
	 */
	public function testConsole()
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
							'pattern' => '[LOG] - [%level] - [%date] - %message%newline'
						),
					)
				)  
			)
		);
		ob_start();
		$logger->info('Tester Message');

		$logs = ob_get_clean();

		$this->assertNotEquals('', $logs);

	}

	public function testCSV()
	{

		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();
		$logger = Logger::getLogger(
			'test_logger', 
			array(
				'writers' => array(
					'CSV' => array(
						'file'   => __DIR__.'/test.csv',
						'append' => false,
						'layout' => array(
							'pattern' => '%level,%message,%date{\'Y-m-d H:i:s\'},%file'
						)
					)
				)  
			)
		);

		$logger->info('A Tester Message');

		$logger->close(); //close this logger instance.

		//open the csv and determine if there is one row in the CSV (where the log has been written.)
		if (($handle = fopen(__DIR__.'/test.csv', 'r')) === false) {
			$this->fail('The CSV file could not be opened, which means it could not be generated');
		}

		$count = 0;
		while (($data = fgetcsv($handle)) !== false) {
			if (count($data) > 0) {
				$count++;
			}
		}

		//close the handle.
		fclose($handle);

		//remove the tester csv file.
		if (file_exists(__DIR__.'/test.csv')) {
			unlink(__DIR__.'/test.csv');
		}
		
		$this->assertEquals(1, $count);

	}

	/**
	 * Tests that we can write using echo() with the EchoWriter.
	 */
	public function testEchoWriter()
	{

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
		$logger->info('A Tester Log');

		$this->assertNotEquals('', ob_get_clean());

	}

	public function testFile()
	{

		//clear any previous configuration (as hierarchy is stored between calls)
		Logger::getHierarchy()->clear();
		$logger = Logger::getLogger(
			'test_logger', 
			array(
				'writers' => array(
					'File' => array(
						'file'    => __DIR__.'/testFile.csv',
						'append'  => false,
						'locking' => true
					)
				)  
			)
		);

		$logger->info('A Tester Message');

		$logger->close(); //close this logger instance.

		//open the csv and determine if there is one row in the CSV (where the log has been written.)
		if (($handle = fopen(__DIR__.'/testFile.csv', 'r')) === false) {
			$this->fail('The CSV file could not be opened, which means it could not be generated');
		}

		//close the handle.
		fclose($handle);
		

		$this->assertEquals(1, $this->getLines(__DIR__.'/testFile.csv'));

		//remove the tester csv file.
		if (file_exists(__DIR__.'/testFile.csv')) {
			unlink(__DIR__.'/testFile.csv');
		}

	}

	/**
	 * grabs the number of lines in a file.
	 * @param string $file the file location
	 * @return int the amount of lines in the file.
	 */
	private function getLines($file) 
	{
		$f = fopen($file, 'rb');
	    $lines = 0;

	    while (!feof($f)) {
	        $lines += substr_count(fread($f, 8192), "\n");
	    }

	    fclose($f);

	    return $lines;
	}

}