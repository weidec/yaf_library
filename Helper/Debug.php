<?php
class Helper_Debug {
	/**
	 * error to exception
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
					self::exceptionString($exception);
				}
			}
		} );
	}

	/**
	 * error severity to string
	 *
	 * @param unknown $severity
	 * @return string
	 */
	private static function errorSeverityString($severity) {
		switch ($severity) {
			case E_ERROR : // 1 //
				return 'E_ERROR';
			case E_WARNING : // 2 //
				return 'E_WARNING';
			case E_PARSE : // 4 //
				return 'E_PARSE';
			case E_NOTICE : // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR : // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING : // 32 //
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR : // 64 //
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING : // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR : // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING : // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE : // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT : // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR : // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED : // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED : // 16384 //
				return 'E_USER_DEPRECATED';
		}
	}

	/**
	 * output severity if exception is ErrorException
	 * @param unknown $exception
	 */
	private static function exceptionString($exception){
		if (PHP_SAPI != 'cli') {
			echo '<pre>';
		}
		$pre = '';
		if ($exception instanceof ErrorException) {
			$pre = self::errorSeverityString ( $exception->getSeverity () ) . ': ';
		}
		echo $pre . $exception->__toString();
	}

	/**
	 * deal with exception
	 */
	static function catchException($exception) {
		if (ini_get ( 'display_errors' )) {
			self::exceptionString($exception);
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