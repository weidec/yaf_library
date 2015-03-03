<?php
class Function_Debug {
}

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

/**
 *
 * @param mixed $true        	
 */
function mem_usage($true = false, $precision = 3) {
	return round ( memory_get_usage ( $true ) / 1024 / 1024, $precision ) . 'MB';
}

/**
 * get execution time in second call
 * 
 * @param boolean $continue        	
 */
function runtime($continue = false) {
	static $start = null;
	$time = microtime ( true );
	if (is_null ( $start ))
		$start = $time;
	else {
		echo round ( $time - $start, 9 ) . (PHP_SAPI != 'cli' ? '<br>' : "\n");
		$start = null;
	}
	if (! $continue && is_null ( $start ))
		exit ();
}