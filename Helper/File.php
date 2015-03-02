<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class Helper_File {
	/**
	 * flush dir use stack
	 *
	 * @param string $dir
	 *        	absolute path is highly recommanded
	 * @return boolean
	 */
	static function dirFlush($dir) {
		clearstatcache ();
		$dir = realpath ( $dir );
		if (file_exists ( $dir ) && is_writable ( $dir )) {
			// glob has someproblems with dot started filename,so use scandir
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
					// do delete if subdir is empty,otherwise push to stack
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
	 * make subdirs
	 *
	 * @param unknown $parent        	
	 * @param unknown $dir        	
	 * @param number $mode        	
	 */
	static function mkSubdir($parent, $subdir, $mode = 0755) {
		if (! is_dir ( $parent )) {
			user_error ( 'dir ' . $parent . ' doesn\'t exist', E_USER_WARNING );
			return false;
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
	 * Clean a messy path.All seperator replaced with '/'.
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
	 * get last n line of the file
	 *
	 * @param string $filename        	
	 * @param int $n        	
	 * @return mixed false or lines
	 */
	static function tail($file, $n) {
		if (! $fp = fopen ( $file, 'r' )) {
			user_error ( 'failed open file, file=' . $file, E_USER_WARNING );
			return false;
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
	 * scan dir recersively
	 *
	 * @param string $dir        	
	 * @param enum $mode
	 *        	file
	 *        	dir
	 *        	null:file and dir
	 * @param int $depth
	 *        	null is infinite
	 * @param array $ignore
	 *        	wildcard
	 * @param number $order
	 *        	0 order by letter asc
	 *        	1 order by letter desc
	 * @param string $context
	 *        	see php menual on scandir
	 * @return array
	 */
	static function scandir($dir, $mode = null, $depth = null, $ignore = array(), $order = 0, $context = null) {
		static $modes = array (
				'file',
				'dir',
				null 
		);
		$r = array ();
		if (! in_array ( $mode, $modes )) {
			user_error ( 'mode is invalid', E_USER_WARNING );
			return false;
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