<?php
class Plugin_Yaf_Error extends Yaf_Plugin_Abstract {
	function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
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
					if (! $request->isCli ()) {
						echo '<pre>';
					}
					echo $exception;
				}
			}
		} );
	}
}