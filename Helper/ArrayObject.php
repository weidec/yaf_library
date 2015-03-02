<?php
class Helper_ArrayObject {
	/**
	 * merge object or array
	 *
	 * @param unknown $obj1        	
	 * @param unknown $obj2        	
	 * @return mixed
	 */
	static function merge($obj1, $obj2) {
		if (is_object ( $obj1 )) {
			foreach ( $obj2 as $k => $v ) {
				$obj1->$k = $v;
			}
		} elseif (is_array ( $obj1 )) {
			foreach ( $obj2 as $k => $v ) {
				$obj1 [$k] = $v;
			}
		} else {
			user_error ( 'first parameter\'s type is invalid, type is ' . gettype ( $obj1 ), E_USER_WARNING );
			return false;
		}
		return $obj1;
	}
	
	/**
	 * recursive implode
	 *
	 * @param unknown $glue        	
	 * @param unknown $pieces        	
	 * @return string
	 */
	static function implode($glue, $pieces) {
		if (! is_array ( $pieces )) {
			if (is_object ( $pieces )) {
				$pieces = ( array ) $pieces;
			} else {
				user_error ( 'type error, must be array or object, type=' . gettype ( $pieces ), E_USER_WARNING );
				return false;
			}
		}
		foreach ( $pieces as $k => $v )
			if (is_array ( $v ))
				$pieces [$k] = call_user_func ( __METHOD__, $v );
		return trim ( implode ( $glue, $pieces ), $glue );
	}
	
	/**
	 * recursive values
	 *
	 * @param unknown $arr        	
	 * @return mixed
	 */
	static function values($arr) {
		$r = array ();
		if (is_array ( $arr ) or is_object ( $arr )) {
			$t = array_values ( ( array ) $arr );
			foreach ( $t as $v ) {
				if (is_array ( $v ) or is_object ( $v )) {
					$r = array_merge ( $r, call_user_func ( __METHOD__, $v ) );
				} else {
					$r [] = $v;
				}
			}
		}
		return $r;
	}
	
	/**
	 * second parameter can be scalar
	 *
	 * @param array $arr        	
	 * @param unknown $val        	
	 */
	static function arrayCombine(array $arr, $var) {
		if (is_array ( $var )) {
			return array_combine ( $arr, $var );
		}
		if (is_int ( $var ) || is_float ( $var ) || is_string ( $var ) || is_bool ( $var )) {
			return array_combine ( $arr, array_pad ( array (), count ( $arr ), $var ) );
		} else {
			user_error ( 'second parameter is invalid', E_USER_WARNING );
		}
	}
	
	/**
	 * get a value and remove it
	 *
	 * @param array $array        	
	 * @param unknown $key        	
	 * @return mixed
	 */
	static function remove($array, $key) {
		if (is_array ( $array )) {
			$v = $array [$key];
			unset ( $array [$key] );
		} elseif (is_object ( $array )) {
			$v = $array->$key;
			unset ( $array->key );
		}
		return $v;
	}
	
	/**
	 * recursive search
	 *
	 * @param unknown $needle        	
	 * @param unknown $haystack        	
	 * @param string $strict        	
	 * @return false or array
	 */
	static function scan($needle, $haystack, $strict = false) {
		if (false === is_array ( $haystack )) {
			if (is_object ( $haystack )) {
				$haystack = ( array ) $haystack;
			} else {
				user_error ( 'must be array or object', E_USER_WARNING );
				return false;
			}
		}
		$key = array ();
		$r = array_search ( $needle, $haystack, $strict );
		if (false === $r) {
			foreach ( $haystack as $k => $v ) {
				if (is_array ( $v )) {
					$t = self::scan ( $needle, $v, $strict, true );
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
	 * insert before a key
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
	 * return values but not key
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
	 * return values use given keys
	 *
	 * @param unknown $arr        	
	 * @param unknown $keys        	
	 * @return mixed
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
	static function inArray($needle, $haystack, $strict = false) {
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
	 * make second dimension value to array key,if dimension value not exists it will be unset.
	 *
	 * @param unknown $list        	
	 * @param unknown $k        	
	 * @return mixed
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
			user_error ( 'parameter 1 is not an array', E_USER_ERROR );
			return false;
		}
	}
	
	/**
	 *
	 * @param object $obj        	
	 * @return object $obj
	 */
	static function deepClone($obj) {
		$objClone = clone $obj;
		foreach ( $objClone as $k => $v ) {
			if (is_object ( $v )) {
				$objClone->$k = call_user_func ( __METHOD__, $v );
			}
		}
		return $objClone;
	}
}