<?php
class Helper_System {
	
	/**
	 * sync php process by flock
	 *
	 * @return boolean
	 */
	static function syncProcess($pidFile, $processIdentifier) {
		if (PHP_SAPI != 'cli') {
			user_error ( __METHOD__ . ' can only run in php cli', E_USER_WARNING );
			return false;
		}
		if (PHP_OS != 'Linux') {
			user_error ( __METHOD__ . ' can only run in Linux', E_USER_WARNING );
			return false;
		}
		if (! is_array ( $processIdentifier ) || empty ( $processIdentifier )) {
			user_error ( 'process identifier is invalid', E_USER_WARNING );
			return false;
		}
		$key = md5 ( $processIdentifier );
		$running = true;
		clearstatcache ( true, $pidFile );
		if (! file_exists ( $pidFile ))
			file_put_contents ( $pidFile, '', LOCK_EX );
		$f = fopen ( $pidFile, 'r+' );
		if (flock ( $f, LOCK_EX ^ LOCK_NB )) {
			$pidList = unserialize ( fgets ( $f ) );
			if (! is_array ( $pidList ) && empty ( $pidList )) {
				$pidList = array ();
			}
			$pid = isset ( $pidList [$key] ) ? $pidList [$key] : null;
			if (! self::isProcessRunning ( $pid ) || ! self::isPhpProcess ( $pid )) {
				$running = false;
			}
			// clean up
			$gcProb = 0.1;
			if (rand ( 1, 100 ) <= $gcProb * 100) {
				foreach ( $pidList as $k => $v ) {
					if (! self::isProcessRunning ( $v ) || ! self::isPhpProcess ( $v )) {
						unset ( $pidList [$key] );
					}
				}
			}
			if (! $running) {
				fseek ( $f, 0 );
				ftruncate ( $f, 0 );
				$pidList [$key] = getmypid ();
				fwrite ( $f, serialize ( $pidList ) );
			}
		}
		flock ( $f, LOCK_UN );
		fclose ( $f );
		return $running;
	}
	
	/**
	 *
	 * @param mixed $pid        	
	 */
	static function isProcessRunning($pid) {
		if (PHP_OS == 'Linux') {
			if (is_numeric ( $pid ) && $pid > 0) {
				$output = array ();
				$line = exec ( "ps -o pid --no-headers -p $pid", $output );
				$line = trim ( $line );
				if ($line == $pid) {
					return true;
				} else {
					if (empty ( $output )) {
						return false;
					} else {
						if (PHP_SAPI == 'cli') {
							$n = "\n";
						} else {
							$n = "<br>";
						}
						// abnormal
						$output = implode ( $n, $output );
						user_error ( 'something wrong happend, msg=' . $output, E_USER_WARNING );
					}
				}
			} else {
				return false;
			}
		} else {
			user_error ( 'is_process_running() can only run in linux', E_USER_ERROR );
		}
	}
	
	/**
	 *
	 * @param int $pid        	
	 * @return boolean
	 */
	static function isPhpProcess($pid) {
		if (PHP_OS == 'Linux' && PHP_SAPI == 'cli') {
			$r = false;
			if (is_numeric ( $pid ) && $pid > 0) {
				$lines = array ();
				exec ( "/proc/$pid/exe -v", $lines );
				if (count ( $lines ) == 3) {
					if ('PHP' == substr ( $lines [0], 0, 3 ) && 'Copyright' == substr ( $lines [1], 0, 9 ) && 'Zend' == substr ( $lines [2], 0, 4 )) {
						$r = true;
					}
				}
				// additional check method if no privilege
				if (false == $r) {
					if (isset ( $_SERVER ['_'] ) && '/php' == substr ( $_SERVER ['_'], - 4 )) {
						$r = true;
					}
				}
			}
			return $r;
		} else {
			user_error ( 'Must be used in linux and cli mode', E_USER_ERROR );
		}
	}
}