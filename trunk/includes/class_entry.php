<?php


	class CodeKBEntry { 

		private $_id = 0;
		
		private $_user = null;
		
		private $_name = "";
		
		private $_author = "";
		
		private $_description = "";
		
		private $_symbol = "";
		
		private $_created = "";
		
		private $_modified = "";
	
		public function __construct($id, CodeKBUser &$user) {
			
			$this->_id = $id;
			$this->_user =& $user;
			
			if (!$this->_user->entrycan("see", $this))
				throw new CodeKBException(__METHOD__, "entry", "nosuchentry");

			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT name, " .						  
					  		  "author, " .
				 	  		  "description, " .
				  	  		  "symbol, " .
				  	  		  "created, " .
				  	  		  "modified " .				  		  		  
				  	  		  "FROM entries " .
				  	  		  "WHERE id = {$db->number($this->_id)}");
			
			$this->_name = $db->column("name");
			$this->_author = $db->column("author");
			$this->_description = $db->column("description");
			$this->_symbol = $db->column("symbol");
			$this->_created = $db->column("created");
			$this->_modified = $db->column("modified");
		
		} // construct
		
		public function id() {
			
			return $this->_id;
			
		} // id

		public function name() {
			
			return $this->_name;
			
		} // name
		
		public function author() {
			
			return $this->_author;
			
		} // author
		
		public function description() {
			
			return $this->_description;
			
		} // description
		
		public function documentation() {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT documentation " .				  		  		  
				  	  		  "FROM entries " .
				  	  		  "WHERE id = {$db->number($this->_id)}");
			
			return $db->column("documentation");
		
		} // documentation		
		
		public function symbol() {
			
			return $this->_symbol;
			
		} // symbol

		public function created() {
			
			return CodeKBDatabase::datetime($this->_created);
			
		} // created
		
		public function modified() {
			
			return CodeKBDatabase::datetime($this->_modified);
			
		} // modified		
		
		public function categories() {
	
			$db = new CodeKBDatabase();
			 
			$db->dosql("SELECT cat " .
						  "FROM entry_cat " .
						  "WHERE entry = {$db->number($this->_id)}");
		 
			// Give him only cats he is allowed to see
			
			$entries = array();
			
			while ($val = $db->row()) {
				if ($this->_user->can("see", $val['cat']))
					$entries[] = $val['cat'];
			}
		
			return $entries;
	
		} // categories


		public function addlink($cat) {
	
			// return values 
			// 1 entry already linked here
	
			if (!$this->_user->can("addentry", $cat))
				return false;

			$db = new CodeKBDatabase();
	
			$db->start();
	
			$db->dosql("SELECT entry " .
								"FROM entry_cat " .
								"WHERE cat = {$db->number($cat)} AND " .
									  "entry = {$db->number($this->_id)}");
	
			if ($db->countrows() > 0 ) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "entry", "duplicate", $cat, 1);
			}
	
			$db->dosql("INSERT INTO entry_cat (cat, entry) " .
							"VALUES ({$db->number($cat)}, " .
									"{$db->number($this->_id)})");

			$db->commit();
	
			if ($db->success())		
				return true;

			throw new CodeKBException(__METHOD__, "entry", "failedadd", $cat);
		
		} // addlink


		public function delink($cat) {
	
			// return values 
			// 1 failed to delete
	
			if (!$this->_user->can("delentry", $cat))
				throw new CodeKBException(__METHOD__, "entry", "failedunlink", $cat);

			$db = new CodeKBDatabase();
			
			$db->start();
	
			$db->dosql("DELETE FROM entry_cat " .
							"WHERE cat = {$db->number($cat)} AND " .
					  		"entry = {$db->number($this->_id)}");

			// Are there any other links left?
	
			$db->dosql("SELECT entry " .
								"FROM entry_cat " .
								"WHERE entry = {$db->number($this->_id)}");
	
			if ($db->countrows() == 0 ) {
				if (!$this->delete()) {
					$db->abort();
					throw new CodeKBException(__METHOD__, "entry", "faileddel", $cat, 1);
				}
			}

			$db->commit();
	
			if ($db->success())		
				return true;

			throw new CodeKBException(__METHOD__, "entry", "failedunlink", $cat);

		} // delink


		public function listfiles($sort = null) {
	
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT id, ".
							   "name, ".
							   "fs_name, ".
							   "size, ".
  							   "symbol, ".
							   "highlight ".
						   		"FROM files ".
								 	"WHERE entry = {$db->number($this->_id)}" .
							 		"ORDER BY ".($sort?$db->string($sort):"name"));
			return $db->all(); 
	
		} // listfiles


		public function addfile($file, $type, $symbol) {

			// return values 
			// 1 failed to upload

			if (!$this->_user->entrycan("changeentry", $this))
				return false;

			global $HTTP_POST_FILES;

			$fs_name = null;

			if (is_uploaded_file($HTTP_POST_FILES[$file]['tmp_name']))
				$fs_name = CodeKBFile::upload($file);
	
			if (!$fs_name)
				throw new CodeKBException(__METHOD__, "file", "uploadfailed", null, 1);
			else
				$size = $HTTP_POST_FILES[$file]['size'];

			$db = new CodeKBDatabase();
			
			$db->start();

			// We need a random id
			$succ = false;
			while($succ == false) {
				$id = mt_rand();
				$db->dosql("SELECT id ".
									"FROM files ".
									"WHERE id = {$db->number($id)}");
				if ($db->countrows() == 0)
					break;
			}
	
			$db->dosql("INSERT INTO files (id, entry, name, fs_name, size, symbol, highlight) " .
							"VALUES ({$db->number($id)}, " .
									"{$db->number($this->_id)}, " .
									"'{$db->string($HTTP_POST_FILES[$file]['name'])}', " .
									"'{$db->string($fs_name)}', " .
									"{$db->number($size)}, " .
									"'{$db->string($symbol)}', " .
									"'{$db->string($type)}')");
	
			$db->commit();
	
			if ($db->success())
				return $id;
		
			// Insert failed so remove zombie file
			$file = new CodeKBFile($id, $this->_user);
			$file->delete();
			unset($file);

			throw new CodeKBException(__METHOD__, "entry", "fileaddfailed");
		
		} // addfile


		private function delete() {

			if (!$this->_user->entrycan("delentry", $this))
				return false;
		
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT id " .
						"FROM files " .
						"WHERE entry = {$db->number($this->_id)}");
	
			while ($val = $db->row()) {
				$file = new CodeKBFile($val['id'], $this->_user);
				if (!$file->delete(true))
					return false;
				unset($file);
			}
		
			$db->start();
	
			$db->dosql("DELETE FROM entry_cat " .
						"WHERE entry = {$db->number($this->_id)}");
	
			$db->dosql("DELETE FROM entries " .
						"WHERE id = {$db->number($this->_id)}");
			
			$db->commit();
	
			if ($db->success())
				return true;
			
			throw new CodeKBException(__METHOD__, "entry", "faileddel");		

		} // delete

		public function change($name, $author, $symbol, $description, $documentation) {
	
			if (!$this->_user->entrycan("changeentry", $this))
				return false;

			if (!$author && $this->_user->name())
					$author = $this->_user->name();
	
			$db = new CodeKBDatabase();
			
			$db->dosql("UPDATE entries " .
							"SET name = '{$db->string($name)}', " .
								"author = '{$db->string($author)}', " .
								"symbol = '{$db->string($symbol)}', " .
								"description = '{$db->string($description)}', " .
								"documentation = '{$db->string($documentation)}', " .
								"modified = now()".
								"WHERE id = {$db->number($this->_id)}");
	
			if ($db->success())	{

				$this->_name = $name;
				$this->_author = $author;
				$this->_symbol = $symbol;
				$this->_description = $description;

				return true;
				
			}

			throw new CodeKBException(__METHOD__, "entry", "failedchange", $name);

		} // change
		
	} // class CodeKBEntry

?>
