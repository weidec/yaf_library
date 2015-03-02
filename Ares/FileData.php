<?php

/**
 * 管理文件形式的数据。
 *
 * @author Ares
 *        
 */
class Ares_FileData {
	private $file;
	/**
	 *
	 * @param string $file
	 *        	文件相对路径
	 */
	function __construct($file) {
		$this->file = $file;
		$dir = dirname ( $this->file );
		if (! file_exists ( $dir )) {
			mkdir ( $dir );
		}
	}
	
	/**
	 * 写文件
	 *
	 * @param mixed $data        	
	 */
	function put($data) {
		$data = serialize ( $data );
		file_put_contents ( $this->file, $data, LOCK_EX );
	}
	
	/**
	 * 获取内容
	 */
	function get() {
		$file = $this->file;
		if (is_file ( $file )) {
			return unserialize ( file_get_contents ( $this->file ) );
		}
	}
	
	/**
	 * 数据文件修改时间
	 */
	function mtime() {
		$file = $this->file;
		if (is_file ( $file )) {
			return filemtime ( $file );
		} else {
			throw new ErrorException ( "file not found, file=" . $file );
		}
	}
	
	/**
	 * 删除数据文件
	 */
	function unlink() {
		$file = $this->file;
		if (is_file ( $file )) {
			return unlink ( $file );
		}
	}
}