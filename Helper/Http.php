<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class Helper_Http {
	
	/**
	 * send post
	 *
	 * @param string $url        	
	 * @param array $data        	
	 * @param string $type        	
	 * @return string
	 */
	static function post($url, $data, $type = 'form') {
		if ($type == 'form')
			$contentType = 'application/x-www-form-urlencoded';
		elseif ($type == 'xmlrpc')
			$contentType = 'text/xml';
		else
			user_error ( 'type was invalid', E_USER_WARNING );
		$url = parse_url ( $url );
		if (! $url)
			user_error ( "couldn't parse url", E_USER_ERROR );
		$port = "80";
		if ($type == 'form')
			$data = http_build_query ( $data );
		$len = strlen ( $data );
		$uri = $url ['path'];
		if (! empty ( $url ['query'] )) {
			$uri .= '?' . $url ['query'];
		}
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
		$fp = fsockopen ( $url ['host'], $port );
		$line = "";
		if (! $fp) {
			user_error ( "fsockopen error", E_USER_ERROR );
		} else {
			fwrite ( $fp, $out );
			while ( ! feof ( $fp ) ) {
				$line .= fgets ( $fp, 2048 );
			}
			// cutoff header
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
	 * redirect
	 *
	 * @param string $url        	
	 * @param number $code        	
	 */
	static function redirect($url, $code = 302) {
		// some brower will cache 301 request, clean it!
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
