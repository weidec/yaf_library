<?php
class Threaded implements Traversable, Countable, ArrayAccess {
	
	/**
	 *
	 * @return array
	 */
	public function chunk($size, $preserve);
	
	/**
	 *
	 * @return integer
	 */
	public function count();
	
	/**
	 *
	 * @return bool
	 */
	public function extend($class);
	
	/**
	 *
	 * @return Threaded
	 */
	public static function from(Closure $run, Closure $construct = null, array $args = array());
	
	/**
	 *
	 * @return array
	 */
	public function getTerminationInfo();
	
	/**
	 *
	 * @return bool
	 */
	public function isRunning();
	
	/**
	 *
	 * @return bool
	 */
	public function isTerminated();
	
	/**
	 *
	 * @return bool
	 */
	public function isWaiting();
	
	/**
	 *
	 * @return bool
	 */
	public function lock();
	
	/**
	 *
	 * @return bool
	 */
	public function merge($from, $overwrite = null);
	
	/**
	 *
	 * @return bool
	 */
	public function notify();
	
	/**
	 *
	 * @return bool
	 */
	public function pop();
	
	/**
	 *
	 * @return void
	 */
	public function run();
	
	/**
	 *
	 * @return bool
	 */
	public function shift();
	
	/**
	 *
	 * @return mixed
	 */
	public function synchronized(Closure $block);
	
	/**
	 *
	 * @return bool
	 */
	public function unlock();
	
	/**
	 *
	 * @return bool
	 */
	public function wait($timeout = null);
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	public function offsetExists($offset) {
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	public function offsetGet($offset) {
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 * @param
	 *        	value
	 */
	public function offsetSet($offset, $value) {
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	public function offsetUnset($offset) {
	}
}