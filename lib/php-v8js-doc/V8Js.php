<?php
abstract class V8Js {
	const V8_VERSION = V8Js::V8_VERSION;
	const FLAG_NONE = V8Js::FLAG_NONE;
	const FLAG_FORCE_ARRAY = V8Js::FLAG_FORCE_ARRAY;
	public function __construct($object_name = "PHP", array $variables = array(), array $extensions = array(), $report_uncaught_exceptions = TRUE);
	public function executeString($script, $identifier = "V8Js::executeString()", $flags = V8Js::FLAG_NONE);
	public static function getExtensions();
	public function getPendingException();
	public static function registerExtension($extension_name, $script, array $dependencies = array(), $auto_enable = FALSE);
}