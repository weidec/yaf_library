<?php
class Plugin_Yaf_Smarty extends Yaf_Plugin_Abstract {
	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
		include Yaf_Loader::getInstance ()->getLibraryPath ( true ) . '/lib/Smarty-3.1.21/libs/Smarty.class.php';
		$confSmarty = array ();
		$confSmarty ['compile_dir'] = 'cache/smarty/compile';
		$confSmarty ['cache_dir'] = 'cache/smarty/cache';
		$confSmarty ['config_dir'] = 'conf/smarty';
		$dir = Yaf_Application::app ()->getAppDirectory ();
		$templateDir = $dir . '/views';
		$module = $request->getModuleName ();
		if (isset ( $module ) && $module != 'Index') {
			$templateDir .= '/_' . strtolower ( $module );
		}
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
		$view = new Utility_YafSmarty ( $templateDir, $confSmarty );
		Yaf_Dispatcher::getInstance ()->setView ( $view );
	}
}