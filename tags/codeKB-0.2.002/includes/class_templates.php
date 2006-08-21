<?php

	class CodeKBTemplate {
		
		private $_content = array();
		
		private $_file = "";
		
		private $_template = "";
		
		public function __construct($templatename) {
			
			global $conf;
			
			$this->_file = $conf['general']['basepath']."/includes/templates/".$templatename.".tpl";
			
			if ( !file_exists($this->_file) )
				throw new CodeKBException(__METHOD__, "general", "error");
				
			$this->_template = file_get_contents($this->_file);
			
		} // constructor
		
		public function push($key, $content) {
			
			if (is_object($content))
				$content = $content->__toString();
				
			$this->_content[$key] = $content;
			
			
		} // push
		
		public function __toString() {
			
			$ckb = $this->_content;
			
			$code = $this->_template;
			
			// if clause and variable replacement

			$code = str_replace("\"", "\\\"", $code);
			$code = preg_replace("/<if[\s]*(.*)>/", "\"; if ( \$1 ) { \$out .= \"", $code); 
			$code = preg_replace("/<else>/", "\"; } else { \$out .= \"", $code); 
			$code = preg_replace("/<\/if>/", "\"; }; \$out .= \"", $code); 

			$out = false;
 
			if (!eval("\$out = \"".$code."\"; return true;"))
				throw new CodeKBException(__METHOD__, "general", "error");
			
			return $out;
			
		} // tostring
		
	} // class CodeKBTemplate

?>
