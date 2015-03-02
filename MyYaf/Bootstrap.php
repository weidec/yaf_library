<?php
/**
 * Do some initialization work.
 * Must be called in subclass of Yaf_Bootstrap_Abstract and should be called in first _init*.
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
	 * init php config
	 */
	private function _initConfigPhp() {
		$config = Yaf_Registry::get ( 'config' );
		if (! property_exists ( $config, 'php' )) {
			return;
		}
		$dir = realpath ( Yaf_Application::app ()->getAppDirectory () );
		$php = $config->php;
		$php = $php->toArray ();
		$phpFinal = array ();
		foreach ( $php as $k => $v ) {
			if (is_array ( $v )) {
				foreach ( $v as $k1 => $v1 ) {
					if (is_array ( $v1 )) {
						foreach ( $v1 as $k2 => $v2 ) {
							$phpFinal [$k . '.' . $k1 . '.' . $k2] = $v2;
						}
					} else {
						$phpFinal [$k . '.' . $k1] = $v1;
					}
				}
			} else {
				$phpFinal [$k] = $v;
			}
		}
		$pathAppend = array (
				'error_log',
				'session.save_path' 
		);
		foreach ( $phpFinal as $k => $v ) {
			if (in_array ( $k, $pathAppend )) {
				$v = $dir . '/' . $v;
			}
			ini_set ( $k, $v );
		}
		if (Yaf_Dispatcher::getInstance ()->getRequest ()->isCli ()) {
			ini_set ( 'display_errors', true );
		}
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
			$basePath = rtrim ( Yaf_Loader::getInstance ()->getLibraryPath ( true ), ' /' ) . '/lib';
			$dirs = explode ( ':', $conf->autoload_path );
			foreach ( $dirs as $k => $v ) {
				if (0 !== strpos ( $v, '/' )) {
					$dirs [$k] = $basePath . '/' . $v;
				}
			}
			$dirs = array_unique ( $dirs );
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
		$basePath = rtrim ( Yaf_Loader::getInstance ()->getLibraryPath ( true ), ' /' ) . '/lib';
		$dirs = array ();
		if (isset ( $conf, $conf->include_path ) && ! empty ( $conf->include_path )) {
			$dirs = explode ( ':', $conf->include_path );
			foreach ( $dirs as $k => $v ) {
				if (0 !== strpos ( $v, '/' )) {
					$dirs [$k] = $basePath . '/' . $v;
				}
			}
		}
		$dirs [] = $basePath;
		$include_path = implode ( ':', $dirs );
		$path = get_include_path () . ':' . $include_path;
		$path = explode ( ':', $path );
		$path = array_unique ( $path );
		$path = implode ( ':', $path );
		set_include_path ( trim ( $path, ':' ) );
	}
	
	/**
	 * localNamespace
	 */
	private function _initLocalNamespace() {
		Yaf_Loader::getInstance ()->registerLocalNamespace ( array (
				'Controller',
				'Model' 
		) );
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
	
	/**
	 * init route on basic of app/conf/route.ini
	 */
	private function _initRoute() {
		$config = Yaf_Registry::get ( 'config' );
		if (! isset ( $config->route ) || ! isset ( $config->route->routes )) {
			return;
		}
		$routes = $config->route->routes;
		Yaf_Dispatcher::getInstance ()->getRouter ()->addConfig ( $routes );
	}
	
	/**
	 * smarty
	 */
	private function _initView() {
		$conf = Yaf_Application::app ()->getConfig ()->myyaf;
		if (isset ( $conf, $conf->view )) {
			$conf = $conf->view;
		}
		$dispatcher = Yaf_Dispatcher::getInstance ();
		if (isset ( $conf->enable )) {
			if ($conf->enable) {
				$dispatcher->enableView ();
			} else {
				$dispatcher->disableView ();
			}
		}
		if (isset ( $conf->type ) && strtolower ( $conf->type ) == 'smarty') {
			if (! isset ( $conf->smarty )) {
				$confSmarty = array ();
				$confSmarty ['compile_dir'] = 'cache/smarty/compile';
				$confSmarty ['cache_dir'] = 'cache/smarty/cache';
				$confSmarty ['config_dir'] = 'conf/smarty';
			} else {
				$confSmarty = $conf->smarty->toArray ();
			}
			$dir = Yaf_Application::app ()->getAppDirectory ();
			$templateDir = $dir . '/views';
			// module view path. until yaf 2.3.3, defaultModule doesn't work, defaultModule will always be Index.
			$moduleDefault = 'Index';
			$yaf = Yaf_Application::app ()->getConfig ();
			if (isset ( $yaf->dispatcher, $yaf->dispatcher->defaultModule )) {
				$moduleDefault = $yaf->dispatcher->defaultModule;
			}
			$module = $dispatcher->getRequest ()->getModuleName ();
			if (isset ( $module ) && $module != $moduleDefault) {
				$templateDir .= '/_' . strtolower ( $module );
			}
			$confSmarty ['template_dir'] = $templateDir;
			$keyAppend = array (
					'compile_dir',
					'cache_dir',
					'config_dir' 
			);
			foreach ( $confSmarty as $k => $v ) {
				if (in_array ( $k, $keyAppend )) {
					$confSmarty [$k] = $dir . '/' . $v;
				}
			}
			$view = new MyYaf_View_Smarty ( null, $confSmarty );
			$dispatcher->setView ( $view );
		}
	}
}
