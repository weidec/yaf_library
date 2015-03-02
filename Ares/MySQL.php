<?php
class Ares_MySQL extends PDO {
	function __construct($dsn, $username = null, $passwd = null, $options = null) {
		parent::__construct ( $dsn, $username, $passwd, $options );
		$this->setAttribute ( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ );
		$this->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->exec ( 'set names utf8' );
	}
	
	/**
	 * 插入一条记录
	 *
	 * @param unknown $table        	
	 * @param unknown $data        	
	 * @param string $type
	 *        	null,duplicate,ignore
	 * @return mixed
	 */
	function insert($table, $data, $type = null) {
		if (is_object ( $data )) {
			settype ( $data, 'array' );
		}
		$cols = implode ( ',', array_keys ( $data ) );
		return $this->insertAll ( $table, $cols, array (
				$data 
		), $type );
	}
	
	/**
	 * 批量插入
	 *
	 * @param string $table        	
	 * @param string|array $cols
	 *        	插入的列
	 * @param
	 *        	string 逗号分隔的列
	 * @param mixed $data
	 *        	必须是一个多维数组，可以是数字数组或关联数组
	 * @param string $type
	 *        	null,duplicate,ignore
	 * @return mixed
	 */
	function insertAll($table, $cols, $data, $type = null) {
		if (empty ( $data )) {
			return 0;
		}
		$sql = "insert";
		if ($type == 'ignore') {
			$sql .= " ignore";
		}
		if (is_array ( $cols )) {
			$cols = implode ( ',', $cols );
		}
		$sql .= " into `$table` (`" . str_replace ( ',', '`,`', $cols ) . '`) values (';
		$cols = explode ( ',', $cols );
		foreach ( $data as $v ) {
			settype ( $v, 'array' );
			foreach ( $cols as $k1 => $v1 ) {
				if (array_key_exists ( $v1, $v )) {
					$sql .= $this->quote ( $v [$v1] );
				} elseif (array_key_exists ( $k1, $v )) {
					$sql .= $this->quote ( $v [$k1] );
				} else {
					throw new PDOException ( 'column notfound, column=' . $v1 );
				}
				$sql .= ',';
			}
			$sql = substr ( $sql, 0, - 1 ) . '),(';
		}
		$sql = substr ( $sql, 0, - 2 );
		if ($type == 'duplicate') {
			$sql .= " ON DUPLICATE KEY UPDATE ";
			foreach ( $cols as $v ) {
				$sql .= '`' . $v . '`=VALUES(`' . $v . '`),';
			}
			$sql = substr ( $sql, 0, - 1 );
		}
		return $this->exec ( $sql );
	}
	
	/**
	 * 更新数据库记录
	 *
	 * @param string $table        	
	 * @param array $data        	
	 * @param string $where        	
	 * @return boolean
	 */
	function update($table, $data, $where) {
		$val = array ();
		$sql = "update $table set ";
		foreach ( $data as $k => $v ) {
			$sql .= '`' . $k . '`=?,';
			$val [] = $v;
		}
		$sql = rtrim ( $sql, ',' ) . ' where ' . $where;
		$ps = $this->prepare ( $sql );
		return $ps->execute ( $val );
	}
}