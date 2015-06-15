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