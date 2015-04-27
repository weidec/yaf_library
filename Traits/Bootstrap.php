<?php
/**
 * Yaf common
 * @author admin@phpdr.net
 *
 */
trait Traits_Bootstrap {
	
	/**
	 * Common
	 *
	 * @throws Yaf_Exception_StartupError
	 */
	private function initCommon() {
		if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
			header ( 'Content-Type: text/html; charset=utf-8' );
		}
		if (! defined ( 'APP_PATH' )) {
			throw new Yaf_Exception_StartupError ( 'APP_PATH not defined' );
		}
	}
	
	/**
	 * php index.php moduleName%controller_name?key1=>value1&key2=value2
	 */
	private function initCli() {
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
