<?php
class MyYar_Server {
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
		if (array_key_exists ( 'clientId', $param )) {
			if (array_key_exists ( $param ['clientId'], self::$conf )) {
				$clientOk = true;
				$conf = ( object ) self::$conf [$param ['clientId']];
			}
		}
		if (! $clientOk) {
			throw new Yar_Server_Exception ( 'client not found' );
		}
		// timeout check
		if (isset ( $conf->timeout )) {
			$timeoutOk = false;
			if (array_key_exists ( 'time', $param )) {
				$timeoutRemote = $param ['time'];
				if (time () - $timeoutRemote <= $conf->timeout) {
					$timeoutOk = true;
				}
			} else {
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
			$args = $param ['args'];
			$sign = md5 ( $name . serialize ( $args ) . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
		}
		if (! $signOk) {
			throw new Yar_Server_Exception ( 'sign error' );
		}
		// class check
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