<?php
class MyYaf_View_Smarty implements Yaf_View_Interface {
	/**
	 * Smarty object
	 *
	 * @var Smarty
	 */
	protected $_smarty;
	
	/**
	 * Constructor
	 *
	 * @param string $tmplPath        	
	 * @param array $extraParams        	
	 * @return void
	 */
	public function __construct($tmplPath = null, $extraParams = array()) {
		$this->_smarty = new Smarty ();
		$this->_smarty->muteExpectedErrors ();
		if (null !== $tmplPath) {
			$this->setScriptPath ( $tmplPath );
		}
		
		foreach ( $extraParams as $key => $value ) {
			$this->_smarty->$key = $value;
		}
	}
	
	/**
	 * Set the path to the templates
	 *
	 * @param string $path
	 *        	The directory to set as the path.
	 * @return void
	 */
	public function setScriptPath($path) {
		if (is_readable ( $path )) {
			$this->_smarty->template_dir = $path;
			return;
		}
		
		throw new Yaf_Exception_LoadFailed_View ( 'Invalid path provided' );
	}
	public function getScriptPath() {
		return $this->_smarty->template_dir;
	}
	
	/**
	 * Assign variables to the template
	 *
	 * Allows setting a specific key to the specified value, OR passing
	 * an array of key => value pairs to set en masse.
	 *
	 * @see __set()
	 * @param string|array $spec
	 *        	The assignment strategy to use (key or
	 *        	array of key => value pairs)
	 * @param mixed $value
	 *        	(Optional) If assigning a named variable,
	 *        	use this as the value.
	 * @return void
	 */
	public function assign($spec, $value = null) {
		if (is_array ( $spec )) {
			$this->_smarty->assign ( $spec );
			return;
		}
		
		$this->_smarty->assign ( $spec, $value );
	}
	
	/**
	 * Processes a template and returns the output.
	 *
	 * @param string $name
	 *        	The template to process.
	 * @param array $value        	
	 * @return string The output.
	 */
	public function render($name, $value = NULL) {
		if (isset ( $value )) {
			$this->_smarty->assign ( $value );
		}
		return $this->_smarty->fetch ( $name );
	}
	
	/**
	 * output
	 *
	 * @param string $name
	 *        	The template to process.
	 * @param array $value        	
	 * @see Yaf_View_Interface::display()
	 */
	public function display($name, $value = NULL) {
		if (isset ( $value )) {
			$this->assign ( $value );
		}
		echo $this->_smarty->fetch ( $name );
	}
}