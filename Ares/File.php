<?php
class Ares_File {
	/**
	 * 利用堆栈清空目录
	 *
	 * @param string $dir
	 *        	目录名,最好是绝对路径,否则相对路径搞错了后果自负
	 * @return boolean
	 */
	static function dirFlush($dir) {
		clearstatcache ();
		// 格式化路径并清除结尾的斜线
		$dir = realpath ( $dir );
		if (file_exists ( $dir ) && is_writable ( $dir )) {
			// glob对点开头的文件处理有些问题,所以用scandir
			$files = scandir ( $dir );
			foreach ( $files as $k => $v ) {
				if ($v == '.' || $v == '..')
					unset ( $files [$k] );
				else
					$files [$k] = $dir . DIRECTORY_SEPARATOR . $v;
			}
			while ( ! empty ( $files ) ) {
				$file = array_pop ( $files );
				if (is_file ( $file )) {
					if (! unlink ( $file ))
						return false;
				} else {
					// 子目录为空就删除,否则进栈
					$dirFiles = scandir ( $file );
					if (count ( $dirFiles ) == 2) {
						if (! rmdir ( $file ))
							return false;
					} else {
						foreach ( $dirFiles as $k => $v ) {
							if ($v == '.' || $v == '..')
								unset ( $dirFiles [$k] );
							else
								$dirFiles [$k] = $file . DIRECTORY_SEPARATOR . $v;
						}
						$files = array_merge ( $files, array (
								$file 
						), $dirFiles );
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * 在某个目录下循环创建子目录
	 *
	 * @param unknown $parent
	 *        	父路径
	 * @param unknown $dir
	 *        	相对路径
	 * @param number $mode
	 *        	模式
	 */
	static function mkDirSub($parent, $subdir, $mode = 0755) {
		if (! is_dir ( $parent )) {
			throw new ErrorException ( 'dir ' . $parent . ' doesn\'t exist' );
		}
		$parent = realpath ( $parent );
		$subdir = self::pathClean ( trim ( $subdir, ' /\\' ) );
		if (! empty ( $subdir )) {
			$subdirs = explode ( '/', $subdir );
			foreach ( $subdirs as $dir ) {
				$parent .= '/' . $dir;
				if (! file_exists ( $parent )) {
					mkdir ( $parent, $mode );
				}
			}
		}
	}
	
	/**
	 * 规范化一个相对路径或绝对路径，所有分隔符用/替换，计算路径中的.和..
	 *
	 * @param string $path        	
	 * @return string
	 */
	static function pathClean($path) {
		$path = trim ( $path );
		$path = str_replace ( '\\\\', '/', $path );
		$path = str_replace ( '\\', '/', $path );
		$path = str_replace ( '//', '/', $path );
		$arr = explode ( '/', $path );
		foreach ( $arr as $k => $v ) {
			if (empty ( $v ) || $v == '.') {
				unset ( $arr [$k] );
			} elseif ($v == '..') {
				unset ( $arr [$k] );
				if ($k > 0) {
					unset ( $arr [$k - 1] );
				}
			}
		}
		$file = trim ( implode ( '/', $arr ), '/' );
		return $file;
	}
	
	/**
	 * 取文件最后$n行
	 *
	 * @param string $filename
	 *        	文件路径
	 * @param int $n
	 *        	最后几行
	 * @return mixed false表示有错误，成功则返回字符串
	 */
	static function tail($file, $n) {
		if (! $fp = fopen ( $file, 'r' )) {
			throw new Exception ( 'failed open file, file=' . $file );
		}
		$pos = - 2;
		$eof = "";
		$str = "";
		while ( $n > 0 ) {
			while ( $eof != "\n" ) {
				if (! fseek ( $fp, $pos, SEEK_END )) {
					$eof = fgetc ( $fp );
					$pos --;
				} else {
					break;
				}
			}
			$str .= fgets ( $fp );
			$eof = "";
			$n --;
		}
		return $str;
	}
	
	/**
	 * 递归扫描目录
	 *
	 * @param string $dir
	 *        	被扫描的目录
	 * @param enum $mode
	 *        	文件类型，file，dir，null表示所有类型
	 * @param int $depth
	 *        	递归的深度,null是无限递归
	 * @param array $ignore
	 *        	通配符匹配
	 * @param number $order
	 *        	默认的排序顺序是按字母升序排列，如果设为 1，则按字母降序排列。
	 * @param string $context
	 *        	参见手册scandir
	 * @return array
	 */
	static function scandirR($dir, $mode = null, $depth = null, $ignore = array(), $order = 0, $context = null) {
		static $modes = array (
				'file',
				'dir',
				null 
		);
		$r = array ();
		if (! in_array ( $mode, $modes )) {
			throw new ErrorException ( 'mode is invalid' );
		}
		if (is_numeric ( $depth ) && -- $depth < 0)
			return $r;
		$dir = rtrim ( $dir, '/' );
		if (! is_dir ( $dir )) {
			return $r;
		}
		if (is_resource ( $context )) {
			$list = scandir ( $dir, $order, $context );
		} else {
			$list = scandir ( $dir, $order );
		}
		if (is_array ( $list ) and ! empty ( $list )) {
			foreach ( $list as $v ) {
				if ($v == '.' || $v == '..') {
					continue;
				} else {
					if (! empty ( $ignore )) {
						foreach ( $ignore as $v1 ) {
							if (fnmatch ( $v1, $v )) {
								continue 2;
							}
						}
					}
					if (is_file ( $dir . '/' . $v ) and ($mode == 'file' or $mode == null)) {
						$r [] = $v;
					} elseif (is_dir ( $dir . '/' . $v )) {
						if ($mode == 'dir' or $mode == null)
							$r [] = $v;
						$t = self::scandirR ( $dir . '/' . $v, $mode, $depth, $ignore, $order, $context );
						if (! empty ( $t )) {
							foreach ( $t as $k1 => $v1 ) {
								$t [$k1] = $v . '/' . $v1;
							}
						}
						$r = array_merge ( $r, $t );
					}
				}
			}
		} else {
			$r = $list;
		}
		return $r;
	}
}