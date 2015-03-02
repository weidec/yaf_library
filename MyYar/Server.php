<?php
class MyYaf_Rpc_Yar_Server {
	private function getConf() {
		return Yaf_Registry::get ( 'config' )->rpc->server;
	}
	static function handle() {
		(new Yar_Server ( new self () ))->handle ();
	}
	function api($name, $param) {
		$signOk = false;
		if (array_key_exists ( 'sign', $param )) {
			$signRemote = $param ['sign'];
			$args = $param ['args'];
			$conf = self::getConf ( $name );
			$sign = md5 ( $name . serialize ( $args ) . $conf->key );
			if ($sign == $signRemote) {
				$signOk = true;
			}
		}
		if (! $signOk) {
			throw new Exception ( 'sign error' );
		}
		list ( $className, $method ) = explode ( '.', $name );
		$class = new $className ();
		return call_user_func_array ( array (
				$class,
				$method 
		), $args );
	}
}