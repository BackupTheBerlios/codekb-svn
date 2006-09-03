<?php

// Stuff to organize categories

	class CodeKBCategory {
		
		private $_id = 0;
		
		private $_user = null;
		
		private $_name = "";
		
		private $_description = "";
		
		private $_parent = 0;
	
		public function __construct($id, CodeKBUser &$user) {
			
			$this->_id = $id;
			$this->_user =& $user;
			
			if (!$this->_user->can("see", $this ))
				throw new CodeKBException(__METHOD__, "category", "nosuchcat");
				
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT name, ".
				  			  "description, " .
				  			  "parent " .
					  	      "FROM categories " .
				  			  "WHERE id = {$db->number($this->_id)}");
			
			$this->_name = $db->column("name");
			$this->_description = $db->column("description");
			$this->_parent = $db->column("parent");
		
		} // construct
		
		public function id() {
			
			return $this->_id;
				
		} // id
		
		public function name() {
			
			global $lang;
			
			if ($this->_id == 0)
				return $lang['category']['root'];
			else
				return $this->_name;
				
		} // name
		
		public function description() {
			
			return $this->_description;
				
		} // description
		
		public function parent() {
			
			return $this->_parent;
				
		} // parent
		
		public function listentries($sort = false, $filter = false, $id = null) {
			
			if (is_null($id))
				$id = $this->_id;
				
			// Filter: We allow including a custom where clause - maybe dangerous...
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT entries.id AS id, " .
						  "entries.name AS name, " .						  
						  "entries.symbol AS symbol, " .						  
				 		  "entries.author AS author, " .
				  		  "entries.description AS description " .
				  		  "FROM entries, entry_cat " .
				  		  "WHERE entry_cat.cat = {$db->number($id)} AND " .
				  				"entry_cat.entry = entries.id ".
				  				$filter.
						  		"ORDER BY ".($sort?$db->string($sort):"name"));				  				

			$entries = array();
			
			while ($val = $db->row())
				$entries[] = $val;

			return $entries;
	
		} // listentries


		public function listcategories($sort = false, $recursive = 0, $id = null) {

			if (is_null($id))
				$id = $this->_id;
				
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT cat2.id AS id, " .
						  "cat2.name AS name, " .
						  "cat2.description AS description " .
						  "FROM categories cat1, categories cat2 " .
						  "WHERE cat1.id = {$db->number($id)} AND " .
								"cat2.parent = cat1.id ".
						  "ORDER BY ".($sort?$db->string($sort):"name"));
		 
			// Give him only cats he is allowed to see
			
			$cats = array();
	
			while ($val = $db->row()) 
				if ($this->_user->can("see", $val['id'])) {
					if ($recursive > 0)
						$val['reclevel'] = $recursive; 
					$cats[] = $val;
					if ($recursive > 0) {
				
						$reccats = $this->listcategories($sort, $recursive + 1, $val['id']);
						if (is_array($reccats))
							$cats = array_merge($cats, $reccats);
					}		
				}
		
			return $cats;
	
		} // listcategories


		public function addentry($name, $author, $symbol, $description, $documentation) {
	
			if (!$this->_user->can("addentry", $this))
				return false;

			if (!$author && $this->_user->name())
				$author = $this->_user->name();
	
			$db = new CodeKBDatabase();
	
			$db->start();
	
			// We need a random id
			$succ = false;
			while($succ == false) {
				$id = mt_rand();
			
				$db->dosql("SELECT id ".
									"FROM entries ".
									"WHERE id = {$db->number($id)}");
			
				if ($db->countrows() == 0)
					break;
			}
			
			$db->dosql("INSERT INTO entries (id, name, symbol, author, description, documentation, created) " .
						"VALUES ({$db->number($id)}, " .
								"'{$db->string($name)}', " .
								"'{$db->string($symbol)}', " .						
								"'{$db->string($author)}', " .
								"'{$db->string($description)}', " .
								"'{$db->string($documentation)}', " .
								"now())");

			$db->dosql("INSERT INTO entry_cat (cat, entry) " .
							"VALUES ({$db->number($this->_id)} ," .
						 			"{$db->number($id)})");
						
			$db->commit();
	
			if ($db->success())		
				return $id;
				
				throw new CodeKBException(__METHOD__, "entry", "failedadd");

		} // addentry


		public function addsubcat($name, $description) {

			// return values
			// 1 duplicate category
	
			if (!$this->_user->can("addcat", $this))
				return false;
		
			$db = new CodeKBDatabase();
			
			$db->start();
			$db->dosql("SELECT id " .
								"FROM categories " .
								"WHERE parent = {$db->number($this->_id)} AND " .
									  "name = '{$db->string($name)}'");
									  
			if ($db->countrows() > 0 ) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "category", "duplicate", $name, 1);
			}				
	
			// We need a random id
			$succ = false;
			while($succ == false) {
				$id = mt_rand();
				
				$db->dosql("SELECT id ".
									"FROM categories ".
									"WHERE id = {$db->number($id)}");
				if ($db->countrows() == 0)
					break;
			}	

			$db->dosql("INSERT INTO categories (id, name, description, parent) " .
							"VALUES ({$db->number($id)}, " .
									"'{$db->string($name)}', " .
									"'{$db->string($description)}', " .
									"{$db->number($this->_id)})");

			$db->dosql("SELECT groupid, rights " .
							"FROM rights " .
							"WHERE category = {$db->number($this->_id)}");

			// Clone access rights from parent category 				
	
			while ($val = $db->row())
				$db->dosql("INSERT INTO rights (groupid, category, rights) " .
								"VALUES ({$db->number($val['groupid'])}, " .
										"{$db->number($id)}, " .
										"{$db->number($val['rights'])})", 1);
	
			$db->commit();
	
			if ($db->success())
				return $id;

			$db->abort();
			throw new CodeKBException(__METHOD__, "category", "failedadd", $name);

		} // addsubcat
		

		public function change($name, $description, $parent = -1) {
	
			// return values
			// 1 child cannot be parent
			// 2 duplicate category
	
			if (!$this->_user->can("changecat", $this))
				return false;
	
			$db = new CodeKBDatabase;
	
			$db->start();
		
			if ($parent == -1) {
				$db->dosql("SELECT parent " .
								"FROM categories " .
								"WHERE id = {$db->number($this->_id)}");
				$parent = $db->column("parent");
			} else {
				$i = $parent; 
		
				if ($i == $this->_id)
					throw new CodeKBException(__METHOD__, "category", "childnoparent", $name, 1);
				
				while ($i != 0) {	
					$db->dosql("SELECT parent ".
									"FROM categories ".
									"WHERE id = {$db->number($i)}");
					
					$i = $db->column("parent");
					
					if ($i == $this->_id) {
						$db->abort();
						throw new CodeKBException(__METHOD__, "category", "childnoparent", $name, 1);
					}
				}
			}

			$db->dosql("SELECT id " .
							"FROM categories " .
							"WHERE parent = {$db->number($parent)} AND " .
								  "id <> {$db->number($this->_id)} AND " .
								  "name = '{$db->string($name)}'");
	
			if ($db->countrows() > 0 ) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "category", "duplicate", $name, 2);
			}
	
			$db->dosql("UPDATE categories " .
						"SET name = '{$db->string($name)}', " .
						"description = '{$db->string($description)}', " .
						"parent = {$db->number($parent)} " .
						"WHERE id = {$db->number($this->_id)}");
	
			$db->commit();
	
			if ($db->success()) {
				
				$this->_name = $name;
				$this->_description = $description;
				if ($parent != -1)
					$this->_parent = $parent;
				
				return true; 
			}
			
			$db->abort();
			throw new CodeKBException(__METHOD__, "category", "failedchange", $name);
	
		} // change

		public function delete(&$dbobj = null, $level = 0) {
	
			// return values
			// 1 aborted recursion
			
			// Are we at the first recursion level?
			if (is_null($dbobj)) {
				$first = true;
				$db = new CodeKBDatabase();
				$db->start();
				$dbobj =& $db;
			} else {
				$first = false;
				$db =& $dbobj;
			}
	
			if ($this->_id == 0 || !$this->_user->can("delcat", $this)) {
				$db->abort();
				return false;
			}

			$entries = $this->listentries();
			
			foreach($entries as $val) {
				$tmpentry = new CodeKBEntry($val['id'], $this->_user);
				$tmpentry->delink($this->_id);
				unset($tmpentry);
			}

			$db->dosql("SELECT id " .
						"FROM categories " .
						"WHERE parent = {$db->number($this->_id)}", $level);
			
			while ($val = $db->row($level)) {

				$subcat = new CodeKBCategory($val['id'], $this->_user);
				if (!$subcat->delete($db, $level+1)) {
					$db->abort();
					throw new CodeKBException(__METHOD__, "category", "faileddel", null, 1);
				}
				unset($subcat);
			}
			
			$db->dosql("DELETE FROM rights " .
							"WHERE category = {$db->number($this->_id)}", $level);
			
			$db->dosql("DELETE FROM categories " .
							"WHERE id = {$db->number($this->_id)}", $level);

			if ($first)
				$db->commit();
			else
				return $db->success();
		
			if ($db->success())
				return true;
				
			$db->abort();
			throw new CodeKBException(__METHOD__, "category", "faileddel");

		} // delete

	} // class CodeKBCategory

?>
