<?php
class Thread  extends Threaded  implements Countable  , Traversable  , ArrayAccess  {

	public function detach ( );

	/**
	 * @return integer
	 */
	public function getCreatorId ( );

	/**
	 * @return Thread
	 */
	public static function getCurrentThread ( );

	/**
	 * @return integer
	 */
	public static function getCurrentThreadId ( );

	/**
	 * @return integer
	 */
	public function getThreadId ( );

	/**
	 * @return mixed
	 */
	public static  function globally ( );

	/**
	 * @return bool
	 */
	public function isJoined ( );

	/**
	 * @return bool
	 */
	public function isStarted ( );

	/**
	 * @return bool
	 */
	public function join ( );

	public function kill ( );

	/**
	 * @return bool
	 */
	public function start ( $options =null );

}