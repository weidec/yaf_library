<?php
trait Traits_Singleton {
	private static $instances = array ();
	/**
	 *
	 * @return self
	 */
	static function getInstance() {
		$key = get_called_class ();
		if (! isset ( self::$instances [$key] )) {
			self::$instances [$key] = new static ();
		}
		return self::$instances [$key];
	}
}