<?php
/**
 * Neet CurlMulti
 * @author admin@phpdr.net
 *        
 */
require dirname ( __DIR__ ) . '/lib/BaiduPcs-2.1.0/libs/BaiduPCS.class.php';
class Utility_BaiduPCS extends BaiduPCS {
	private $clientID;
	private $clientSecret;
	private $curl;
	private $tokenFile;
	/**
	 *
	 * @param unknown $param
	 *        	tokenFile
	 *        	clientID
	 *        	clientSecret
	 */
	function __construct($param = array()) {
		if (empty ( $param ['tokenFile'] )) {
			user_error ( 'tokenFile not specified', E_USER_ERROR );
		}
		if (empty ( $param ['clientID'] )) {
			user_error ( 'clientID not set', E_USER_ERROR );
		}
		if (empty ( $param ['clientSecret'] )) {
			user_error ( 'clientSecret not set', E_USER_ERROR );
		}
		$this->tokenFile = $param ['tokenFile'];
		$this->clientID = $param ['clientID'];
		$this->clientSecret = $param ['clientSecret'];
		$fileData = '';
		if (is_file ( $this->tokenFile )) {
			$fileData = file_get_contents ( $this->tokenFile );
		}
		if (empty ( $fileData )) {
			user_error ( "token not found", E_USER_ERROR );
		}
		$token = json_decode ( $fileData );
		$url = 'https://pcs.baidu.com/rest/2.0/pcs/quota?method=info&access_token=' . $token->access_token;
		$r = null;
		$this->curl = new CurlMulti_Core ();
		$this->curl->add ( array (
				'url' => $url 
		), function ($result) use(&$r) {
			if ($result ['info'] ['http_code'] == 200) {
				$r = $result;
			} else {
				user_error ( 'http error, http_code=' . $result ['info'] ['http_code'] . ', url=' . $result ['info'] ['url'], E_USER_WARNING );
			}
		}, function ($err) {
			user_error ( 'curl error, ' . $err ['error'] [0] . ': ' . $err ['error'] [1], E_USER_ERROR );
		} )->start ();
		$r = json_decode ( $r ['content'] );
		if (isset ( $r->error_code ) && $r->error_code == 111) {
			$token = json_decode ( $this->tokenRefresh ( $token->refresh_token ) );
		}
		if (isset ( $token->error )) {
			$msg = $token->error;
			if (isset ( $token->error_description )) {
				$msg .= ', ' . $token->error_description;
			}
			user_error ( $msg, E_USER_ERROR );
		}
		parent::__construct ( $token->access_token );
	}
	
	/**
	 * curl upload.The SDK method read the whole file to memory and then upload it...
	 *
	 * @param unknown $file        	
	 * @return array false
	 */
	function curlUpload($file, $remoteFile) {
		if (! is_file ( $file )) {
			return false;
		}
		$size = filesize ( $file );
		$url = 'https://c.pcs.baidu.com/rest/2.0/pcs/file?method=upload&path=' . urlencode ( $remoteFile ) . '&access_token=' . $this->getAccessToken ();
		$opt = array ();
		$timeout = $size / 1024 / 5;
		if ($timeout < 600) {
			$timeout = 600;
		}
		$opt [CURLOPT_TIMEOUT] = $timeout;
		$opt [CURLOPT_SSL_VERIFYPEER] = false;
		$opt [CURLOPT_SSL_VERIFYHOST] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 30;
		$opt [CURLOPT_POST] = true;
		$opt [CURLOPT_POSTFIELDS] = array (
				'file' => '@' . $file 
		);
		$return = null;
		$this->curl->cbInfo = function ($info) use($file, $size) {
			$row = array_pop ( $info ['running'] );
			if (! empty ( $row ['size_upload'] )) {
				echo "\r\33[2K" . $row ['size_upload'] . '/' . $size . "\t" . round ( $row ['size_upload'] / $size * 100, 2 ) . '% (' . round ( $row ['speed_upload'] / 1024, 0 ) . 'k/s)';
			}
		};
		$this->curl->add ( array (
				'url' => $url,
				'opt' => $opt 
		), function ($r) use(&$return) {
			$return = $r;
		} )->start ();
		return $return;
	}
	
	/**
	 * get an accesstoken.The method should be called in controller.
	 */
	static function tokenInit($callbackUrl, $clientID, $clientSecret, $tokenFile, $code = null) {
		if (! isset ( $code )) {
			// 获取auth code
			$redirectUrl = "https://openapi.baidu.com/oauth/2.0/authorize?response_type=code&client_id=" . $clientID . "&redirect_uri=$callbackUrl&display=popup&scope=basic netdisk";
			header ( 'Location: ' . $redirectUrl, true, 302 );
		} else {
			// 根据auth code获取access token
			$url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code=$code&client_id=" . $clientID . "&client_secret=" . $clientSecret . "&redirect_uri=$callbackUrl";
			$token = self::tokenSave ( $url, $tokenFile );
			$token = json_decode ( $token );
			if (isset ( $token->access_token )) {
				return true;
			}
		}
	}
	
	/**
	 * get and accesstoken use fresh token
	 */
	private function tokenRefresh($refreshToken) {
		$url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=refresh_token&refresh_token=$refreshToken&client_id=" . $this->clientID . "&client_secret=" . $this->clientSecret . "&scope=basic netdisk";
		return self::tokenSave ( $url, $this->tokenFile );
	}
	
	/**
	 * save token to file
	 *
	 * @param unknown $token        	
	 */
	private static function tokenSave($url, $tokenFile) {
		$token = null;
		$curl = new CurlMulti_Core ();
		$curl->add ( array (
				'url' => $url 
		), function ($r) use(&$token) {
			$token = $r ['content'];
		} )->start ();
		file_put_contents ( $tokenFile, $token, LOCK_EX );
		return $token;
	}
}
