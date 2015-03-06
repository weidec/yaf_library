<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class Utility_ChunZhenIp {
	private $file;
	function __construct($dataFile) {
		$this->file = $dataFile;
	}
	/**
	 * parse ip address from QQ Chunzhen IP data file
	 *
	 * @param mixed $ip        	
	 */
	function parse($ip) {
		if (! preg_match ( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip )) {
			user_error ( 'wrong ip', E_USER_WARNING );
			return false;
		}
		$fd = fopen ( $this->file, 'rb' );
		if ($fd) {
			$ip = explode ( '.', $ip );
			$ipNum = $ip [0] * 16777216 + $ip [1] * 65536 + $ip [2] * 256 + $ip [3];
			// get start and end position
			$DataBegin = fread ( $fd, 4 );
			$DataEnd = fread ( $fd, 4 );
			$ipbegin = implode ( '', unpack ( 'L', $DataBegin ) );
			if ($ipbegin < 0)
				$ipbegin += pow ( 2, 32 );
			$ipend = implode ( '', unpack ( 'L', $DataEnd ) );
			if ($ipend < 0)
				$ipend += pow ( 2, 32 );
			$ipAllNum = ($ipend - $ipbegin) / 7 + 1;
			$BeginNum = 0;
			$EndNum = $ipAllNum;
			$ip1num = null;
			$ip2num = null;
			// find ip use dichotomy algorithm
			while ( $ip1num > $ipNum || $ip2num < $ipNum ) {
				$Middle = intval ( ($EndNum + $BeginNum) / 2 );
				// excursion the pointer to index position and read 4 bytes
				fseek ( $fd, $ipbegin + 7 * $Middle );
				$ipData1 = fread ( $fd, 4 );
				if (strlen ( $ipData1 ) < 4) {
					fclose ( $fd );
					return '- System Error';
				}
				// convert to long, plush 2^32 if is negative
				$ip1num = implode ( '', unpack ( 'L', $ipData1 ) );
				if ($ip1num < 0)
					$ip1num += pow ( 2, 32 );
					// if greater than ip then change the end position and go to next loop
				if ($ip1num > $ipNum) {
					$EndNum = $Middle;
					continue;
				}
				// fetch next index after current fetched
				$DataSeek = fread ( $fd, 3 );
				if (strlen ( $DataSeek ) < 3) {
					fclose ( $fd );
					return '- System Error';
				}
				$DataSeek = implode ( '', unpack ( 'L', $DataSeek . chr ( 0 ) ) );
				fseek ( $fd, $DataSeek );
				$ipData2 = fread ( $fd, 4 );
				if (strlen ( $ipData2 ) < 4) {
					fclose ( $fd );
					return '- System Error';
				}
				$ip2num = implode ( '', unpack ( 'L', $ipData2 ) );
				if ($ip2num < 0)
					$ip2num += pow ( 2, 32 );
					// return unknow if not found
				if ($ip2num < $ipNum) {
					if ($Middle == $BeginNum) {
						fclose ( $fd );
						return '- Unknown';
					}
					$BeginNum = $Middle;
				}
			}
			// don't understand code bellow yet...
			$ipFlag = fread ( $fd, 1 );
			if ($ipFlag == chr ( 1 )) {
				$ipSeek = fread ( $fd, 3 );
				if (strlen ( $ipSeek ) < 3) {
					fclose ( $fd );
					return '- System Error';
				}
				$ipSeek = implode ( '', unpack ( 'L', $ipSeek . chr ( 0 ) ) );
				fseek ( $fd, $ipSeek );
				$ipFlag = fread ( $fd, 1 );
			}
			if ($ipFlag == chr ( 2 )) {
				$AddrSeek = fread ( $fd, 3 );
				if (strlen ( $AddrSeek ) < 3) {
					fclose ( $fd );
					return '- System Error';
				}
				$ipFlag = fread ( $fd, 1 );
				if ($ipFlag == chr ( 2 )) {
					$AddrSeek2 = fread ( $fd, 3 );
					if (strlen ( $AddrSeek2 ) < 3) {
						fclose ( $fd );
						return '- System Error';
					}
					$AddrSeek2 = implode ( '', unpack ( 'L', $AddrSeek2 . chr ( 0 ) ) );
					fseek ( $fd, $AddrSeek2 );
				} else {
					fseek ( $fd, - 1, SEEK_CUR );
				}
				while ( ($char = fread ( $fd, 1 )) != chr ( 0 ) )
					$ipAddr2 .= $char;
				$AddrSeek = implode ( '', unpack ( 'L', $AddrSeek . chr ( 0 ) ) );
				fseek ( $fd, $AddrSeek );
				while ( ($char = fread ( $fd, 1 )) != chr ( 0 ) )
					$ipAddr1 .= $char;
			} else {
				fseek ( $fd, - 1, SEEK_CUR );
				while ( ($char = fread ( $fd, 1 )) != chr ( 0 ) )
					$ipAddr1 .= $char;
				$ipFlag = fread ( $fd, 1 );
				if ($ipFlag == chr ( 2 )) {
					$AddrSeek2 = fread ( $fd, 3 );
					if (strlen ( $AddrSeek2 ) < 3) {
						fclose ( $fd );
						return '- System Error';
					}
					$AddrSeek2 = implode ( '', unpack ( 'L', $AddrSeek2 . chr ( 0 ) ) );
					fseek ( $fd, $AddrSeek2 );
				} else {
					fseek ( $fd, - 1, SEEK_CUR );
				}
				while ( ($char = fread ( $fd, 1 )) != chr ( 0 ) )
					$ipAddr2 .= $char;
			}
			fclose ( $fd );
			if (preg_match ( '/http/i', $ipAddr2 )) {
				$ipAddr2 = '';
			}
			$ipaddr = "$ipAddr1 $ipAddr2";
			$ipaddr = preg_replace ( '/CZ88\.NET/is', '', $ipaddr );
			$ipaddr = preg_replace ( '/^\s*/is', '', $ipaddr );
			$ipaddr = preg_replace ( '/\s*$/is', '', $ipaddr );
			if (preg_match ( '/http/i', $ipaddr ) || $ipaddr == '') {
				$ipaddr = '- Unknown';
			}
			$ipaddr = mb_convert_encoding ( $ipaddr, 'utf-8', 'GBK' );
			return $ipaddr;
		}
	}
}