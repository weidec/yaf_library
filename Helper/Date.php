<?php
class Helper_Date {
	
	/**
	 * convert int time length to readable format
	 *
	 * @param integer $timeScale        	
	 * @return string
	 */
	static function timeLengthFormat($timeScale) {
		$str = $timeScale . 's';
		if ($str > 3600) {
			$str = ceil ( $str / 3600 ) . 'h' . ceil ( ($str % 3600) / 60 ) . 'm';
		} elseif ($str > 60) {
			$str = ceil ( $str / 60 ) . 'm' . ($str % 60) . 's';
		}
		return $str;
	}
}