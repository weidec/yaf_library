<?php
class MyYaf_Rpc_Yar_Concurrent_Client {
	use MyYaf_Rpc_Yar_Util;
	static function call($name, $method, $args = array(), $callback = null) {
		$conf = self::getConf ( $name );
		$sign = md5 ( $method . serialize ( $args ) . $conf->key );
		$param = array (
				'sign' => $sign,
				'args' => $args 
		);
		$opt = array ();
		if (isset ( $conf->timeout )) {
			$opt [YAR_OPT_TIMEOUT] = $conf->timeout;
		}
		return Yar_Concurrent_Client::call ( $conf->url, 'api', array (
				0 => $method,
				1 => $param 
		), $callback, null, $opt );
	}
	static function loop($callback = null, $error_callback = null) {
		return Yar_Concurrent_Client::loop ( $callback, $error_callback );
	}
}