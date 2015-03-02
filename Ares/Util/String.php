<?php

namespace _lib;

use ErrorException;

class UtilString {
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
	 * 从两头寻找位置找子字符串
	 *
	 * @param unknown $str
	 * @param unknown $start
	 *        	开始字符串
	 * @param unknown $end
	 *        	结束字符串
	 * @param String $mode
	 *        	g|ng greed and nonegreed
	 */
	static function subStr($str, $start, $end, $mode = 'g') {
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
				throw new ErrorException ( 'mode is invalid, mode=' . $mode );
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
	 * 检查字符串是否为空
	 *
	 * @param unknown $str
	 * @return boolean
	 */
	static function isEmpty($str) {
		return ( boolean ) preg_match ( '/^\s*$/i', $str );
	}

	/**
	 * 检查字符串是否全部由合法的utf8字符组成
	 *
	 * @param string $str
	 * @return boolean
	 */
	static function isUtf8($str) {
		if (! is_string ( $str )) {
			throw new ErrorException ( 'parameter is not a string' );
		}
		// 总是报notice级别的错误所以强行压制
		$strUtf8 = iconv ( 'UTF-8', 'UTF-8//IGNORE', $str );
		if ($strUtf8 != $str) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 返回字符串中解析出的第一个数字，没有解析出数字返回false
	 *
	 * @param unknown $string
	 * @return string boolean
	 */
	static function parseInt($string) {
		if (preg_match_all ( '/(\d+)/', $string, $array )) {
			return implode ( '', $array [0] );
		} else {
			return false;
		}
	}

	/**
	 * nl2br的反函数
	 *
	 * @param unknown $str
	 * @return mixed
	 */
	static function br2nl($str) {
		return preg_replace ( '/\<br\s*\/?\s*>/i', "\n", $str );
	}

	/**
	 * <pre>
	 * 对url中的/和+二次编码，nginx
	 * decode，导致一些问题
	 * </pre>
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
	 * 解码html，编码后的字符如果为大写则不能被解码，所以先把大写替换成小写再解码
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
	 * 字符串中的大写字母替换成小写字母，仅限a-z的英文字母
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
	 * recursive
	 * trim()
	 *
	 * @param mixed $arr
	 * @param string $charlist
	 * @return mixed
	 */
	static function trimR($arr, $charlist = null) {
		if (is_array ( $arr ) and ! empty ( $arr )) {
			foreach ( $arr as &$v )
				$v = self::trimR ( $v, $charlist );
		} elseif (is_string ( $arr ))
			$arr = trim ( $arr, $charlist );
		return $arr;
	}

	/**
	 * recursive
	 * implode
	 *
	 * @param unknown $glue
	 * @param unknown $pieces
	 * @return string
	 */
	static function implodeR($glue, $pieces) {
		if (is_array ( $pieces )) {
			foreach ( $pieces as $k => $v )
				if (is_array ( $v ))
					$pieces [$k] = self::implodeR ( $glue, $v );
			return trim ( implode ( $glue, $pieces ), $glue );
		}
	}

	/**
	 * 使用$glue连接数组中某个$key的值，并且在后面和$glue分隔的$other拼接
	 *
	 * @param mixed $list
	 *        	可以是数组列表或对象列表
	 * @param string $key
	 * @param string $glue
	 * @param string $other
	 *        	拼接其他结果
	 * @param string $uniq
	 *        	是否去除重复的值
	 * @return string
	 */
	static function implodeDeep($list, $key, $glue, $other = '', $uniq = true) {
		$arr = array ();
		if (is_array ( $list )) {
			foreach ( $list as $v ) {
				if (array_key_exists ( $key, $v )) {
					if (is_array ( $v )) {
						$arr [] = $v [$key];
					} else if (is_object ( $v )) {
						$arr [] = $v->$key;
					}
				}
			}
		}
		if (! empty ( $other ))
			$arr = array_merge ( $arr, explode ( $glue, $other ) );
		if ($uniq)
			$arr = array_unique ( $arr );
		return implode ( $glue, $arr );
	}
}