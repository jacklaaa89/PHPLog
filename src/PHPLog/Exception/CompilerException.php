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

	/*  */
	public function __construct($message, $block = null, $offset = null) {
		parent::__construct($message);
		$this->message = $message;
		$this->block = $block;
		$this->offset = $offset;
	}

	public function getBlock() {
		return $this->block;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function __toString() {
		return $this->message . ((isset($this->block)) ? ' - in block: "' . $this->block . '"' : '') . ((isset($this->offset)) ? ' - at offset: "' . $this->offset . '"' : '');
	}

}