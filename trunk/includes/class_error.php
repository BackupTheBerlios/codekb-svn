<?php

	// Error and debug stuff

	// Do we want to show php errors?
	if (!$conf['err']['phperrors'])
		error_reporting(0);
	
	class CodeKBException extends Exception {
		
		private static $_error = array();
		
		private $_method = "";
		
		public function __construct($method, $section, $string, $info = "", $code = 0) {
			
			global $lang; 
			
			$this->_method = $method;
			$message = $lang[$section][$string]."(".$info.")";
			 
			parent::__construct($message, $code);
			
			CodeKBException::$_error[] = $this->__toString();
			
		} // construct
		
		public function __toString() {
			return "<strong>{$this->_method}</strong> : <em>{$this->message}</em> [{$this->code}]";
		} // toString
		
		public static function backtrace() {
		
			foreach (CodeKBException::$_error as $val) {
				echo $val;
				echo "<br />\n";
			}
		
		} // backtrace
		
		
	} // class CodeKBException

	set_exception_handler('CodeKBException::backtrace');
	
?>
