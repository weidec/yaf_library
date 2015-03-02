<?php

namespace Ares;

use ErrorException;

/**
 * 数组和对象的功能封装
 */
class ArrayObject {
	
	/**
	 * 合并两个对象
	 *
	 * @param unknown $obj1        	
	 * @param unknown $obj2        	
	 */
	static function objectMerge($obj1, $obj2) {
		foreach ( $obj2 as $k => $v ) {
			$obj1->$k = $v;
		}
	}
	
	/**
	 * recursive array_values()
	 *
	 * @param unknown $arr        	
	 * @return mixed
	 */
	static function arrayValuesR($arr) {
		$r = array ();
		if (is_array ( $arr ) or is_object ( $arr )) {
			$t = array_values ( ( array ) $arr );
			foreach ( $t as $v ) {
				if (is_array ( $v ) or is_object ( $v )) {
					$r = array_merge ( $r, self::arrayValuesR ( $v ) );
				} else {
					$r [] = $v;
				}
			}
		}
		return $r;
	}
	
	/**
	 * 是array_combine()的扩展，第二个参数可以是标量
	 *
	 * @param array $arr        	
	 * @param unknown $val
	 *        	标量，或和$arr元素数量相同的数组
	 */
	static function arrayCombine(array $arr, $var) {
		if (is_array ( $var )) {
			return array_combine ( $arr, $var );
		}
		if (is_int ( $var ) || is_float ( $var ) || is_string ( $var ) || is_bool ( $var )) {
			return array_combine ( $arr, array_pad ( array (), count ( $arr ), $var ) );
		} else {
			throw new ErrorException ( 'second parameter is invalid' );
		}
	}
	
	/**
	 * 从数组中移出一项
	 *
	 * @param unknown $array        	
	 * @param unknown $key        	
	 * @return unknown
	 */
	static function arrayRemove($array, $key) {
		$v = $array [$key];
		unset ( $array [$key] );
		return $v;
	}
	
	/**
	 * recursive array_search()
	 *
	 * @param unknown $needle        	
	 * @param unknown $haystack        	
	 * @param string $strict        	
	 * @return 没找到返回false,否则返回键
	 */
	static function arraySearchR($needle, $haystack, $strict = false) {
		if (false === is_array ( $haystack ))
			return false;
		$key = array ();
		$r = array_search ( $needle, $haystack, $strict );
		if (false === $r) {
			foreach ( $haystack as $k => $v ) {
				if (is_array ( $v )) {
					$t = self::arraySearchR ( $needle, $v, $strict, true );
					if (false !== $t) {
						$key = array_merge ( $key, array (
								$k 
						), $t );
						break;
					}
				}
			}
		} else {
			$key [] = $r;
		}
		if (empty ( $key ))
			return false;
		else
			return $key;
	}
	
	/**
	 * 在数组的某个键前边插入一项
	 *
	 * @param unknown $array        	
	 * @param unknown $key        	
	 * @param unknown $row        	
	 * @return array
	 */
	static function arrayInsert($array, $key, $row) {
		$up = array ();
		if (is_array ( $array ) && is_array ( $row ) && array_key_exists ( $key, $array )) {
			foreach ( $array as $k => $v ) {
				if ($key === $k) {
					$up [key ( $row )] = $row [key ( $row )];
				}
				$up [$k] = array_shift ( $array );
			}
		}
		return $up;
	}
	
	/**
	 * 直接返回值而不是键，适用于不需要键的情况
	 *
	 * @param unknown $arr        	
	 * @param string $num        	
	 * @return mixed
	 */
	static function arrayRandValue($arr, $num = null) {
		if (! isset ( $num ))
			$num = 1;
		if (is_array ( $arr ) && is_numeric ( $num )) {
			$key = array_rand ( $arr, $num );
			if ($num == 1)
				$single = true;
			else
				$single = false;
			if ($single)
				$key = array (
						$key 
				);
			$r = array ();
			foreach ( $key as $v ) {
				$r [] = $arr [$v];
			}
			if ($single)
				$r = array_pop ( $r );
			return $r;
		}
	}
	
	/**
	 * 给定一批键返回对应的值
	 *
	 * @param unknown $arr        	
	 * @param unknown $keys        	
	 * @return boolean Ambigous unknown>
	 */
	static function arrayAt($arr, $keys) {
		if (! is_array ( $arr ))
			return false;
		$r = false;
		foreach ( $keys as $k ) {
			if (array_key_exists ( $k, $arr ))
				$r = $arr = $arr [$k];
		}
		return $r;
	}
	
	/**
	 * recursive in_array()
	 *
	 * @param unknown $needle        	
	 * @param unknown $haystack        	
	 * @param string $strict        	
	 * @return boolean
	 */
	static function inArrayR($needle, $haystack, $strict = false) {
		if (is_array ( $haystack )) {
			if (in_array ( $needle, $haystack, $strict ))
				return true;
			else {
				foreach ( $haystack as $v ) {
					if (self::inArrayR ( $needle, $v, $strict ))
						return true;
				}
				return false;
			}
		} else
			return false;
	}
	
	/**
	 * 把二维数组中第二维的某个字段作为第一维的键，如果不存在这个键这一条记录就干掉了
	 *
	 * @param unknown $list        	
	 * @param unknown $k        	
	 * @throws Exception
	 * @return array
	 */
	static function arrayValue2key($list, $k) {
		if (is_array ( $list )) {
			$r = array ();
			foreach ( $list as $v ) {
				if (is_array ( $v )) {
					if (isset ( $v [$k] )) {
						$r [$v [$k]] = $v;
					}
				} else if (is_object ( $v )) {
					if (isset ( $v->$k )) {
						$r [$v->$k] = $v;
					}
				}
			}
			return $r;
		} else {
			throw new ErrorException ( 'parameter 1 is not an array' );
		}
	}
	
	/**
	 * php默认是浅克隆，函数实现深克隆
	 *
	 * @param object $obj
	 * @return object $obj
	 */
	function deepCloneR($obj) {
		$objClone = clone $obj;
		foreach ( $objClone as $k => $v ) {
			if (is_object ( $v )) {
				$objClone->$k = call_user_func ( __FUNCTION__, $v );
			}
		}
		return $objClone;
	}
	
	/**
	 * 递归的合并两个对象
	 *
	 * @param unknown $obj1
	 * @param unknown $obj2
	 */
	function objectMergeR($obj1, $obj2) {
		foreach ( $obj2 as $k => $v ) {
			if (is_object ( $v ) && isset ( $obj1->$k ) && is_object ( $obj1->$k )) {
				call_user_func ( __FUNCTION__, $obj1->$k, $v );
			} else {
				$obj1->$k = $v;
			}
		}
	}
}