<?php

namespace _lib;

use ErrorException;

/**
 * 只能在linux环境下运行，只能在命令环境中运行。
 *
 * @author ares@phpdr.net
 */
class Tor {
	private $controlPassword = 'tor321';
	private $tor;
	function __construct($port) {
		settype($port,'integer');
		if (php_sapi_name () == 'cli') {
			$ports=$this->scan ();
			if(in_array($port,$ports)){
				$this->tor='127.0.0.1:'.$port;
			}else{
				throw new ErrorException('Tor port not found, port='.$port);
			}
		} else {
			throw new ErrorException ( __CLASS__ . " can only be used under php command line" );
		}
	}

	/**
	 * 重启tor，这样可以刷新IP，switchIp这个操作有延迟
	 */
	function restart(){
		$port=explode(':',$this->tor);
		$port=$port[1];
		`tor-client stop $port`;
		`tor-client start $port`;
	}

	/**
	 * 扫描tor客户端
	 * --SocksPort 10090 --SocksListenAddress 127.0.0.1 --ControlPort 10091
	 */
	private function scan() {
		$lines = `ps aux|grep 'tor.\+--SocksPort [0-9]\+'`;
		$ports=array();
		if (! empty ( $lines )) {
			$lines = explode ( "\n", $lines );
			foreach ( $lines as $v ) {
				$v = trim ( $v );
				if (! empty ( $v )) {
					preg_match ( '/--SocksPort (\d+)/', $v, $v );
					$port = $v [1];
					if (is_numeric ( $port )) {
						$ports[]=(integer)$port;
					} else {
						throw new ErrorException ( 'tor port is invlaid, port=' . $port );
					}
				}
			}
			$ports = array_slice ( array_unique ( $ports), 0 );
		}
		return $ports;
	}

	/**
	 * 切换一个新的IP
	 *
	 * @param string $ip
	 *        	控制端IP
	 * @param int $port
	 *        	控制端port
	 * @param string $authCode
	 *        	控制密码
	 * @param int $lastSwitch
	 *        	上次切换时间
	 * @return boolean
	 */
	function switchIp() {
		list($ip,$port)=explode(':', $this->tor);
		$r = false;
		$fp = fsockopen ( $ip, $port, $errno, $errstr, 3 );
		if (! $fp) {
			throw new Errorexception ( "can't connect to tor control, ip=$ip, port=$port" );
		} else {
			fputs ( $fp, "AUTHENTICATE \"{$this->pass}\"\r\n" );
			$response = fread ( $fp, 1024 );
			list ( $code, $text ) = explode ( ' ', $response, 2 );
			if ($code != '250') {
				throw new ErrorException ( "authentication failed" );
			} else {
				// send the request to for new identity
				fputs ( $fp, "signal NEWNYM\r\n" );
				$response = fread ( $fp, 1024 );
				list ( $code, $text ) = explode ( ' ', $response, 2 );
				if ($code != '250') {
					throw new ErrorException ( "signal failed" );
				} else {
					$r = true;
				}
			}
			fclose ( $fp );
		}
		return $r;
	}

	/**
	 * 返回一个包含curl配置的socks5代理
	 *
	 * @param number $port
	 *        	要使用的tor的端口
	 * @return mixed array(CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,CURLOPT_PROXY=>)，失败返回false
	 */
	function getProxy() {
		$r = array (
				CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
				CURLOPT_PROXY => $this->tor
		);
		return $r;
	}

	/**
	 * 获取某个tor的IP
	 *
	 * @param string $tor
	 * @return mixed
	 */
	function getIp() {
		$ch = curl_init ();
		$opt = array ();
		$opt [CURLOPT_URL] = 'http://173.255.201.38/ip';
		$opt [CURLOPT_HEADER] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 10;
		$opt [CURLOPT_TIMEOUT] = 10;
		$opt [CURLOPT_AUTOREFERER] = true;
		$opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$opt [CURLOPT_RETURNTRANSFER] = true;
		$opt [CURLOPT_FOLLOWLOCATION] = true;
		$opt [CURLOPT_MAXREDIRS] = 10;
		$opt [CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
		$opt [CURLOPT_PROXY] = $this->tor;
		curl_setopt_array ( $ch, $opt );
		$ip = curl_exec ( $ch );
		return $ip;
	}
}