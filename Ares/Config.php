<?php

/**
 * 解析.ini格式的配置文件为一个树形结构的对象
 * 配置文件不同section通过冒号继承
 * 默认根据hostname确定使用的section，如果不能确定就优先使用production
 * 检测环境的时候总是优先检测production，其余section按定义顺序检测
 *
 * @author ares@phpdr.net
 *        
 */
class Ares_Config {
	/**
	 * 解析后的配置文件
	 *
	 * @var \stdClass
	 */
	private $config;
	/**
	 * 一个二维数组，键是配置文件的section
	 * 值是一个数组或回调函数
	 * 如果是数组则计算hostname是否在数组中决定是否使用该section
	 * 如果是回调函数通过返回值true或false来确定是否使用该section
	 *
	 * @var array
	 */
	private $map = array ();
	
	/**
	 * section会被解析，:表示继承
	 * 配置项中的'.'用来区分层级关系
	 * section中的'.'不会被解析，配置中的数组不受影响。
	 *
	 * @param string $conf        	
	 * @throws ErrorException
	 * @return \stdClass
	 */
	function __construct($conf, $map) {
		$config = $this->parseIni ( ( object ) parse_ini_string ( $conf, true ) );
		if (array_key_exists ( 'production', $map )) {
			$production = $map ['production'];
			unset ( $map ['production'] );
			$map = array_merge ( array (
					'production' => $production 
			), $map );
		} else {
			throw new ErrorException ( 'production section not found in config' );
		}
		$section = 'production';
		$hostname = gethostname ();
		
		foreach ( $map as $k => $v ) {
			if (is_array ( $v )) {
				foreach ( $v as $v1 ) {
					if ($v1 == $hostname) {
						$section = $k;
						break 2;
					}
				}
			} elseif (is_callable ( $v )) {
				if (true == call_user_func ( $v )) {
					$section = $k;
					break;
				}
			} else {
				throw new ErrorException ( 'Wrong map value in ' . __CLASS__ );
			}
		}
		$this->config = $config->$section;
	}
	
	/**
	 * 总是返回配置对象
	 *
	 * @return mixed
	 */
	function __get($key) {
		if (isset ( $this->config->$key )) {
			return $this->config->$key;
		}
	}
	
	/**
	 * 切分
	 *
	 * @param \stdClass $v        	
	 * @param string $k1        	
	 * @param mixed $v1        	
	 */
	private function split($v, $k1, $v1) {
		$keys = explode ( '.', $k1 );
		$last = array_pop ( $keys );
		$node = $v;
		foreach ( $keys as $v2 ) {
			if (! isset ( $node->$v2 )) {
				$node->$v2 = new stdClass ();
			}
			$node = $node->$v2;
		}
		$node->$last = $v1;
		if (count ( $keys ) > 0) {
			unset ( $v->$k1 );
		}
	}
	
	/**
	 * parseIni
	 *
	 * @param object $conf        	
	 * @return \stdClass
	 */
	private function parseIni($conf) {
		$confObj = new stdClass ();
		foreach ( $conf as $k => $v ) {
			// 是section
			if (is_array ( $v )) {
				$confObj->$k = ( object ) $v;
				foreach ( $v as $k1 => $v1 ) {
					call_user_func ( array (
							$this,
							'split' 
					), $confObj->$k, $k1, $v1 );
				}
			} else {
				call_user_func ( array (
						$this,
						'split' 
				), $confObj, $k, $v );
			}
		}
		unset ( $conf );
		// 处理继承
		foreach ( $confObj as $k => $v ) {
			if (false !== strpos ( $k, ':' )) {
				if (0 === strpos ( $k, ':' )) {
					throw new ErrorException ( 'config ' . $k . ' is invalid, \':\' can\'t be the first char' );
				} elseif (1 < substr_count ( $k, ':' )) {
					throw new ErrorException ( 'config ' . $k . ' is invalid, \':\' can appear only once' );
				} else {
					$keys = explode ( ':', $k );
					if (! isset ( $confObj->$keys [1] )) {
						throw new ErrorException ( 'parent section ' . $keys [1] . ' doesn\'t exist in config file' );
					} else {
						if (isset ( $confObj->$keys [0] )) {
							throw new ErrorException ( 'config is invalid, ' . $keys [0] . ' and ' . $k . ' conflicts' );
						} else {
							$confObj->$keys [0] = $this->deepCloneR ( $confObj->$keys [1] );
							$this->objectMergeR ( $confObj->$keys [0], $v );
							unset ( $confObj->$k );
						}
					}
				}
			}
		}
		return $confObj;
	}
	
	/**
	 * php默认是浅克隆，函数实现深克隆
	 *
	 * @param object $obj        	
	 * @return object $obj
	 */
	private function deepCloneR($obj) {
		$objClone = clone $obj;
		foreach ( $objClone as $k => $v ) {
			if (is_object ( $v )) {
				$objClone->$k = $this->deepCloneR ( $v );
			}
		}
		return $objClone;
	}
	
	/**
	 * 递归的合并两个对象
	 *
	 * @param unknown $obj1        	
	 * @param unknown $obj2        	
	 */
	private function objectMergeR($obj1, $obj2) {
		foreach ( $obj2 as $k => $v ) {
			if (is_object ( $v ) && isset ( $obj1->$k ) && is_object ( $obj1->$k )) {
				$this->objectMergeR ( $obj1->$k, $v );
			} else {
				$obj1->$k = $v;
			}
		}
	}
}