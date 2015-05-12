<?php
class Helper_System {

	/**
	 * synchronized by file lock
	 *
	 * @param unknown $lockFile
	 * @param unknown $callback
	 * @param unknown $param_arr
	 */
	static function synchronized($lockFile, $callback, $param_arr = array()) {
		if (PHP_OS != 'Linux') {
			user_error ( __METHOD__ . ' can only run in Linux', E_USER_WARNING );
			return false;
		}
		clearstatcache ( true, $lockFile );
		$f = fopen ( $lockFile, 'c+' );
		if (flock ( $f, LOCK_EX | LOCK_NB )) {
			call_user_func_array ( $callback, $param_arr );
		}
		flock ( $f, LOCK_UN );
		fclose ( $f );
	}

	/**
	 *
	 * @param unknown $pid
	 * @return boolean|null on error
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
						// abnormal
						$output = implode ( "\n", $output );
						user_error ( 'something unexpected hadppened, output=' . $output, E_USER_WARNING );
					}
				}
			} else {
				return false;
			}
		} else {
			user_error ( 'is_process_running() can only run in linux', E_USER_ERROR );
		}
	}
}