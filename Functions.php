<?php
class Functions {
}

if (! function_exists ( 'printr' )) {
	/**
	 *
	 * @param mixed $data
	 * @param boolean $continue
	 * @param boolean $return
	 * @return string
	 */
	function printr($data, $continue = false, $return = false) {
		$str = '';
		if (PHP_SAPI != 'cli')
			$str .= "<pre>\n";
		$str .= print_r ( $data, true );
		if (PHP_SAPI != 'cli')
			$str .= "</pre>\n";
		if ($return) {
			return trim ( $str );
		} else {
			echo $str . "\n";
			if (! $continue)
				exit ();
		}
	}
}

if (! function_exists ( 'vardump' )) {
	/**
	 *
	 * @param mixed $data
	 * @param boolean $continue
	 * @param boolean $return
	 * @return string
	 */
	function vardump($data, $continue = false, $return = false) {
		ob_start ();
		if (PHP_SAPI != 'cli')
			echo "<pre>\n";
		var_dump ( $data );
		if (PHP_SAPI != 'cli')
			echo "</pre>\n";
		$str = ob_get_clean ();
		if ($return) {
			return trim ( $str );
		} else {
			echo $str;
			if (! $continue)
				exit ();
		}
	}
}