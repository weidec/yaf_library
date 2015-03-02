<?php
class Helper_String {
	/**
	 * Tests if a string is standard 7-bit ASCII or not
	 *
	 * @param string $str        	
	 * @return boolean
	 */
	static function isAscii($str) {
		return (preg_match ( '/[^\x00-\x7F]/S', $str ) == 0);
	}
	
	/**
	 * content between start and end
	 *
	 * @param string $str        	
	 * @param string $start        	
	 * @param string $end        	
	 * @param String $mode
	 *        	g greed
	 *        	ng non-greed
	 * @return string boolean
	 */
	static function substr($str, $start, $end = null, $mode = 'g') {
		if (isset ( $start )) {
			$pos1 = strpos ( $str, $start );
		} else {
			$pos1 = 0;
		}
		if (isset ( $end )) {
			if ($mode == 'g') {
				$pos2 = strrpos ( $str, $end );
			} elseif ($mode == 'ng') {
				$pos2 = strpos ( $str, $end, $pos1 );
			} else {
				user_error ( 'mode is invalid, mode=' . $mode, E_USER_WARNING );
			}
		} else {
			$pos2 = strlen ( $str );
		}
		if (false === $pos1 || false === $pos2 || $pos2 < $pos1) {
			return false;
		}
		$len = strlen ( $start );
		return substr ( $str, $pos1 + $len, $pos2 - $pos1 - $len );
	}
	
	/**
	 * is all utf8 charactor
	 *
	 * @param string $str        	
	 * @return boolean
	 */
	static function isUtf8($str) {
		if (! is_string ( $str )) {
			user_error ( 'parameter is not a string', E_USER_WARNING );
		}
		$strUtf8 = iconv ( 'UTF-8', 'UTF-8//IGNORE', $str );
		if ($strUtf8 != $str) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * get first number in a string
	 *
	 * @param unknown $string        	
	 * @return string|false
	 */
	static function parseInt($string) {
		if (preg_match_all ( '/(\d+)/', $string, $array )) {
			return implode ( '', $array [0] );
		} else {
			return false;
		}
	}
	
	/**
	 * inverse function of nl2br
	 *
	 * @param unknown $str        	
	 * @return mixed
	 */
	static function br2nl($str) {
		return preg_replace ( '/\<br\s*\/?\s*>/i', "\n", $str );
	}
	
	/**
	 * encode / and + in url twice because nginx auto decode / and +
	 *
	 * @param unknown $str        	
	 * @return mixed
	 */
	static function urlEncodeNginx($str) {
		$str = urlencode ( $str );
		$str = str_replace ( array (
				'%2F',
				'%2f' 
		), '%252F', $str );
		$str = str_replace ( array (
				'%2B',
				'%2B' 
		), '%252B', $str );
		return $str;
	}
	
	/**
	 * decode html no matter string is upper case or lower case
	 *
	 * @param unknown $str        	
	 * @param unknown $flags        	
	 * @return string
	 */
	static function htmlEntityDecode($str, $flags = null, $encoding = null) {
		$str = self::u2l ( $str );
		if (! isset ( $flags )) {
			$flags = ENT_COMPAT | ENT_HTML401;
		}
		if (! isset ( $encoding )) {
			$encoding = 'UTF-8';
		}
		return html_entity_decode ( $str, $flags, $encoding );
	}
	
	/**
	 * replace upper letter to lower letter, only for a-z
	 *
	 * @param unknown $str        	
	 * @return string
	 */
	static function u2l($str) {
		return preg_replace_callback ( '/&[a-z]+?;/i', function ($match) {
			return strtolower ( $match [0] );
		}, $str );
	}
	
	/**
	 * recursive trim
	 *
	 * @param mixed $arr        	
	 * @param string $charlist        	
	 * @return mixed
	 */
	static function trim($arr, $charlist = null) {
		if (is_array ( $arr ) and ! empty ( $arr )) {
			foreach ( $arr as &$v )
				$v = self::trimR ( $v, $charlist );
		} elseif (is_string ( $arr ))
			$arr = trim ( $arr, $charlist );
		return $arr;
	}
}