<?php
/**
 * Yaf common
 * @author admin@phpdr.net
 *
 */
class Common {
	
	/**
	 * initialization
	 *
	 * @throws Yaf_Exception_StartupError
	 */
	static function init() {
		if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
			header ( 'Content-Type: text/html; charset=utf-8' );
		}
		if (! defined ( 'APP_PATH' )) {
			throw new Yaf_Exception_StartupError ( 'APP_PATH not defined' );
		}
		self::initCli ();
		self::initError ();
		self::initException ();
	}
	
	/**
	 * throw exception
	 *
	 * @throws ErrorException
	 */
	private static function initError() {
		set_error_handler ( function ($errno, $errstr, $errfile, $errline) {
			// errors supressed by @ will cause error_reporting() always return 0
			$r = error_reporting ();
			if ($r & $errno) {
				$exception = new ErrorException ( $errstr, 0, $errno, $errfile, $errline );
				if ($errno == E_USER_ERROR || $errno == E_RECOVERABLE_ERROR) {
					throw $exception;
				}
				if (ini_get ( 'log_errors' )) {
					error_log ( $exception->__toString () . "\n" );
				}
				if (ini_get ( 'display_errors' )) {
					if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
						echo '<pre>';
					}
					echo $exception;
				}
			}
		} );
	}
	
	/**
	 * catch exception
	 */
	private static function initException() {
		set_exception_handler ( function ($exception) {
			if (ini_get ( 'display_errors' )) {
				if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
					echo '<pre>';
				}
				echo $exception;
			}
			if (ini_get ( 'log_errors' )) {
				error_log ( $exception->__toString () . "\n" );
			}
		} );
	}
	
	/**
	 * e.g.
	 * php index.php moduleName%controller_name?key1=>value1&key2=value2
	 */
	private static function initCli() {
		$request = Yaf_Dispatcher::getInstance ()->getRequest ();
		if ($request->isCli ()) {
			global $argc, $argv;
			if ($argc > 1) {
				$module = '';
				$uri = $argv [1];
				if (preg_match ( '/^[^\?]*%/i', $uri )) {
					list ( $module, $uri ) = explode ( '%', $uri, 2 );
				}
				$module = strtolower ( $module );
				$modules = Yaf_Application::app ()->getModules ();
				if (in_array ( ucfirst ( $module ), $modules )) {
					Yaf_Dispatcher::getInstance ()->getRequest ()->setModuleName ( $module );
				}
				if (false === strpos ( $uri, '?' )) {
					$args = array ();
				} else {
					list ( $uri, $args ) = explode ( '?', $uri, 2 );
					parse_str ( $args, $args );
				}
				$request->setRequestUri ( $uri );
				foreach ( $args as $k => $v ) {
					$request->setParam ( $k, $v );
				}
			}
		}
	}
}
