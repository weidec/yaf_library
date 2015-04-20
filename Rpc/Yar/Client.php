<?php
class Rpc_Yar_Client {
	private $conf;
	private $client;
	
	/**
	 *
	 * @param array $conf
	 *        	id,url,key
	 */
	function __construct(array $conf) {
		$keys = array (
				'id',
				'url',
				'key' 
		);
		$this->conf = new stdClass ();
		foreach ( $keys as $v ) {
			if (! array_key_exists ( $v, $conf )) {
				throw new Yar_Client_Exception ( "$v not set in \$conf" );
			}
			if ($v != 'url') {
				$this->conf->$v = $conf [$v];
			}
		}
		$this->client = new Yar_Client ( $conf ['url'] );
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
		$args['_id']=$this->conf->id;
		$args['_sign']=$sign;
		$args['_time']=$time;
		return $this->client->api ( $name,$args);
	}
	
	/**
	 *
	 * @return Yar_Client
	 */
	function getClient() {
		return $this->client;
	}
}