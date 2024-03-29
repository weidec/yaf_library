<?php
class Utility_Http_Client {
	private $conf;
	private $curl;
	private $url;
	private $res = array ();
	private $opt = array ();
	
	/**
	 *
	 * @param array $conf
	 *        	id,url,key
	 */
	function __construct($conf) {
		$keys = array (
				'id',
				'url',
				'key' 
		);
		$this->conf = new stdClass ();
		foreach ( $keys as $v ) {
			if (! array_key_exists ( $v, $conf )) {
				throw new Utility_Http_Client_Exception ( "$v not set in \$conf" );
			}
			$this->conf->$v = $conf [$v];
		}
		if (false === strpos ( $this->conf->url, '?' )) {
			$this->conf->url .= '?';
		} else {
			$this->conf->url .= '&';
		}
		$this->curl = new CurlMulti_Core ();
	}
	
	/**
	 * add a request, mainly used for multithread call
	 *
	 * @param mixed $requestId        	
	 * @param array $args        	
	 * @param string $callback        	
	 * @return self
	 */
	function add($requestId, $args = array(), $callback = null) {
		if (array_key_exists ( $requestId, $this->res )) {
			user_error ( 'id already added, id=' . $requestId, E_USER_WARNING );
			return $this;
		}
		$this->res [$requestId] = null;
		if (empty ( $callback )) {
			$callback = function ($r) use($requestId) {
				$this->res [$requestId] = $r ['content'];
			};
		}
		$this->url = array ();
		$url = $this->getUrl ( $args );
		$this->curl->add ( array (
				'url' => $url,
				'opt' => $this->opt 
		), $callback );
		$this->url [$requestId] = $url;
		return $this;
	}
	
	/**
	 * start request
	 *
	 * @return array
	 */
	function start() {
		$this->curl->start ();
		$res = $this->res;
		foreach ( $res as $k => $v ) {
			$res [$k] = $this->decode ( $v );
		}
		$this->res = array ();
		return $res;
	}
	
	/**
	 *
	 * @param unknown $data        	
	 */
	private function decode($data) {
		if (0 === strpos ( $data, '{"' )) {
			return json_decode ( $data );
		}
		return $data;
	}
	
	/**
	 * single call
	 *
	 * @param string $name        	
	 * @return string
	 */
	function call($args = array()) {
		$res = null;
		$url = $this->getUrl ( $args );
		$this->url = $url;
		$this->curl->add ( array (
				'url' => $url,
				'opt' => $this->opt 
		), function ($r) use(&$res) {
			$res = $r ['content'];
		} )->start ();
		return $this->decode ( $res );
	}
	
	/**
	 *
	 * @param unknown $args        	
	 * @return string
	 */
	private function getUrl($args) {
		$time = time ();
		$args ['_id'] = $this->conf->id;
		$args ['_time'] = $time;
		$args ['_sign'] = md5 ( $time . $this->conf->key );
		$query .= http_build_query ( $args );
		$url = $this->conf->url . $query;
		return $url;
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
}
