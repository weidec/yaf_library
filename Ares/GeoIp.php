<?php

namespace Ares;

class GeoIp {
	private $gi = null;
	function __construct($dataFile = null) {
		$path = '../library/GeoIp';
		require_once ($path . '/geoip/geoip.inc');
		require_once ($path . '/geoip/geoipcity.inc');
		if (! isset ( $dataFile )) {
			$dataFile = $path . '/geoip/GeoLiteCity.dat';
		}
	}
	
	/**
	 * 从GeoIP数据文件解析IP地址
	 *
	 * @param string $ip        	
	 * @return \stdClass
	 */
	function getLocation($ip) {
		$ipData = geoip_record_by_addr ( $this->gi, $ip );
		if (! empty ( $ipData )) {
			foreach ( array (
					'city',
					'region',
					'country_name' 
			) as $v ) {
				$ipData->$v = iconv ( "ISO-8859-1", "UTF-8", $ipData->$v );
			}
		}
		return $ipData;
	}
	function __destruct() {
		geoip_close ( $this->gi );
	}
}