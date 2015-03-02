<?php
/**
 * Do some initialization work.
 * @author admin@phpdr.net
 *
 */
class MyYaf_Bootstrap {
	private static $instance;
	
	/**
	 * do init
	 *
	 * @return MyYaf_Bootstrap_Init
	 */
	static function init() {
		if (isset ( self::$instance )) {
			throw new Yaf_Exception_StartupError ( 'already initialized' );
		}
		$class = get_called_class ();
		self::$instance = new static ();
	}
	
	/**
	 * do the actual work
	 */
	function __construct() {
		$methods = get_class_methods ( $this );
		foreach ( $methods as $v ) {
			if (0 === strpos ( $v, '_init' )) {
				$vName = strtolower ( substr ( $v, 5 ) );
				$this->$v ();
			}
		}
	}
	
	/**
	 * use utf-8 output
	 */
	private function _initHeader() {
		if (! Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
			header ( 'Content-Type: text/html; charset=utf-8' );
		}
	}
	
	/**
	 * check something
	 */
	private function _initCheck() {
		if (! defined ( 'APP_PATH' )) {
			throw new Yaf_Exception_StartupError ( 'APP_PATH not defined' );
		}
	}
	
	/**
	 * yaf(application) excluded
	 * config files are in app/conf/*.ini
	 */
	private function _initConfig() {
		$dir = Yaf_Application::app ()->getAppDirectory () . '/conf';
		$files = glob ( $dir . '/*.ini', GLOB_NOSORT );
		$config = new stdClass ();
		foreach ( $files as $v ) {
			$node = new Yaf_Config_Ini ( $v, Yaf_Application::app ()->environ () );
			// ignore application config
			if (isset ( $node->yaf, $node->yaf->directory ) || isset ( $node->application, $node->application->directory )) {
				continue;
			}
			$name = basename ( $v, '.ini' );
			$config->$name = $node;
		}
		Yaf_Registry::set ( 'config', $config );
	}
	
	/**
	 * php error to ErrorException, yaf catchException
	 *
	 * @throws ErrorException
	 */
	private function _initError() {
		$dispatcher = Yaf_Dispatcher::getInstance ();
		$dispatcher->throwException ( true );
		$dispatcher->catchException ( true );
		set_error_handler ( function ($errno, $errstr, $errfile, $errline) {
			// errors supressed by @ will cause error_reporting() always return 0
			$r = error_reporting ();
			if ($r != 0) {
				throw new ErrorException ( $errstr, 0, $errno, $errfile, $errline );
			}
		}, ini_get ( 'error_reporting' ) );
	}
	
	/**
	 * additional autoload
	 */
	private function _initAutoload() {
		$conf = Yaf_Application::app ()->getConfig ()->myyaf;
		if (isset ( $conf, $conf->autoload_path ) && ! empty ( $conf->autoload_path )) {
			$basePath = rtrim ( Yaf_Loader::getInstance ()->getLibraryPath ( true ), ' /' );
			$dirs = explode ( ':', $conf->autoload_path );
			foreach ( $dirs as $k => $v ) {
				if ($v == '.') {
					continue;
				}
				if (0 !== strpos ( $v, '/' )) {
					$v = $basePath . '/' . $v;
				}
				if (! in_array ( $v, $dirs )) {
					$dirs [$k] = $v;
				}
			}
			spl_autoload_register ( function ($name) use($dirs) {
				$name = str_replace ( '\\', '/', $name );
				foreach ( $dirs as $v ) {
					foreach ( array (
							'.php',
							'.class.php' 
					) as $v1 ) {
						$file = $v . '/' . $name . $v1;
						if (is_file ( $file )) {
							include $file;
						}
					}
				}
			}, true, false );
		}
	}
	
	/**
	 * additional include path
	 */
	private function _initInclude() {
		$conf = Yaf_Application::app ()->getConfig ()->myyaf;
		$basePath = rtrim ( Yaf_Loader::getInstance ()->getLibraryPath ( true ), ' /' );
		$dirs = array ();
		if (isset ( $conf, $conf->include_path ) && ! empty ( $conf->include_path )) {
			$dirs = explode ( ':', $conf->include_path );
			foreach ( $dirs as $k => $v ) {
				if (0 !== strpos ( $v, '/' )) {
					$v = $basePath . '/' . $v;
				}
				if (! in_array ( $v, $dirs )) {
					$dirs [$k] = $v;
				}
			}
		}
		if (! empty ( $dirs )) {
			set_include_path ( get_include_path () . ':' . implode ( ':', $dirs ) );
		}
	}
	
	/**
	 * moduleName|controller_name?key1=>value1&key2=value2
	 */
	private function _initCli() {
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
				$conf = Yaf_Registry::get ( 'config' );
				if (isset ( $conf->module )) {
					$conf = $conf->module;
					if (isset ( $conf->subdomain )) {
						if (in_array ( ucfirst ( $module ), $modules )) {
							$conf = $conf->subdomain->toArray ();
							if (array_key_exists ( $module, $conf )) {
								Yaf_Dispatcher::getInstance ()->getRequest ()->setModuleName ( $conf [$module] );
							}
						}
					}
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
