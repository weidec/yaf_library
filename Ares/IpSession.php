<?php

/**
 * <pre>
 * 基于IP的Session
 * 每个值都有自己独立的过期时间,整个session的过期时间是所有值当中最大的那个,flash型数据默认过期时间是$flashLifetime
 * 最大持续时间$maxLifttime
 *
 * </pre>
 *
 * CREATE TABLE `ip_session` (
 * `id` char(15) NOT NULL,
 * `expire` int(10) unsigned NOT NULL,
 * `data` text NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='基于IP的Session';
 */
class IpSession {
	const DATA_SYS = 10;
	const DATA_USER = 11;
	const NODE_EXPIRE = 1;
	const NODE_VALUE = 2;
	const NODE_IS_FLASH = 3;
	private $table = 'ip_session';
	private $now;
	private $flashLifetime = 86400;
	private $maxLifetime = 31536000; // one year
	private $row;
	private $ip;
	function __construct($ip = null) {
		if (! isset ( $ip )) {
			$this->ip = UtilHttp::getClientIp ();
		} else {
			$this->ip = $ip;
		}
		if(!UtilValidator::isIp($ip)){
			throw new ErrorException('ip is invalid');
		}
		$this->now = time ();
	}

	function __destruct() {
		$this->gc ();
	}

	/**
	 * lifetime
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $lifetime
	 *        	小于零删除数据,等于null是flash型数据
	 */
	function set($key, $value, $lifetime) {
		$row = $this->getRow ();
		if (is_null ( $lifetime )) {
			$expire = $this->now + $this->flashLifetime;
		}
		if (! is_numeric ( $lifetime )) {
			throw new ErrorException ( 'lifetime is invalid' );
		} else {
			if ($lifetime < 0) { // 过期删除
				unset ( $row->data [self::DATA_USER]->$key );
			} elseif ($lifetime > 0) { // 设置过期时间
				if ($lifetime > $this->maxLifetime) {
					$lifetime = $this->maxLifetime;
				}
				$expire = $this->now + $lifetime;
			}
		}
		// 记录数据
		if ($expire > $this->now) {
			$node = array ();
			$node [self::NODE_EXPIRE] = $expire;
			$node [self::NODE_VALUE] = $value;
			// 是否是flash型数据
			if (is_null ( $lifetime ))
				$node [self::NODE_IS_FLASH] = 1;
			else
				$node [self::NODE_IS_FLASH] = 0;
			$row->data [self::DATA_USER]->$key = $node;
		}
		$this->setRow ( $row );
	}

	/**
	 * 过期数据会被删除
	 *
	 * @param string $key
	 * @return mixed
	 */
	function get($key) {
		$row = $this->getRow ();
		if (isset ( $row->expire ) && $row->expire < $this->now) {
			$this->destroy ();
			return;
		}
		$data = null;
		if (property_exists ( $row->data [self::DATA_USER], $key )) {
			$node = $row->data [self::DATA_USER]->$key;
			$unset = false;
			if ($node [self::NODE_EXPIRE] >= $this->now) {
				$data = $node [self::NODE_VALUE];
				if ($node [self::NODE_IS_FLASH] == 1)
					$unset = true;
			} else {
				$unset = true;
			}
			if ($unset) {
				unset ( $row->data [self::DATA_USER]->$key );
				$this->setRow ( $row );
			}
		}
		return $data;
	}
	function destroy() {
		$this->getDB ()->exec ( "delete from {$this->table} where id='{$this->ip}'" );
	}

	/**
	 * 获取数据库中的一行记录
	 *
	 * @return \stdClass
	 */
	private function getRow() {
		if (! isset ( $this->row )) {
			$row = $this->getDB ()->query ( "select * from {$this->table} where id=" . $this->getDB ()->quote ( $this->ip ) )->fetch ();
			if (! empty ( $row )) {
				$row->expire = $row->expire;
				$row->data = unserialize ( $row->data );
			} else {
				$row = new stdClass ();
				$row->id = $this->ip;
				$row->expire = null;
				$row->data = array ();
				$row->data [self::DATA_SYS] = new stdClass ();
				$row->data [self::DATA_USER] = new stdClass ();
				$this->setRow ( $row );
			}
			$this->row = $row;
		}
		return $this->row;
	}

	/**
	 * 持久化
	 *
	 * @param object $row
	 */
	private function setRow($row) {
		if (! empty ( $row )) {
			// 设置整个session的expire,遍历用户数据寻找最大expire,并删除过期数据
			if (! empty ( $row->data [self::DATA_USER] )) {
				foreach ( $row->data [self::DATA_USER] as $k => $v ) {
					if ($v [self::NODE_EXPIRE] > $row->expire) {
						$row->expire = $v [self::NODE_EXPIRE];
					} elseif ($v [self::NODE_EXPIRE] < $this->now) {
						unset ( $row->data [self::DATA_USER]->$k );
					}
				}
			} else {
				// 不直接destroy因为后续操作可能会set数据
				$row->expire = $this->now - 3600;
			}
			$db = $this->getDb ();
			if (( int ) $db->query ( "select count(*) from {$this->table} where id='{$row->id}'" )->fetchColumn () > 0) {
				$sql = "update {$this->table} set expire='{$row->expire}',data=" . $this->getDB ()->quote ( serialize ( $row->data ) ) . " where id='{$row->id}'";
			} else {
				$sql = "insert into {$this->table}(id,expire,data) values('{$row->id}','{$row->expire}','" . serialize ( $row->data ) . "')";
			}
			if (false === $this->getDB ()->exec ( $sql )) {
				throw new ErrorException ( $this->getDb ()->errorInfo () );
			}
			$this->row = $row;
		}
	}

	/**
	 * 一定记录执行的gc
	 *
	 * @throws ErrorException
	 */
	protected function gc() {
		$p = 0.01;
		settype ( $p, 'float' );
		$max = floor ( 1 / $p );
		if (mt_rand ( 1, $max ) >= $max) {
			if (false === $this->getDB ()->exec ( "delete from {$this->table} where expire<'" . date ( 'Y-m-d H:i:s', $this->now ) . "'" ))
				throw new ErrorException ( 'session gc failed,sql : ' . $this->getDb ()->errorInfo () );
		}
	}

	/**
	 * 获取数据库
	 *
	 * @return \PDO
	 */
	private function getDb() {
		return _::app()->getDb ();
	}
}