<?php
class Utility_Rpc_Yar_Server {
	private static $conf;
	private $classNames;
	
	/**
	 * initialize configuration of server side
	 * array key is clientId and value is conf,each conf contains key,timeout(second)
	 *
	 * @param array $conf        	
	 */
	static function initConfig(array $conf) {
		foreach ( $conf as $k => $v ) {
			if (! array_key_exists ( 'key', $v )) {
				throw new Yar_Server_Exception ( 'key not found, clientId=' . $k );
			}
		}
		foreach ( $conf as $k => $v ) {
			if (isset ( $v ['timeout'] ) && empty ( $v ['timeout'] )) {
				unset ( $v ['timeout'] );
			}
			$conf [$k] = $v;
		}
		self::$conf = $conf;
	}
	
	/**
	 * get a server instance
	 *
	 * @param array $classNames
	 *        	wildcards
	 * @throws Yar_Server_Exception
	 * @return Yar_Server
	 */
	static function factory(array $classNames) {
		if (! isset ( self::$conf )) {
			throw new Yar_Server_Exception ( 'conf is not set yet' );
		}
		return new Yar_Server ( new self ( $classNames ) );
	}
	
	/**
	 *
	 * @param unknown $clientId        	
	 * @param unknown $classNames        	
	 */
	private function __construct($classNames) {
		$this->classNames = $classNames;
	}
	
	/**
	 * Handle calls from MyYar_Client
	 *
	 * @param unknown $name        	
	 * @param unknown $param        	
	 * @throws Yar_Server_Exceptionn
	 * @return mixed
	 */
	function api($name, $param) {
		// client check
		$clientOk = false;
		if (array_key_exists ( 'id', $param )) {
			if (array_key_exists ( $param ['id'], self::$conf )) {
				$clientOk = true;
				$conf = ( object ) self::$conf [$param ['id']];
			}
		}
		if (! $clientOk) {
			throw new Yar_Server_Exception ( 'client not found' );
		}
		// time and timeout check
		if (! array_key_exists ( 'time', $param ) || 10 != strlen ( $param ['time'] )) {
			throw new Yar_Server_Exception ( 'time not specified' );
		}
		if (isset ( $conf->timeout )) {
			$timeoutOk = false;
			$timeoutRemote = $param ['time'];
			if (time () - $timeoutRemote <= $conf->timeout) {
				$timeoutOk = true;
			}
			if (! $timeoutOk) {
				throw new Yar_Server_Exception ( 'request timeout' );
			}
		}
		// sign check
		$signOk = false;
		if (array_key_exists ( 'sign', $param )) {
			$signRemote = $param ['sign'];
			$sign = md5 ( $param ['time'] . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
		}
		if (! $signOk) {
			throw new Yar_Server_Exception ( 'sign error' );
		}
		// args
		if (! array_key_exists ( 'args', $param ) || empty ( $param ['args'] )) {
			$args = array ();
		} else {
			$args = $param ['args'];
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
		), $args );
	}
}