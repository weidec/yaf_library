<?php
class Ares_Http {
	
	/**
	 * 发送post请求
	 *
	 * @param unknown $url        	
	 * @param unknown $data        	
	 * @param string $type        	
	 * @throws ErrorException
	 * @return string
	 */
	static function post($url, $data, $type = 'form') {
		if ($type == 'form')
			$contentType = 'application/x-www-form-urlencoded';
		elseif ($type == 'xmlrpc')
			$contentType = 'text/xml';
		else
			throw new ErrorException ( 'type was invalid' );
			// 先解析url
		$url = parse_url ( $url );
		if (! $url)
			user_error ( "couldn't parse url", E_USER_ERROR );
		$port = "80";
		if ($type == 'form')
			$data = http_build_query ( $data );
		$len = strlen ( $data );
		$uri = $url ['path'];
		if (! empty ( $url ['query'] ))
			$uri .= '?' . $url ['query'];
			// 拼上http头
		$out = "POST " . $uri . " HTTP/1.1\r\n";
		$out .= "Host: " . $url ['host'] . "\r\n";
		$out .= "Expires: Mon, 26 Jul 1970 05:00:00 GMT\r\n";
		$out .= "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . " GMT\r\n";
		$out .= "Cache-Control: no-cache, must-revalidate\r\n";
		$out .= "Pragma: no-cache\r\n";
		$out .= "Content-type: $contentType\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n";
		$out .= "\r\n";
		$out .= $data . "\r\n";
		// 打开一个sock
		$fp = fsockopen ( $url ['host'], $port );
		$line = "";
		if (! $fp) {
			user_error ( "fsockopen error", E_USER_ERROR );
		} else {
			fwrite ( $fp, $out );
			while ( ! feof ( $fp ) ) {
				$line .= fgets ( $fp, 2048 );
			}
			// 去掉头文件
			if ($line) {
				$body = stristr ( $line, "\r\n\r\n" );
				$body = substr ( $body, 4 );
				$line = $body;
			}
			fclose ( $fp );
			return $line;
		}
	}
	
	/**
	 * 301或302跳转
	 *
	 * @param string $url        	
	 * @param number $code        	
	 */
	static function redirect($url, $code = 302) {
		// 有些浏览器会对301进行缓存，这里清空之
		header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . "GMT" );
		header ( "Cache-Control: no-cache, must-revalidate" );
		header ( "Pragma: no-cache" );
		if ($code == 301) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
		} elseif ($code == 302) {
			header ( 'HTTP/1.1 302 Moved Temporarily' );
		}
		if (0 !== strpos ( $url, 'http://' ) && 0 !== strpos ( $url, 'https://' )) {
			$url = $_SERVER ['HTTP_HOST'] . $url;
		}
		header ( 'Location: ' . $url, true, $code );
	}
	
	/**
	 * 获取客户端IP地址
	 *
	 * @return mixed
	 */
	static function getClientIp() {
		$unknown = 'unknown';
		$ip = '';
		if (isset ( $_SERVER ['HTTP_X_FORWARDED_FOR'] ) && $_SERVER ['HTTP_X_FORWARDED_FOR'] && strcasecmp ( $_SERVER ['HTTP_X_FORWARDED_FOR'], $unknown )) {
			$ip = $_SERVER ['HTTP_X_FORWARDED_FOR'];
		} elseif (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], $unknown )) {
			$ip = $_SERVER ['REMOTE_ADDR'];
		}
		/*
		 * 处理多层代理的情况 或者使用正则方式： $ip = preg_match("/[\d\.] {7,15}/", $ip, $matches) ? $matches[0] : $unknown;
		 */
		if (false !== strpos ( $ip, ',' ))
			$ip = reset ( explode ( ',', $ip ) );
		return $ip;
	}
	
	/**
	 * 404 page
	 */
	static function error404() {
		if (PHP_SAPI != 'cli') {
			header ( 'HTTP/1.1 404 Not Found' );
			$str404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
			$str404 .= '<html><head>';
			$str404 .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />';
			$str404 .= '<title>404 Not Found</title>';
			$str404 .= '</head><body>';
			$str404 .= '<h1>404 Not Found</h1>';
			$str404 .= '</body></html>';
			exit ( $str404 );
		} else {
			global $argv;
			$action = array_key_exists ( 1, $argv ) ? $argv [1] : $argv [0];
			echo 'Not Found : ' . $action . "\n";
			exit ();
		}
	}
	
	/**
	 * 403 page
	 */
	static function error403() {
		if (PHP_SAPI != 'cli') {
			header ( 'HTTP/1.1 403 Forbidden' );
			$str404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
			$str404 .= '<html><head>';
			$str404 .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />';
			$str404 .= '<title>403 Forbidden</title>';
			$str404 .= '</head><body>';
			$str404 .= '<h1>403 Forbidden</h1>';
			$str404 .= '</body></html>';
			exit ( $str404 );
		} else {
			global $argv;
			$action = array_key_exists ( 1, $argv ) ? $argv [1] : $argv [0];
			echo 'Forbidden : ' . $action . "\n";
			exit ();
		}
	}
}
