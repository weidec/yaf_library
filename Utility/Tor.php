<?php
/**
 * only be use in linux command line
 *
 * @author admin@phpdr.net
 */
class Utility_Tor {
	private $controlPassword = 'tor321';
	private $tor;
	function __construct($port) {
		settype ( $port, 'integer' );
		if (php_sapi_name () == 'cli') {
			$ports = $this->scan ();
			if (in_array ( $port, $ports )) {
				$this->tor = '127.0.0.1:' . $port;
			} else {
				user_error ( 'Tor port not found, port=' . $port, E_USER_WARNING );
			}
		} else {
			user_error ( __CLASS__ . " can only be used under php command line", E_USER_ERROR );
		}
	}
	
	/**
	 * restart tor, get new ip immediately
	 */
	function restart() {
		$port = explode ( ':', $this->tor );
		$port = $port [1];
		`tor-client stop $port`;
		`tor-client start $port`;
	}
	
	/**
	 * scan tor clinet
	 * --SocksPort 10090 --SocksListenAddress 127.0.0.1 --ControlPort 10091
	 */
	private function scan() {
		$lines = `ps aux|grep 'tor.\+--SocksPort [0-9]\+'`;
		$ports = array ();
		if (! empty ( $lines )) {
			$lines = explode ( "\n", $lines );
			foreach ( $lines as $v ) {
				$v = trim ( $v );
				if (! empty ( $v )) {
					preg_match ( '/--SocksPort (\d+)/', $v, $v );
					$port = $v [1];
					if (is_numeric ( $port )) {
						$ports [] = ( integer ) $port;
					} else {
						user_error ( 'tor port is invlaid, port=' . $port, E_USER_WARNING );
					}
				}
			}
			$ports = array_slice ( array_unique ( $ports ), 0 );
		}
		return $ports;
	}
	
	/**
	 * get a new ip use tor control port
	 *
	 * @param string $ip        	
	 * @param int $port
	 *        	control port
	 * @param string $authCode
	 *        	should be wraped by single quotes
	 * @return boolean
	 */
	function switchIp() {
		list ( $ip, $port ) = explode ( ':', $this->tor );
		$r = false;
		$fp = fsockopen ( $ip, $port, $errno, $errstr, 3 );
		if (! $fp) {
			user_error ( "can't connect to tor control, ip=$ip, port=$port", E_USER_WARNING );
		} else {
			fputs ( $fp, "AUTHENTICATE \"{$this->pass}\"\r\n" );
			$response = fread ( $fp, 1024 );
			list ( $code, $text ) = explode ( ' ', $response, 2 );
			if ($code != '250') {
				user_error ( "authentication failed", E_USER_WARNING );
			} else {
				// send the request to for new identity
				fputs ( $fp, "signal NEWNYM\r\n" );
				$response = fread ( $fp, 1024 );
				list ( $code, $text ) = explode ( ' ', $response, 2 );
				if ($code != '250') {
					user_error ( "signal failed", E_USER_WARNING );
				} else {
					$r = true;
				}
			}
			fclose ( $fp );
		}
		return $r;
	}
	
	/**
	 *
	 * @param number $port
	 *        	tor port
	 * @return mixed
	 */
	function getProxy() {
		$r = array (
				CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
				CURLOPT_PROXY => $this->tor 
		);
		return $r;
	}
}