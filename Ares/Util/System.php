<?php

namespace Ares\Util;

use ErrorException;
use Ares;

class System {
	
	/**
	 * <pre>
	 * cli模式下非阻塞方式检测相同的PHP进程是否正在运行,相同的PHP进程指route相同的PHP进程。
	 * 如果锁文件被其他程序锁定也会认为程序正在运行
	 * </pre>
	 *
	 * @throws ErrorException
	 * @return boolean
	 */
	static function syncProcess($pidFile) {
		if (PHP_SAPI != 'cli') {
			throw new ErrorException ( __METHOD__ . ' can only run in php cli' );
		}
		if (PHP_OS != 'Linux') {
			throw new ErrorException ( __METHOD__ . ' can only run in Linux' );
		}
		$app = Ares::app ();
		$route = $app->getRoute ();
		if (! is_array ( $route ) || empty ( $route )) {
			throw new ErrorException ( 'route is invalid' );
		}
		$key = md5 ( $app->getRoute ( null, true ) );
		
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
			// 清理
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
	 * 如果正在运行或者发生未知错误返回true，如果没有运行返回false
	 *
	 * @param mixed $pid        	
	 */
	static function isProcessRunning($pid) {
		if (PHP_OS == 'Linux') {
			if (is_numeric ( $pid ) && $pid > 0) {
				$output = array ();
				$line = exec ( "ps -o pid --no-headers -p $pid", $output );
				// 返回值有空格
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
						// 到这一步的话应该是出什么问题了
						$output = implode ( $n, $output );
					}
				}
			} else {
				return false;
			}
		} else {
			throw new ErrorException ( 'is_process_running() can only run in linux' );
		}
	}
	
	/**
	 * 测试运行的进程是否是php进程，只能在php命令行中运行
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
				// 如果权限不够需要用这种方法补充判断一下
				if (false == $r) {
					if (isset ( $_SERVER ['_'] ) && '/php' == substr ( $_SERVER ['_'], - 4 )) {
						$r = true;
					}
				}
			}
			return $r;
		} else {
			throw new ErrorException ( __METHOD__ . " can only be run in linux cli" );
		}
	}
}