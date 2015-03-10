<?php
class Mutex {
	/**
	 *
	 * @return long
	 */
	final public static function create($lock = null);
	
	/**
	 *
	 * @return bool
	 */
	final public static function destroy($mutex);
	
	/**
	 *
	 * @return bool
	 */
	final public static function lock($mutex);
	
	/**
	 *
	 * @return bool
	 */
	final public static function trylock($mutex);
	
	/**
	 *
	 * @return bool
	 */
	final public static function unlock($mutex, $destroy = null);
}