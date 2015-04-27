<?php
class Util_Http_Client {
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
	 * @param mixed $id
	 *        	id for request
	 * @param array $args        	
	 * @param string $callback        	
	 * @return self
	 */
	function add($id, $args = array(), $callback = null) {
		if (array_key_exists ( $id, $this->res )) {
			user_error ( 'id already added, id=' . $id, E_USER_WARNING );
			return $this;
		}
		$this->res [$id] = null;
		if (empty ( $callback )) {
			$callback = function ($r) use($id) {
				$this->res [$id] = $r ['content'];
			};
		}
		$this->url = array ();
		$url = $this->getUrl ( $args );
		$this->curl->add ( array (
				'url' => $url,
				'opt' => $this->opt 
		), $callback );
		$this->url [] = $url;
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
		$this->res = array ();
		return $res;
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
		$this->curl->add ( array (
				'url' => $url,
				'opt' => $this->opt 
		), function ($r) use(&$res) {
			$res = $r ['content'];
		} )->start ();
		return $res;
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
