<?php
/**
 * parse ini file as a object tree
 * section can be inherited by means of colon
 *
 * @author admin@phpdr.net
 *        
 */
class Helper_Config {
	/**
	 * parsed config
	 *
	 * @var stdClass
	 */
	private $config;
	
	/**
	 * Colon represent inheritance.
	 * Dot represent hierarchy.
	 * Dot int section name not be parsed. Dot in array config not be parsed.
	 *
	 * @param string $content        	
	 */
	function __construct($content, $section) {
		$config = $this->parseIni ( ( object ) parse_ini_string ( $content, true ) );
		if (! property_exists ( $config, $section )) {
			user_error ( 'section not found', E_USER_WARNING );
		}
		$this->config = $config->$section;
	}
	
	/**
	 * get config item
	 *
	 * @return mixed
	 */
	function __get($key) {
		if (isset ( $this->config->$key )) {
			return $this->config->$key;
		}
	}
	
	/**
	 * split
	 *
	 * @param stdClass $v        	
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
	 * @return stdClass
	 */
	private function parseIni($conf) {
		$confObj = new stdClass ();
		foreach ( $conf as $k => $v ) {
			// is section
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
		// deal with inheritance
		foreach ( $confObj as $k => $v ) {
			if (false !== strpos ( $k, ':' )) {
				if (0 === strpos ( $k, ':' )) {
					user_error ( 'config ' . $k . ' is invalid, \':\' can\'t be the first char', E_USER_WARNING );
				} elseif (1 < substr_count ( $k, ':' )) {
					user_error ( 'config ' . $k . ' is invalid, \':\' can appear only once', E_USER_WARNING );
				} else {
					$keys = explode ( ':', $k );
					if (! isset ( $confObj->$keys [1] )) {
						user_error ( 'parent section ' . $keys [1] . ' doesn\'t exist in config file', E_USER_WARNING );
					} else {
						if (isset ( $confObj->$keys [0] )) {
							user_error ( 'config is invalid, ' . $keys [0] . ' and ' . $k . ' conflicts', E_USER_WARNING );
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
	 * php clone is shallow and this achieve deep clone
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
	 * merge two objects recursively
	 *
	 * @param object $obj1        	
	 * @param object $obj2        	
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