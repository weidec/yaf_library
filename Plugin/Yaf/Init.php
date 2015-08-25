<?php
/**
 * Yaf common
 * @author admin@phpdr.net
 *
 */
class Plugin_Yaf_Init extends Yaf_Plugin_Abstract {
	function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
		if (! $request->isCli ()) {
			header ( 'Content-Type: text/html; charset=utf-8' );
		}
		if (! defined ( 'APP_PATH' )) {
			throw new Yaf_Exception_StartupError ( 'APP_PATH not defined' );
		}

		new Function_Debug ();
		Helper_Debug::error2exception ();
		set_exception_handler ( 'Helper_Debug::catchException' );
		set_include_path ( get_include_path () . ':' . Yaf_Loader::getInstance ()->getLibraryPath ( true ) );

		// parse cli
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
					$request->setModuleName ( $module );
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
