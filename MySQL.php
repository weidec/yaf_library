<?php
/**
 * 
 * @author admin@phpdr.net
 *
 */
class MySQL extends PDO {
	/**
	 * insert a single row
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
	 * insert rows
	 *
	 * @param string $table        	
	 * @param string|array $cols        	
	 * @param mixed $data
	 *        	two dimension
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
	 * update
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