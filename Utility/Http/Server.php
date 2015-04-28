<?php
class Utility_Http_Server {
	public $errorCode = 0x0001;
	public $errorStack = false;
	private $conf = array ();
	
	/**
	 *
	 * @param array $conf
	 *        	eache value is an array with id,key,timeout
	 * @throws Rpc_Http_Server_Exception
	 */
	function __construct($conf) {
		foreach ( $conf as $k => $v ) {
			if (empty ( $v ['id'] ) || empty ( $v ['key'] )) {
				throw new Utility_Http_Server_Exception ( "id or key not found in conf[$k]", $this->errorCode );
			}
			if (array_key_exists ( $v ['id'], $this->conf )) {
				throw new Utility_Http_Server_Exception ( 'duplicate client id found, id=' . $v ['id'], $this->errorCode );
			}
			$node = array ();
			$node ['key'] = $v ['key'];
			if (array_key_exists ( 'timeout', $v ) && is_numeric ( $v ['timeout'] )) {
				$node ['timeout'] = $v ['timeout'];
			}
			$this->conf [$v ['id']] = $node;
		}
	}
	
	/**
	 *
	 * @param $callback mixed
	 *        	function returning callback for the request
	 * @throws Utility_Http_Server_Exception
	 */
	function handle($callback) {
		$res = array (
				'errorCode' => 0,
				'errorMessage' => null,
				'result' => null 
		);
		try {
			$param = $this->getParam ();
			// client check
			$clientOk = false;
			if (array_key_exists ( $param ['_id'], $this->conf )) {
				$clientOk = true;
				$conf = ( object ) $this->conf [$param ['_id']];
			}
			if (! $clientOk) {
				throw new Utility_Http_Server_Exception ( 'client not found, _id=' . $param ['_id'], $this->errorCode );
			}
			// timeout check
			if (isset ( $conf->timeout ) && is_numeric ( $conf->timeout )) {
				$timeoutOk = false;
				$timeoutRemote = $param ['_time'];
				if (time () - $timeoutRemote <= $conf->timeout) {
					$timeoutOk = true;
				}
				if (! $timeoutOk) {
					throw new Utility_Http_Server_Exception ( 'request timeout', $this->errorCode );
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
				throw new Utility_Http_Server_Exception ( 'sign error', $this->errorCode );
			}
			if (! is_callable ( $callback )) {
				throw new Utility_Http_Server_Exception ( 'callback is invalid, callback=' . $callback, $this->errorCode );
			}
			unset ( $param ['_id'], $param ['_time'], $param ['_sign'] );
			$res ['result'] = call_user_func ( $callback, $param );
		} catch ( Exception $e ) {
			if (ini_get ( 'log_errors' )) {
				error_log ( $e->__toString () . "\n" );
			}
			if ($e instanceof Utility_Http_Server_Exception || false == $this->errorStack) {
				$res ['errorMessage'] = $e->getMessage ();
			} else {
				$res ['errorMessage'] = $e->__toString ();
			}
			$res ['errorCode'] = $e->getCode ();
		}
		echo json_encode ( $res );
	}
	
	/**
	 * Get params sent from client
	 *
	 * @throws Utility_Http_Server_Exception
	 * @return array()
	 */
	private function getParam() {
		$param = $_GET;
		$keys = array (
				'_id',
				'_sign',
				'_time' 
		);
		foreach ( $keys as $v ) {
			if (empty ( $param [$v] )) {
				throw new Utility_Http_Server_Exception ( 'param ' . $v . ' not found', $this->errorCode );
			}
		}
		return $param;
	}
}