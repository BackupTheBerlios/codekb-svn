<?php

	class CodeKBFile {
		
		private $_id = 0;
		
		private $_user = null;
		
		private $_entry = null;
		
		private $_name = "";
		
		private $_fsname = "";
		
		private $_size = 0;
		
		private $_highlight = "";
		
		private $_downloadable = false;
		
		public function __construct($id, CodeKBUser &$user) {
			
			$this->_id = $id;
			$this->_user =& $user;
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT name, ".
						      "entry, ".			
						      "fs_name, ".
							  "size, ".
							  "symbol, ".
							  "highlight ".
							  "FROM files ".
							  "WHERE id = {$db->number($this->_id)}");
								
			if ($db->countrows() == 0)
				throw new CodeKBException(__METHOD__, "file", "nosuchfile");
			
			$this->_entry = new CodeKBEntry($db->column("entry"), $this->_user);

			if ($this->_user->entrycan("download", $this->_entry))
				$this->_downloadable = true;
				
			$this->_name = $db->column("name");
			$this->_fsname = $db->column("fs_name");
			$this->_size = $db->column("size");
			$this->_symbol = $db->column("symbol");
			$this->_highlight = $db->column("highlight");				
		
		} // construct
		
		public function id() {
			
			return $this->_id;
			
		} // id
		
		public function entry() {
			
			return $this->_entry;
			
		} // entry

		public function name() {
			
			return $this->_name;
			
		} // name

		public function size() {
			
			return $this->_size;
			
		} // size
		
		public function symbol() {
			
			return $this->_symbol;
			
		} // symbol

		public function highlight() {
			
			return $this->_highlight;
			
		} // highlight
		
		public function downloadable() {
			
			return $this->_downloadable;
			
		} // downloadable

		public function delete($removeentry = false) {
			
			// return values
			// 1 failed to delete file

			if ($removeentry) {
				if (!$this->_user->entrycan("delentry", $this->_entry))
					return false;
			} else {
				if (!$this->_user->entrycan("changeentry", $this->_entry))
					return false;
			}

			if (!$this->delink()) {
				throw new CodeKBException(__METHOD__, "file", "failedunlink", null, 1);
			}
			
			$db = new CodeKBDatabase();
			
			$db->dosql("DELETE FROM files " .
							"WHERE id = {$db->number($this->_id)}");
		
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "file", "failedremove");
		
		} // delete

		
		function change($name, $highlight, $symbol, $newupload = null) {
	
			// return values 
			// 1 upload failed
	
			if (!$this->_user->entrycan("changeentry", $this->_entry))
				return false;
	
			// Do we want to exchange our file with a new one?
	
			if ($newupload) {
	
				// First upload new one and then delete the old
		
				global $HTTP_POST_FILES;

				$fs_name = null;
	
				if (is_uploaded_file($HTTP_POST_FILES[$newupload]['tmp_name']))
					$fs_name = $this->upload($newupload);
		
				if (!$fs_name)
					throw new CodeKBException(__METHOD__, "file", "failedchange", $name, 1);
				else
					$size = $HTTP_POST_FILES[$newupload]['size'];
					
			} else {
				$fs_name = $this->_fsname;
				$size = $this->_size;
			}
			
			$db = new CodeKBDatabase();
		
			$db->dosql("UPDATE files " .
						"SET name = '{$db->string($name)}', " .
						"fs_name = '{$db->string($fs_name)}', " .
						"size = {$db->number($size)}, " .
						"highlight = '{$db->string($highlight)}', " .
						"symbol = '{$db->string($symbol)}' " .
						"WHERE id = {$db->number($this->_id)}");
						
			if (!$db->success())
				throw new CodeKBException(__METHOD__, "file", "failedchange", $name);
			
			// Remove old file
			if ($newupload)
				$this->delink();
			
			$this->_name = $name;
			$this->_fs_name = $fs_name;
			$this->_size = $size;
			$this->_highlight = $highlight;
			$this->_symbol = $symbol;
		
			return true;
		
		} // change
		
		public function content() {
	
			if (!$this->_downloadable)
				return null;
			
			global $conf;
	
			if ( is_file($conf['file']['path']."/".$this->_fsname ) )
				return file_get_contents($conf['file']['path']."/".$this->_fsname);
	
			throw new CodeKBException(__METHOD__, "file", "nocontent", $this->_fsname);

		} // content
		
		
		public static function upload($file) {
	
			global $HTTP_POST_FILES;
			global $conf;
	
			$fd = $HTTP_POST_FILES[$file];
	
			if (!is_uploaded_file($fd['tmp_name']))
				throw new CodeKBException(__METHOD__, "file", "nouploadedfile");
		
			$new_file = $conf['file']['path']."/".$fd['name']."_".mt_rand();
	
			while ( file_exists($new_file) == true )
				$new_file = $conf['file']['path']."/".$fd['name']."_".mt_rand();
	
			if (! move_uploaded_file($fd['tmp_name'], $new_file) )
				throw new CodeKBException(__METHOD__, "file", "movefailed");
	
			return basename($new_file);
	
		} // upload
		
		private function delink() {
			
			global $conf;

			if ( is_file($conf['file']['path']."/".$this->_fsname ) )
				if ( unlink($conf['file']['path']."/".$this->_fsname) )
					return true;
	
			throw new CodeKBException(__METHOD__, "file", "faileddel", $this->_fsname);
			
		} // delink
		
	} // class CodeKBFile

?>
