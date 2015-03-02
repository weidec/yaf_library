<?php
class Ares_Regex {
	static function isIp($ip) {
		return ( bool ) preg_match ( '/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip );
	}
}