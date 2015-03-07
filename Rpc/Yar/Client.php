<?php
class Rpc_Yar_Client {
	private $conf;
	private $className;
	private $client;
	
	/**
	 *
	 * @param array $conf
	 *        	clientId,url,key,timeout(milisecond) timeout is optional
	 * @param string $className
	 *        	remote className
	 * @param string $key        	
	 */
	function __construct(array $conf, $className) {
		$keys = array (
				'clientId',
				'url',
				'key' 
		);
		$this->conf = new stdClass ();
		foreach ( $keys as $v ) {
			if (! array_key_exists ( $v, $conf )) {
				throw new Yar_Client_Exception ( "$v not set in \$conf" );
			}
			if ($v != 'url' && $v != 'timeout') {
				$this->conf->$v = $conf [$v];
			}
		}
		$this->client = new Yar_Client ( $conf ['url'] );
		if (array_key_exists ( 'timeout', $conf ) && ! empty ( $conf ['timeout'] )) {
			$this->setOpt ( YAR_OPT_TIMEOUT, ( int ) $conf ['timeout'] );
		}
		$this->className = $className;
	}
	
	/**
	 * parameters are same as server side
	 *
	 * @param string $name        	
	 */
	function __call($name, $args) {
		$name = $this->className . '.' . $name;
		$time = time ();
		$sign = md5 ( $time . $this->conf->key );
		$param = array (
				'id' => $this->conf->clientId,
				'sign' => $sign,
				'time' => $time,
				'args' => $args 
		);
		return $this->client->api ( $name, $param );
	}
	
	/**
	 * same as Yar_Client::setOpt()
	 *
	 * @param unknown $name        	
	 * @param unknown $value        	
	 */
	function setOpt($name, $value) {
		return $this->client->setOpt ( $name, $value );
	}
}