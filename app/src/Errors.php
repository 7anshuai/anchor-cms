<?php

class Errors {

	protected $handlers;

	public function __construct() {
		$this->handlers = new \SplObjectStorage;
	}

	public function register() {
		set_error_handler([$this, 'native']);
		set_exception_handler([$this, 'exception']);
		register_shutdown_function([$this, 'shutdown']);
	}

	public function handler($callback) {
		$this->handlers->attach($callback);
	}

	public function exception($exception) {
		foreach($this->handlers as $handler) {
			$handler($exception);
		}

		$this->halt();
	}

	public function halt() {
		exit(1);
	}

	public function native($code, $message, $file, $line) {
		if($code & error_reporting()) {
			$this->exception(new \ErrorException($message, $code, 0, $file, $line));
		}

		return false;
	}

	public function shutdown() {
		if($error = error_get_last()) {
			extract($error);

			$this->native($type, $message, $file, $line);
		}
	}

}
