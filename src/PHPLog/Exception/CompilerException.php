<?php

namespace PHPLog\Exception;

/**
 * Exception class for pattern compilation errors.
 * @version 1
 * @author Jack Timblin
 */
class CompilerException extends \Exception {
	
	/* the block of pattern code which contains the syntax error. */
	protected $block;

	/* the start point in the block which contains the syntax error. */
	protected $offset;

	/* the provided error message. */
	protected $message;

	/**
	 * @override
	 * Constructor - initializes the exception.
	 * @param string $message the exception message, i.e the error that occured.
	 * @param string $block the block of pattern which contains a syntax error.
	 * @param int $offset the offset of the block where the syntax error occured.
	 */
	public function __construct($message, $block = null, $offset = null) {
		parent::__construct($message);
		$this->message = $message;
		$this->block = $block;
		$this->offset = $offset;
	}

	/**
	 * gets the block which contains the syntax error.
	 * @return string the offending block.
	 */
	public function getBlock() {
		return $this->block;
	}

	/**
	 * gets the offset in the block where the syntax error was found.
	 * @return int the offset in the block where then syntax error was found.
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @override
	 * returns a string representation of this exception.
	 * @return string the string respresentation of this exception.
	 */
	public function __toString() {
		return $this->message . ((isset($this->block)) ? ' - in block: "' . $this->block . '"' : '') . ((isset($this->offset)) ? ' - at offset: "' . $this->offset . '"' : '');
	}

}