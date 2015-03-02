<?php

namespace _lib;

use ErrorException;
use stdClass;

/**
 * 利用PHP的Mcrypt扩展加解密，性能没有测试。
 */
class Crypt {
	const DEFAULT_KEY = '047b0373ec613cae9fd279431a7ce3fa';
	private $key;
	function __construct($key = null) {
		if (is_string ( $key ) && strlen ( $key ) > 0) {
			$this->key = md5 ( $key );
		} else {
			$this->key = self::DEFAULT_KEY;
		}
	}

	/**
	 * 每个实例只保存一个对象
	 *
	 * @param string $key
	 * @return Ambigous <NULL, \frame\lib\Crypt>
	 */
	static function getInstance($key = null) {
		static $objs;
		if (! isset ( $objs )) {
			$objs = new stdClass ();
		}
		$objKey = md5 ( $key );
		$obj = null;
		if (isset ( $objs->objKey )) {
			$obj = $obj->objKey;
		} else {
			$obj = new self ( $key );
			$obj->objKey = $obj;
		}
		return $obj;
	}

	/**
	 * 加密
	 *
	 * @param string $str
	 * @return mixed string or false
	 */
	function encrypt($str) {
		return $this->docrypt ( $str, false );
	}

	/**
	 * 解密
	 *
	 * @param string $str
	 * @return mixed string or false
	 */
	function decrypt($str) {
		return $this->docrypt ( $str, true );
	}

	/**
	 * 加密或解密
	 *
	 * @param string $str
	 * @param boolean $decrypt
	 * @return mixed string or false
	 */
	private function docrypt($str, $decrypt) {
		$r = false;
		if (is_string ( $str )) {
			// Open the cipher
			$td = mcrypt_module_open ( MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '' );
			// Create the IV and determine the keysize length, use MCRYPT_RAND on Windows instead
			$iv = mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
			$ks = mcrypt_enc_get_key_size ( $td );
			// Create key
			$key = substr ( $this->key, 0, $ks );
			// Intialize encryption
			mcrypt_generic_init ( $td, $key, $iv );
			$r = $decrypt ? mdecrypt_generic ( $td, $str ) : mcrypt_generic ( $td, $str ); /* Encrypt data */
			// Terminate encryption handler
			mcrypt_generic_deinit ( $td );
			mcrypt_module_close ( $td );
		} else {
			throw new ErrorException ( 'encrypt string can\'t be empty' );
		}
		return $r;
	}
}