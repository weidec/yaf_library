<?php
class Pool {
	protected $size;
	protected $class;
	protected $workers;
	protected $work;
	protected $ctor;
	protected $last;
	public function collect(Callable $collector);
	
	/**
	 *
	 * @return Pool
	 */
	public function __construct($size, $class, array $ctor = null);
	public function resize($size);
	public function shutdown();
	
	/**
	 *
	 * @return integer
	 */
	public function submit(Threaded $task);
	
	/**
	 *
	 * @return integer
	 */
	public function submitTo($worker, Threaded $task);
}