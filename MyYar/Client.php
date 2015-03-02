<?php
class MyYaf_Rpc_Yar_Client {
	use MyYaf_Rpc_Yar_Util;
	private $model;
	private $client;
	private $key;
	function __construct($name, $model) {
		$conf = self::getConf ( $name );
		$this->model = $model;
		$this->client = new Yar_Client ( $conf->url );
		if (isset ( $conf->timeout )) {
			$this->client->setOpt ( YAR_OPT_TIMEOUT, ( int ) $conf->timeout );
		}
		$this->key = $conf->key;
	}
	function __call($name, $args) {
		$name = $this->model . '.' . $name;
		$sign = md5 ( $name . serialize ( $args ) . $this->key );
		$param = array (
				'sign' => $sign,
				'args' => $args 
		);
		return $this->client->api ( $name, $param );
	}
	function setOpt($name, $value) {
		return $this->client->setOpt ( $name, $value );
	}
}