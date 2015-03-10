<?php
class Cond {
	/**
	 *
	 * @return bool
	 */
	final public static function broadcast($condition);
	
	/**
	 *
	 * @return long
	 */
	final public static function create();
	
	/**
	 *
	 * @return bool
	 */
	final public static function destroy($condition);
	
	/**
	 *
	 * @return bool
	 */
	final public static function signal($condition);
	
	/**
	 *
	 * @return bool
	 */
	final public static function wait($condition, $mutex, $timeout = null);
}