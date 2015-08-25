<?php
class Plugin_Yaf_Error extends Yaf_Plugin_Abstract {
	function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
		Helper_Debug::error2exception ();
	}
}