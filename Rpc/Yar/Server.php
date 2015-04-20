<?php
class Rpc_Yar_Server {
	private $conf;
	
	/**
	 *
	 * @param array $conf
	 *        	id,key,url,timeout
	 */
	function __construct($conf) {
		static $called = false;
		if ($called) {
			throw new Yar_Server_Exception ( 'method ' . __FUNCTION__ . ' not allowed' );
		}
		$called = true;
		$this->conf = $conf;
	}
	
	/**
	 *
	 * @return boolean
	 */
	function handle() {
		static $called = false;
		if ($called) {
			throw new Yar_Server_Exception ( 'method ' . __FUNCTION__ . ' not allowed' );
		}
		$called = true;
		$server = new Yar_Server ( new self () );
		return $server->handle ();
	}
	
	/**
	 *
	 * @param string $name        	
	 * @param array $param        	
	 * @throws Yar_Server_Exceptionn
	 * @return mixed
	 */
	function api($name, $param) {
		// client check
		$clientOk = false;
		if (array_key_exists ( '_id', $param )) {
			if (array_key_exists ( $param ['_id'], self::$conf )) {
				$clientOk = true;
				$conf = ( object ) self::$conf [$param ['_id']];
			}
		}
		if (! $clientOk) {
			throw new Yar_Server_Exception ( 'client not found' );
		}
		// time and timeout check
		if (! array_key_exists ( '_time', $param ) || 10 != strlen ( $param ['_time'] )) {
			throw new Yar_Server_Exception ( 'time not specified' );
		}
		if (! empty ( $conf->timeout )) {
			$timeoutOk = false;
			$timeoutRemote = $param ['_time'];
			if (time () - $timeoutRemote <= $conf->timeout) {
				$timeoutOk = true;
			}
			if (! $timeoutOk) {
				throw new Yar_Server_Exception ( 'request timeout' );
			}
		}
		// sign check
		$signOk = false;
		if (array_key_exists ( '_sign', $param )) {
			$signRemote = $param ['_sign'];
			$sign = md5 ( $param ['_time'] . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
		}
		if (! $signOk) {
			throw new Yar_Server_Exception ( 'sign error' );
		}
		// class check
		if (false === strpos ( $name, '.' )) {
			throw new Yar_Server_Exception ( 'method name is invalid, name=' . $name );
		}
		list ( $className, $method ) = explode ( '.', $name );
		$classnameOk = false;
		foreach ( $this->classNames as $v ) {
			if (fnmatch ( $v, $className )) {
				$classnameOk = true;
				break;
			}
		}
		if (! $classnameOk) {
			throw new Yar_Server_Exception ( 'class is not allowed, classname=' . $className );
		}
		$class = new $className ();
		return call_user_func_array ( array (
				$class,
				$method 
		), $param );
	}
}