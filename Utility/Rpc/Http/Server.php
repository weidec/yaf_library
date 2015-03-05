<?php
class Utility_Rpc_Http_Server {
	private static $conf;
	private $classNames;
	
	/**
	 * initialize configuration of server side
	 * array key is clientId and value is config,each config contains key,timeout(second)
	 *
	 * @param array $conf        	
	 */
	static function initConfig(array $conf) {
		foreach ( $conf as $k => $v ) {
			if (! array_key_exists ( 'key', $v )) {
				throw new Utility_Rpc_Http_Server_Exception ( 'key not found, clientId=' . $k );
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
	 * @throws Utility_Rpc_Http_Server_Exception
	 * @return Yar_Server
	 */
	static function factory(array $classNames) {
		if (! isset ( self::$conf )) {
			throw new Utility_Rpc_Http_Server_Exception ( 'conf is not set yet' );
		}
		return new self ( $classNames );
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
	 *
	 * @param unknown $name        	
	 * @param unknown $param        	
	 * @throws Utility_Rpc_Http_Server_Exceptionn
	 * @return mixed
	 */
	function handle() {
		$res = array (
				'errorNo' => 0,
				'errorMsg' => null,
				'res' => null 
		);
		try {
			$param = $this->getRequest ();
			// client check
			$clientOk = false;
			if (array_key_exists ( $param ['id'], self::$conf )) {
				$clientOk = true;
				$conf = ( object ) self::$conf [$param ['id']];
			}
			if (! $clientOk) {
				throw new Utility_Rpc_Http_Server_Exception ( 'client not found' );
			}
			// timeout check
			if (isset ( $conf->timeout )) {
				$timeoutOk = false;
				$timeoutRemote = $param ['time'];
				if (time () - $timeoutRemote <= $conf->timeout) {
					$timeoutOk = true;
				}
				if (! $timeoutOk) {
					throw new Utility_Rpc_Http_Server_Exception ( 'request timeout' );
				}
			}
			// sign check
			$signOk = false;
			$signRemote = $param ['sign'];
			$sign = md5 ( $param ['time'] . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
			if (! $signOk) {
				throw new Utility_Rpc_Http_Server_Exception ( 'sign error' );
			}
			$args = $param ['args'];
			// class check
			list ( $className, $method ) = explode ( '.', $param ['method'] );
			$classnameOk = false;
			foreach ( $this->classNames as $v ) {
				if (fnmatch ( $v, $className )) {
					$classnameOk = true;
					break;
				}
			}
			if (! $classnameOk) {
				throw new Utility_Rpc_Http_Server_Exception ( 'class is not allowed, classname=' . $className );
			}
			$class = new $className ();
			$res ['res'] = call_user_func_array ( array (
					$class,
					$method 
			), $args );
		} catch ( Exception $e ) {
			if ($e instanceof Utility_Rpc_Http_Server_Exception) {
				$res ['errorMsg'] = 'http server serror: ' . $e->getMessage ();
			} else {
				$res ['errorMsg'] = $e->getMessage ();
			}
			$res ['errorNo'] = $e->getCode ();
		}
		echo json_encode ( $res );
	}
	
	/**
	 * Get params sent from client.Params contains id,sign,method,args,time.
	 */
	private function getRequest() {
		$req = array ();
		$param = $_GET;
		$keys = array (
				'id',
				'sign',
				'method',
				'time' 
		);
		foreach ( $keys as $v ) {
			if (! array_key_exists ( $v, $param ) && empty ( $param [$v] )) {
				throw new Utility_Rpc_Http_Server_Exception ( 'param ' . $v . ' not found' );
			}
			$req [$v] = $param [$v];
		}
		// method
		if (false === strpos ( $param ['method'], '.' )) {
			throw new Utility_Rpc_Http_Server_Exception ( 'method is invalid, method=' . $param ['method'] );
		}
		// args
		if (! array_key_exists ( 'args', $param ) || empty ( $param ['args'] )) {
			$req ['args'] = array ();
		} else {
			$req ['args'] = json_decode ( $param ['args'], true );
		}
		return $req;
	}
}