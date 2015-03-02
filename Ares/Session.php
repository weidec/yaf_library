<?php

namespace _lib;

use stdClass;
use ErrorException;
use PDO;
use _;

/**
 * <pre>
 * 常驻session
 * 每个值都有自己独立的过期时间,整个session的过期时间是所有值当中最大的那个,flash型数据默认过期时间是$flashLifetime
 * 最大持续时间$maxLifttime
 *
 * CREATE TABLE `session` (
 * `id` char(32) NOT NULL,
 * `expire` int(10) unsigned NOT NULL,
 * `data` text NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Session';
 */
class Session {
	const DATA_SYS = 0;
	const DATA_USER = 1;
	const NODE_EXPIRE = 2;
	const NODE_VALUE = 3;
	const NODE_IS_FLASH = 4;
	private $cookieName = 'session';
	private $table = 'session';
	private $now;
	private $flashLifetime = 86400;
	private $maxLifetime = 31536000; // 一年
	private $row;
	function __construct($cookieName = null) {
		if (is_string ( $cookieName ) || strlen ( $cookieName ) > 0)
			$this->cookieName = $cookieName;
		$this->now = time ();
	}
	function __destruct() {
		$this->gc ();
	}

	/**
	 * 设置session
	 *
	 * @param unknown $key
	 * @param unknown $value
	 * @param unknown $lifetime
	 *        	小于零删除数据,等于null是flash型数据
	 */
	function set($key, $value, $lifetime) {
		$row = $this->getRow ();
		// 设置本条数据的expire
		if (is_null ( $lifetime )) {
			$expire = $this->now + $this->flashLifetime;
		} else {
			if (! is_numeric ( $lifetime )) {
				throw new ErrorException ( 'lifttime is invalid, lifttime: ' . $lifetime );
			}
			if ($lifetime < 0) { // 过期删除
				unset ( $row->data [self::DATA_USER]->$key );
			} elseif ($lifetime > 0) { // 设置过期时间
				if ($lifetime > $this->$maxLifetime) {
					$lifetime = $this->$maxLifetime;
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
	 * 如果指定值过期会被unset
	 *
	 * @param unknown $name
	 * @return mixed
	 */
	function get($key) {
		$row = $this->getRow ();
		$data = null;
		if ($row->expire > $this->now) {
			// 检查是否存在并且cookie名称是否吻合
			if (property_exists ( $row->data [self::DATA_USER], $key ) && $row->data [self::DATA_SYS]->cookieName == $this->cookieName) {
				$node = $row->data [self::DATA_USER]->$key;
				$unset = false;
				if ($node [self::NODE_EXPIRE] > $this->now) {
					$data = $node [self::NODE_VALUE];
					if ($node [self::NODE_IS_FLASH] == 1) {
						$unset = true;
					}
				} else {
					$unset = true;
				}
				if ($unset) {
					unset ( $row->data [self::DATA_USER]->$key );
					$this->setRow ( $row );
				}
			}
		} else {
			$this->destroy ();
		}
		return $data;
	}

	/**
	 * 销毁当前session
	 */
	private function destroy() {
		$id = $this->getCookie ();
		if (! empty ( $id )) {
			$this->getDb ()->exec ( "delete from {$this->table} where id='{$id}'" );
			$this->setCookie ( $id, $this->now - 3600 );
		}
	}

	/**
	 * 返回数据库记录
	 *
	 * @return Ambigous <unknown, mixed>
	 */
	private function getRow() {
		if (! isset ( $this->row )) {
			$id = $this->getCookie ();
			$row = $this->getDb ()->query ( "select * from {$this->table} where id=" . $this->getDb ()->quote ( $id ) )->fetch ();
			if ($row) {
				$row->expire = $row->expire;
				$row->data = unserialize ( $row->data );
				$this->row = $row;
			} else {
				$this->row = $this->create ();
			}
		}
		return $this->row;
	}

	/**
	 * 持久化
	 *
	 * @param unknown $row
	 */
	private function setRow($row) {
		// 设置整个session的expire,遍历用户数据寻找最大expire,过期数据会被删除
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
		if (( int ) $db->query ( "select count(*) from $this->table where id=" . $db->quote ( $row->id ) )->fetchColumn ( 0 ) > 0) {
			$sql = "update {$this->table} set expire={$row->expire},data=" . $db->quote ( serialize ( $row->data ) ) . " where id='{$row->id}'";
		} else {
			$sql = "insert into {$this->table}(id,expire,data) values('{$row->id}',{$row->expire}," . $db->quote ( serialize ( $row->data ) ) . ")";
		}
		if (false === $db->exec ( $sql )) {
			$error = $db->errorInfo ();
			throw new ErrorException ( $error [2] );
		}
		$this->row = $row;
		$this->setCookie ( $row->id, $row->expire );
	}

	/**
	 * 垃圾清理
	 */
	private function gc() {
		$p = 0.01;
		settype ( $p, 'float' );
		$max = floor ( 1 / $p );
		if (mt_rand ( 1, $max ) >= $max) {
			if (false === $this->getDb ()->exec ( "delete from {$this->table} where expire<" . $this->now )) {
				$error = $this->getDb ()->errorInfo ();
				throw new ErrorException ( 'session gc failed, ' . $error [2] );
			}
		}
	}

	/**
	 * 写入Cookie
	 *
	 * @param unknown $value
	 * @param unknown $expire
	 * @return boolean
	 */
	private function setCookie($value, $expire) {
		if (! empty ( $value )) {
			$cid = $this->getClientId ();
			$value = substr ( $cid, 0, 3 ) . $value . substr ( $cid, 3, 1 );
		}
		// 一次请求中同一个值避免多次调用setcookie
		static $list;
		if (! isset ( $list )) {
			$list = new stdClass ();
		}
		$key = md5 ( $value . $expire );
		if (! isset ( $list->$key )) {
			if (! setcookie ( $this->cookieName, $value, $expire, '/', '.' . $_SERVER ['HTTP_HOST'], false, true )) {
				throw new ErrorException ( 'failed to set cookie ' . $this->cookieName );
				return false;
			}
			$list->$key = true;
		}
		return true;
	}

	/**
	 * 获取Cookie
	 *
	 * @return mixed boolean string
	 */
	private function getCookie() {
		if (! isset ( $_COOKIE [$this->cookieName] )) {
			return false;
		}
		$cookie = $_COOKIE [$this->cookieName];
		$cid = substr ( $cookie, 0, 3 ) . substr ( $cookie, 35, 1 );
		if ($cid != $this->getClientId ())
			return false;
		$sid = substr ( $cookie, 3, 32 );
		if (32 == strlen ( $sid ))
			return $sid;
	}

	/**
	 * create a new session
	 */
	private function create() {
		$salt = $_SERVER ['SERVER_ADDR'] . $_SERVER ['SERVER_PORT'];
		// 检查数据表是否存在
		$sql = "SELECT count(*) FROM information_schema.TABLES WHERE table_name ='{$this->table}'";
		if ($this->getDb ()->query ( $sql )->fetchColumn ( 0 ) == 0) {
			throw new ErrorException ( 'session table doesn\'t exist' );
		}
		// 最多尝试次数
		$maxTime = 100;
		$i = 0;
		do {
			$id = md5 ( uniqid ( __FILE__ ) );
			$c = $this->getDb ()->query ( "select count(*) as c from {$this->table} where id='$id'" )->fetch ( PDO::FETCH_OBJ );
			if (is_object ( $c )) {
				if (0 === ( int ) $c->c)
					break;
			}
			if ($i ++ > $maxTime) {
				break;
			}
		} while ( true );
		if ($i > 100 or 32 !== strlen ( $id ))
			throw new ErrorException ( 'session create failed,can\'t create session id' );
		$row = new stdClass ();
		$row->id = $id;
		$row->expire = $this->now - 1;
		$row->data = array ();
		// 内部数据
		$row->data [self::DATA_SYS] = new stdClass ();
		$row->data [self::DATA_SYS]->cookieName = $this->cookieName;
		// 用户数据
		$row->data [self::DATA_USER] = new stdClass ();
		return $row;
	}

	/**
	 * 获取客户端的一些信息
	 *
	 * @return string
	 */
	private function getClientId() {
		$t = md5 ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'] . '+' . $_SERVER ['HTTP_ACCEPT_ENCODING'] . '-' . $_SERVER ['HTTP_USER_AGENT'] );
		return $t [0] . $t [7] . $t [15] . $t [23];
	}

	/**
	 * 获取数据库对象
	 *
	 * @return \frame\lib\DB
	 */
	private function getDb() {
		return _::app()->getDb ();
	}
}