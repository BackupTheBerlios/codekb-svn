<?php
	
	class CodeKBHelp {
		
		private $_file = "";
		
		private $_content = "";
		
		public function load($helpfile) {
			
			global $conf;
			
			$this->_file = $conf['general']['basepath']."/includes/help/".$helpfile.".hlp";
			
			if ( !file_exists($this->_file) )
				throw new CodeKBException(__METHOD__, "general", "error");
				
			$this->_content = file_get_contents($this->_file);
			
		} // load
		
		public function __toString() {
			
			$code = "<div id=\"help\">\n";
			$code .= parsebbcode($this->_content);
			$code .= "</div><br /><br />\n";
			
			return $code;
			
		} // toString
		
	} // class CodeKBHelp

?>
