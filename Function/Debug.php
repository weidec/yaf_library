<?php
class Function_Debug {
}

/**
 * 用易读方式输出格式化数据
 *
 * @param mixed $data        	
 * @param boolean $continue        	
 * @param boolean $return
 *        	如果为true，$continue将不起作用
 * @return string 如果指定第三个参数为true才有返回值
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
 * 用易读方式输出格式化数据
 *
 * @param mixed $data        	
 * @param boolean $continue        	
 * @param boolean $return
 *        	如果为true，$continue将不起作用
 * @return string 如果指定第三个参数为true才有返回值
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
 * 用易读格式输出内存使用率
 *
 * @param mixed $true        	
 */
function mem_usage($true = false, $precision = 3) {
	return round ( memory_get_usage ( $true ) / 1024 / 1024, $precision ) . 'MB';
}

/**
 * 连续两次调用可以获取程序运行时间
 *
 * @param boolean $continue
 *        	是否继续执行程序
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