<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class Helper_Validator {
	static function ip($ip) {
		return ( bool ) preg_match ( '/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip );
	}
	
	/**
	 * leap year inlcuded
	 * format:year-month-day
	 * accurate, but slow
	 *
	 * @param unknown $str        	
	 * @return boolean
	 */
	static function date($str) {
		$p = '/^((((1[6-9]|[2-9]\d)\d{2})-(0?[13578]|1[02])-(0?[1-9]|[12]\d|3[01]))|(((1[6-9]|[2-9]\d)\d{2})-(0?[13456789]|1[012])-(0?[1-9]|[12]\d|30))|(((1[6-9]|[2-9]\d)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))-0?2-29-))$/';
		return ( bool ) preg_match ( $p, $str );
	}
	
	/**
	 * leap year included
	 * format: year-month-day hour:minute:second
	 * accurate, but slow
	 *
	 * @param unknown $str        	
	 */
	static function datetime($str) {
		$p = '/^((((1[6-9]|[2-9]\d)\d{2})-(0?[13578]|1[02])-(0?[1-9]|[12]\d|3[01]))|(((1[6-9]|[2-9]\d)\d{2})-(0?[13456789]|1[012])-(0?[1-9]|[12]\d|30))|(((1[6-9]|[2-9]\d)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))-0?2-29-)) (([0-1]?[0-9])|([2][0-3])):([0-5]?[0-9])(:([0-5]?[0-9]))$/';
		return ( bool ) preg_match ( $p, $str );
	}
	
	/**
	 *
	 * @param unknown $str        	
	 * @return boolean
	 */
	static function email($str) {
		$p = '/^[a-z]([a-z0-9]*[-_]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i';
		return ( bool ) preg_match ( $p, $str );
	}
}