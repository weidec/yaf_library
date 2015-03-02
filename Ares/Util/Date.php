<?php

namespace lib;

class Date {

	/**
	 * 把时间戳形式的时间长度格式化成可读的时间长度
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