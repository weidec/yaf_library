<?php

namespace _lib;

use ErrorException;
use _;

class GoogleKeyword {
	private $baseUrl = 'https://www.google.com';
	private $curl;
	private $tor;
	function __construct($privoxyPort, $torPort) {
		$this->curl = new CurlMulti ();
		$cookie = new AppData ( 'curl/curl_cookie/' . md5 ( UtilString::implodeR ( '/', _::app()->getRoute () ) ) );
		$this->curl->opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$this->curl->opt [CURLOPT_TIMEOUT] = 15;
		$this->curl->opt [CURLOPT_COOKIEJAR] = $cookie->getFile ();
		$this->curl->opt [CURLOPT_COOKIEFILE] = $cookie->getFile ();
		$this->curl->opt [CURLOPT_HTTPPROXYTUNNEL] = true;
		$this->curl->opt [CURLOPT_SSL_VERIFYPEER] = false;
		$this->curl->opt [CURLOPT_SSL_VERIFYHOST] = false;
		$this->curl->cache ['on'] = false;
		$this->curl->maxThread = 1;
		$dir = APP_PATH . '/cache/curl';
		if (! file_exists ( $dir )) {
			mkdir ( $dir );
		}
		$this->curl->cache ['dir'] = $dir;
		$this->curl->opt [CURLOPT_PROXY] = '127.0.0.1:' . $privoxyPort;
		$this->tor = new Tor ( $torPort );
	}

	/**
	 * 根据歌词名取关键词
	 */
	function get($keyword) {
		$i = 0;
		while ( true ) {
			$torRestart = false;
			$keywords = array ();
			$url = $this->baseUrl . '/search?q=' . urlencode ( $keyword );
			/*
			 * 当时单线程怀疑有内存泄露所以使用了多线程
			 */
			$this->curl->add ( array (
					'url' => $url
			), function ($r) use(&$torRestart, &$keywords, $url) {
				// http错误
				if ($r ['info'] ['http_code'] == 503) {
					// 这里要验证码
					$torRestart = true;
				} elseif ($r ['info'] ['http_code'] == 502) {
					// The server encountered a temporary error and could not complete your request.
					$torRestart = true;
				} elseif ($r ['info'] ['http_code'] == 200) {
					// 有"Searches related to"这几个字就有关键词
					if (false !== strrpos ( $r ['content'], 'Searches related to' )) {
						$html = phpQuery::newDocumentHTML ( $r ['content'] );
						// $wraper必须有
						$wraper = $html ['#brs'];
						if ($wraper->count () == 0) {
							throw new ErrorException ( 'Section relate keywords not found, url=' . $url );
						}
						$list = $wraper ['div._p a'];
						if ($list->count () == 0) {
							$list = $wraper ['div._o a'];
						}
						if ($list->count () == 0) {
							$list = $wraper ['div._n a'];
						}
						if ($list->count () > 0) {
							foreach ( $list as $v ) {
								$v = pq ( $v );
								$keywords [] = trim ( $v->text () );
							}
						}
						if (empty ( $keywords )) {
							// file_put_contents ( '/root/t.html', $r ['content'] );
							throw new ErrorException ( 'No keywords found, url=' . $url );
						}
						unset ( $wraper, $list );
						phpQuery::unloadDocuments ();
					}
				} elseif ($r ['info'] ['http_code'] == 403) {
					// 这是彻底被封了
					$torRestart = true;
				} else {
					throw new ErrorException ( 'Unknow error, url=' . $url );
				}
			}, function ($err) use(&$torRestart) {
				if (28 == $err ['error'] [0]) {
					// timeout
					$torRestart = true;
				} elseif (56 == $err ['error'] [0]) {
					// Failure with receiving network data.
					$torRestart = true;
				} else {
					throw new ErrorException ( 'curl error, ' . $err ['error'] [0] . ': ' . $err ['error'] [1] . ', url=' . $err ['info'] ['url'], E_USER_WARNING );
				}
			} );
			$this->curl->fuck ();
			if ($torRestart) {
				$this->tor->restart ();
				$i ++;
				echo "tor restart $i\n";
			} else {
				return $keywords;
			}
		}
	}
}

