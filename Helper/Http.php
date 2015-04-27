<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class Helper_Http {
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
