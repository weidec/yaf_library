<?php

/**
 * 日志文件必须预先定义才能写，便于管理，一个名字对应一个绝对路径的日志文件
 *
 * @author Ares
 *        
 */
class Ares_FileLog {
	private $file;
	/**
	 * 文件路径
	 *
	 * @param string $file        	
	 */
	function __construct($file) {
		$this->file = $file;
		$dir = dirname ( $file );
		if (! file_exists ( $dir )) {
			mkdir ( $dir );
		}
	}
	
	/**
	 * 读取日志的最后n行
	 *
	 * @param number $n        	
	 * @param string $ord        	
	 */
	function tail($n = 100, $ord = 'desc') {
		$str = Ares_File::tail ( $this->file, $n );
		$lines = explode ( "\n", $str );
		if ($ord == 'desc') {
			$lines = array_reverse ( $lines );
		}
		return $lines;
	}
	
	/**
	 * 清空日志
	 */
	function clear() {
		return file_put_contents ( $this->file, '', LOCK_EX );
	}
	
	/**
	 * 删除日志文件
	 *
	 * @return boolean
	 */
	function unlink() {
		return unlink ( $this->file );
	}
	
	/**
	 * 记录日志
	 *
	 * @param unknown $msg        	
	 */
	function append($msg) {
		return file_put_contents ( $this->file, '[' . date ( 'Y-m-d H:i:s' ) . ' ' . ini_get ( 'date.timezone' ) . '] ' . $msg . "\n", FILE_APPEND | LOCK_EX );
	}
}