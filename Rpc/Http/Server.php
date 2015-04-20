<?php
class Rpc_Http_Server {
	private $conf;
	
	/**
	 *
	 * @param array $conf
	 *        	id,key,classname,timeout
	 * @throws Rpc_Http_Server_Exception
	 */
	function __construct($conf) {
		$this->conf = array ();
		foreach ( $conf as $v ) {
			$this->conf [$v ['key']] = $v;
		}
	}
	
	/**
	 *
	 * @throws Rpc_Http_Server_Exception
	 */
	function handle() {
		$res = array (
				'errorNo' => 0,
				'errorMsg' => null,
				'res' => null 
		);
		try {
			$param = $this->getRequest ();
			// method check
			if (false === strpos ( $param ['_method'], '.' )) {
				throw new Rpc_Http_Server_Exception ( 'method is invalid, method=' . $param ['_method'] );
			}
			// client check
			$clientOk = false;
			if (array_key_exists ( $param ['_id'], $this->conf )) {
				$clientOk = true;
				$conf = ( object ) $this->conf [$param ['_id']];
			}
			if (! $clientOk) {
				throw new Rpc_Http_Server_Exception ( 'client not found' );
			}
			// timeout check
			if (isset ( $conf->timeout )) {
				$timeoutOk = false;
				$timeoutRemote = $param ['_time'];
				if (time () - $timeoutRemote <= $conf->timeout) {
					$timeoutOk = true;
				}
				if (! $timeoutOk) {
					throw new Rpc_Http_Server_Exception ( 'request timeout' );
				}
			}
			// sign check
			$signOk = false;
			$signRemote = $param ['_sign'];
			$sign = md5 ( $param ['_time'] . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
			if (! $signOk) {
				throw new Rpc_Http_Server_Exception ( 'sign error' );
			}
			$args = $param ['_args'];
			// class check
			list ( $className, $method ) = explode ( '.', $param ['_method'] );
			$classnameOk = false;
			foreach ( $conf->classname as $v ) {
				if (fnmatch ( $v, $className )) {
					$classnameOk = true;
					break;
				}
			}
			if (! $classnameOk) {
				throw new Rpc_Http_Server_Exception ( 'class is not allowed, classname=' . $className );
			}
			$class = new $className ();
			$cb = array (
					$class,
					$method 
			);
			if (! is_callable ( $cb )) {
				throw new Rpc_Http_Server_Exception ( 'api method not callable, method=' . $param ['_method'] );
			}
			$res ['res'] = call_user_func_array ( $cb, $args );
		} catch ( Exception $e ) {
			if (ini_get ( 'log_errors' )) {
				error_log ( $e->__toString () . "\n" );
			}
			if ($e instanceof Rpc_Http_Server_Exception) {
				$res ['errorMsg'] = 'http server serror: ' . $e->getMessage ();
			} else {
				$res ['errorMsg'] = $e->__toString ();
			}
			$res ['errorNo'] = $e->getCode ();
		}
		echo json_encode ( $res );
	}
	
	/**
	 * Get params sent from client
	 */
	private function getRequest() {
		$req = $_GET;
		$keys = array (
				'_id',
				'_sign',
				'_method',
				'_time' 
		);
		foreach ( $keys as $v ) {
			if (empty ( $req [$v] )) {
				throw new Rpc_Http_Server_Exception ( 'param ' . $v . ' not found' );
			}
		}
		return $req;
	}
}