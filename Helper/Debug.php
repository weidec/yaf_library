<?php
class Helper_Debug {
	/**
	 * deal with exception
	 */
	static function catchException($exception) {
		if (ini_get ( 'display_errors' )) {
			if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
				echo '<pre>';
			}
			echo $exception;
		}
		if (ini_get ( 'log_errors' )) {
			error_log ( $exception->__toString () . "\n" );
		}
	}

	/**
	 *
	 * @throws ErrorException
	 */
	static function error2exception() {
		set_error_handler ( function ($errno, $errstr, $errfile, $errline) {
			// errors supressed by @ will cause error_reporting() always return 0
			$r = error_reporting ();
			if ($r & $errno) {
				$exception = new ErrorException ( $errstr, 0, $errno, $errfile, $errline );
				if ($errno == E_USER_ERROR || $errno == E_RECOVERABLE_ERROR) {
					throw $exception;
				}
				if (ini_get ( 'log_errors' )) {
					error_log ( $exception->__toString () . "\n" );
				}
				if (ini_get ( 'display_errors' )) {
					if (PHP_SAPI != 'cli') {
						echo '<pre>';
					}
					echo $exception;
				}
			}
		} );
	}

	/**
	 *
	 * @param mixed $true
	 */
	static function memUsage($true = false, $precision = 3) {
		return round ( memory_get_usage ( $true ) / 1024 / 1024, $precision ) . 'MB';
	}

	/**
	 * get execution time in second call
	 *
	 * @param boolean $continue
	 */
	static function runtime($continue = false) {
		static $start = null;
		$time = microtime ( true );
		if (is_null ( $start ))
			$start = $time;
		else {
			echo round ( $time - $start, 9 ) . (PHP_SAPI != 'cli' ? '<br>' : "\n");
			$start = null;
		}
		if (! $continue && is_null ( $start ))
			exit ();
	}
}