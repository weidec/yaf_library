<?php
class Rpc_Http_Client {
	private $conf;
	private $url;
	private $opt = array ();
	
	/**
	 *
	 * @param array|object $conf
	 *        	id,url,key
	 */
	function __construct($conf) {
		if (is_object ( $conf )) {
			$conf = ( array ) $conf;
		}
		$keys = array (
				'id',
				'url',
				'key' 
		);
		$this->conf = new stdClass ();
		foreach ( $keys as $v ) {
			if (! array_key_exists ( $v, $conf )) {
				throw new Rpc_Http_Client_Exception ( "$v not set in \$conf" );
			}
			$this->conf->$v = $conf [$v];
		}
	}
	
	/**
	 * parameters are same as server side
	 *
	 * @param string $name        	
	 */
	function __call($name, $args = array()) {
		$time = time ();
		$req = array ();
		$req = $args;
		$req ['_id'] = $this->conf->id;
		$req ['_method'] = $name;
		$req ['_time'] = $time;
		$req ['_sign'] = md5 ( $time . $this->conf->key );
		$query = '?' . http_build_query ( $req );
		$this->url = rtrim ( $this->conf->url, '/' ) . $query;
		$res = $this->fetch ( $this->url );
		if (0 === strpos ( $res, '{"' )) {
			$res = json_decode ( $res );
		}
		return $res;
	}
	/**
	 *
	 * @return string
	 */
	function getLastUrl() {
		return $this->url;
	}
	
	/**
	 * curl opt
	 *
	 * @param unknown $name        	
	 * @param unknown $value        	
	 */
	function setOpt($name, $value) {
		$this->opt [$name] = $value;
	}
	private function fetch($url) {
		$opt = array ();
		$opt [CURLOPT_HEADER] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 3;
		$opt [CURLOPT_TIMEOUT] = 5;
		$opt [CURLOPT_AUTOREFERER] = true;
		$opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$opt [CURLOPT_RETURNTRANSFER] = true;
		$opt [CURLOPT_FOLLOWLOCATION] = true;
		$opt [CURLOPT_MAXREDIRS] = 10;
		$ch = curl_init ();
		curl_setopt_array ( $ch, $opt );
		if (empty ( $this->opt )) {
			curl_setopt_array ( $ch, $this->opt );
		}
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$res = curl_exec ( $ch );
		curl_close ( $ch );
		return $res;
	}
}
