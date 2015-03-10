<?php
class Worker extends Thread implements Traversable, Countable, ArrayAccess {
	
	/**
	 *
	 * @return integer
	 */
	public function getStacked();
	
	/**
	 *
	 * @return bool
	 */
	public function isShutdown();
	
	/**
	 *
	 * @return bool
	 */
	public function isWorking();
	
	/**
	 *
	 * @return bool
	 */
	public function shutdown();
	
	/**
	 *
	 * @return integer
	 */
	public function stack(Threaded &$work);
	
	/**
	 *
	 * @return integer
	 */
	public function unstack(Threaded &$work = null);
}