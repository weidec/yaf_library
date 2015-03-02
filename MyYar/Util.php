<?php
trait MyYaf_Rpc_Yar_Util {
	private static $conf = array ();
	/**
	 * clinet config
	 * @param unknown $name
	 * @throws Exception
	 * @return multitype:
	 */
	private static function getConf($name) {
		if (! array_key_exists ( $name, self::$conf )) {
			$conf = Yaf_Registry::get ( 'config' )->rpc->client;
			if (! isset ( $conf->$name )) {
				throw new Exception ( 'config not found, name=' . $name );
			}
			$conf = $conf->$name;
			self::$conf [$name] = $conf;
		}
		return self::$conf [$name];
	}
}