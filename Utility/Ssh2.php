<?php

/**
 *
 * @author admin@phpdr.net
 *
 */
class Utility_Ssh2 {
	private $conn;
	private $shell;
	private $stdin;
	private $stdout;
	
	/**
	 *
	 * @param array $config
	 *        	string host
	 *        	number port
	 *        	hostkey ssh-rsa,ssh-dss
	 *        	string fingerprint default null
	 */
	function __construct($config) {
		$conn = ssh2_connect ( $config ['host'], $config ['port'], array (
				'hostkey' => $config ['hostkey'] 
		), array (
				'disconnect' => array (
						$this,
						'disconnect' 
				) 
		) );
		$fp = ssh2_fingerprint ( $conn, SSH2_FINGERPRINT_MD5 );
		$fpOK = false;
		if (PHP_SAPI == 'cli') {
			$this->stdin = fopen ( 'php://stdin', 'r' );
			$this->stdout = fopen ( 'php://stdout', 'w' );
			if (! isset ( $config ['fingerprint'] ) || empty ( $config ['fingerprint'] )) {
				fwrite ( $this->stdout, "$fp\nIs fingerprint OK ?(y/n)" );
				while ( true ) {
					$input = strtolower ( stream_get_line ( $this->stdin, 1 ) );
					if ($input == 'y') {
						$fpOK = true;
						break;
					} elseif ($input != 'n') {
						fwrite ( $this->stdout, "input y or n!\n" );
					}
				}
			}
		} else {
			if (! isset ( $config ['fingerprint'] )) {
				$fpOK = true;
			} elseif ($fp == $config ['fingerprint']) {
				$fpOK = true;
			}
		}
		if (! $fpOK) {
			user_error ( 'server fingerprint is ' . $fp . ', provided is ' . $config ['fingerprint'], ' , not match!', E_USER_WARNING );
		}
		$this->conn = $conn;
		$this->shell = ssh2_shell ( $conn, null, null, 1024 );
	}
	
	/**
	 *
	 * @param unknown $user        	
	 * @param unknown $pass        	
	 */
	function authPass($user, $pass) {
		if (! ssh2_auth_password ( $this->conn, $user, $pass )) {
			user_error ( 'Password Authentication Failed', E_USER_ERROR );
		}
	}
	
	/**
	 *
	 * @param unknown $user        	
	 * @param unknown $pubKey        	
	 * @param unknown $priKey        	
	 * @param unknown $phrase        	
	 */
	function authKey($user, $pubKey, $priKey, $phrase) {
		if (! ssh2_auth_pubkey_file ( $this->conn, $user, $pubKey, $priKey, $phrase )) {
			user_error ( 'Public Key Authentication Failed', E_USER_ERROR );
		}
	}
	
	/**
	 * execute a linux command
	 *
	 * @param unknown $cmd        	
	 */
	function exec($cmd) {
		$result = array ();
		$stream = ssh2_exec ( $this->conn, $cmd );
		$result ['error'] = stream_get_contents ( ssh2_fetch_stream ( $stream, SSH2_STREAM_STDERR ) );
		$result ['out'] = stream_get_contents ( ssh2_fetch_stream ( $stream, SSH2_STREAM_STDIO ) );
		return $result;
	}
	
	/**
	 * get a shell in command line
	 */
	function shell() {
		if (PHP_SAPI != 'cli') {
			user_error ( 'shell method can only be called in php cli mode', E_USER_ERROR );
		}
		$last = '';
		// quit shell first and quite while loop second
		$signalTerminate = false;
		while ( true ) {
			$cmd = $this->fread ( $this->stdin );
			$out = stream_get_contents ( $this->shell, 1024 );
			if (! empty ( $out ) and ! empty ( $last )) {
				$l1 = strlen ( $out );
				$l2 = strlen ( $last );
				$l = $l1 > $l2 ? $l2 : $l1;
				$last = substr ( $last, $l );
				$out = substr ( $out, $l );
			}
			echo ltrim ( $out );
			if ($signalTerminate) {
				break;
			}
			if (in_array ( trim ( $cmd ), array (
					'exit' 
			) )) {
				$signalTerminate = true;
			}
			if (! empty ( $cmd )) {
				$last = $cmd;
				fwrite ( $this->shell, $cmd );
			}
		}
	}
	
	/**
	 * solved windows command line read problem,no better way
	 */
	private function fread($fd) {
		static $data = '';
		$read = array (
				$fd 
		);
		$write = array ();
		$except = array ();
		$result = stream_select ( $read, $write, $except, 0, 1000 );
		if ($result === false)
			user_error ( 'stream_select failed', E_USER_WARNING );
		if ($result !== 0) {
			$c = stream_get_line ( $fd, 1 );
			if ($c != chr ( 13 ))
				$data .= $c;
			if ($c == chr ( 10 )) {
				$t = $data;
				$data = '';
				return $t;
			}
		}
	}
	function __destruct() {
		if (isset ( $this->stdin )) {
			fclose ( $this->stdin );
		}
		if (isset ( $this->stdout )) {
			fclose ( $this->stdout );
		}
		$this->disconnect ();
	}
	private function disconnect() {
		if (is_resource ( $this->conn )) {
			unset ( $this->conn );
			fclose ( $this->shell );
		}
	}
}