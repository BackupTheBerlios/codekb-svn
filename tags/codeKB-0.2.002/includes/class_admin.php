<?php

	class CodeKBAdmin {

		public function __construct(CodeKBUser &$user) {
		
			if (!$user->isadmin())
				throw new CodeKBException(__METHOD__, "admin", "noadmin");
		
		} // construct
		

		public function usersgroups($user) {

			if ($user == null)
				return array();
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT groups.name AS name, " .
					  		  "groups.id AS id ".
					  		  "FROM groups, group_user " .
					  	  	  "WHERE group_user.userid = '{$db->number($user)}' AND " .
									"groups.id = group_user.groupid ".
									"ORDER BY groups.name");
									
			return $db->all();

		} // usersgroups

		public function groupsusers($group) {

			if ($group == null)
				return array();
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT users.name AS name, " .
					  		  "users.id AS id ".
					  		  "FROM groups, users, group_user " .
					  	  	  "WHERE group_user.groupid = '{$db->number($group)}' AND " .
									"users.id = group_user.userid " .
									"ORDER BY users.name");
									
			return $db->all();

		} // groupsusers


		public function listgroups() {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT id, " .
						  	   "name " .
							   "FROM groups ".
					  		   "ORDER BY name");
					  		   
			return $db->all();
			
		} // listgroups
		
		public function listusers() {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT id, " .
						  	   "name " .
							   "FROM users ".
					  		   "ORDER BY name");
					  		   
			return $db->all();
			
		} // listusers

		public function listcategories($id = 0, $level = 1) {

			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT cat2.id AS id, " .
						  "cat2.name AS name, " .
						  "cat2.description AS description " .
						  "FROM categories cat1, categories cat2 " .
						  "WHERE cat1.id = {$db->number($id)} AND " .
								"cat2.parent = cat1.id ".
						  "ORDER BY name");
		 
			$cats = array();
	
			while ($val = $db->row()) { 
					$val['reclevel'] = $level; 
					$cats[] = $val;
					$reccats = $this->listcategories($val['id'], $level + 1);
					if (is_array($reccats))
						$cats = array_merge($cats, $reccats);
							
			}
		
			return $cats;
	
		} // listcategories
		
		public function groupname($group) {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT name ".
							   "FROM groups ".
					  		   "WHERE id = {$db->number($group)}");
					  		   
			return $db->column("name");
			
		} // groupname
		
		public function username($user) {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT name ".
							   "FROM users ".
					  		   "WHERE id = {$db->number($user)}");
					  		   
			return $db->column("name");
			
		} // username
		
		public function getrights($group, $cat) {
			
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT rights ".
							"FROM rights ".
							"WHERE groupid = {$db->number($group)} AND ".
								  "category = {$db->number($cat)}");
								  
			return $db->column("rights");
			
		} // getrights

		public function changerights($group, $cat, $rightval, $recursive = null, &$dbobj = null, $level = 0) {

			// return values
			// 1 wrong right value

			if ($rightval < 0 || $rightval > 255)
				throw new CodeKBException(__METHOD__, "admin", "wrongvalue", $rightval, 1);
	
			if (!$dbobj) {
				$db = new CodeKBDatabase();
				$first = true;
				$db->start();
				$dbobj =& $db;
			} else {
				$first = false;
				$db =& $dbobj;
			}
	
			$db->dosql("UPDATE rights " .
						"SET rights = {$db->number($rightval)} " .
						"WHERE groupid = {$db->number($group)} AND " .
				  			"category = {$db->number($cat)}", $level);
	
			if ($recursive) {

				$db->dosql("SELECT id ".
							"FROM categories ".
							"WHERE parent = {$db->number($cat)}", $level);
							
				while ($val = $db->row($level)) 
					$this->changerights($group, $val['id'], $rightval, true, $db, $level+1);
			}
	
			if ($first) {
				$db->commit();
				
				if ($db->success())
					return true;
				
				throw new CodeKBException(__METHOD__, "admin", "failedchangerights");
			}
	
		} // changerights
		
		public function changegroup($group, $name) {
			
			// return values
			// 1 duplicate group
			
			global $lang; 
			
			if ($name == $lang['admin']['nogroup'])
				throw new CodeKBException(__METHOD__, "admin", "duplicategroup", $name, 1);			
			
			$db = new CodeKBDatabase();		
			
			$db->start();
				
			$db->dosql("SELECT id " .
							"FROM groups " .
							"WHERE name = '{$db->string($name)}' AND
									id <> {$db->number($group)}");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "duplicategroup", $name, 1);
			}
	
			$db->dosql("UPDATE groups " .
							"SET name = '{$db->string($name)}' " .
					 			"WHERE id = {$db->number($group)}");
					 
			$db->commit();
	
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "admin", "failedgroupchange", $name);
			
		} // changegroup


		public function changeuser($user, $name, $pass = null) {
			
			// return values
			// 1 duplicate user
			
			global $lang; 
			
			if ($pass)
				$pass = sha1($pass);
			
			if ($name == $lang['admin']['nogroup'])
				throw new CodeKBException(__METHOD__, "admin", "duplicateuser", $name, 1);			
			
			$db = new CodeKBDatabase();		
			
			$db->start();
				
			$db->dosql("SELECT id " .
							"FROM users " .
							"WHERE name = '{$db->string($name)}' AND
									id <> {$db->number($user)}");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "duplicateuser", $name, 1);
			}
	
			$db->dosql("UPDATE users " .
							"SET name = '{$db->string($name)}' " .
								($pass?", pass = '{$db->string($pass)}' ":"").
					 			"WHERE id = {$db->number($user)}");
					 
			$db->commit();
	
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "admin", "faileduserchange", $name);
			
		} // changeuser

		public function addgroup($name, $clone = 0) {

			// return values
			// 1 duplicate group
	
			global $lang; 
			
			if ($name == $lang['admin']['nobody'])
				throw new CodeKBException(__METHOD__, "admin", "duplicategroup", $name, 1);			
			
			$db = new CodeKBDatabase();		
			
			$db->start();
				
			$db->dosql("SELECT id " .
							"FROM groups " .
							"WHERE name = '{$db->string($name)}'");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "duplicategroup", $name, 1);
			}
	
			// We need a random id
			$succ = false;
			while($succ == false) {
				$id = mt_rand();
				$db->dosql("SELECT id ".
								"FROM groups ".
								"WHERE id = {$db->number($id)}");
				if ($db->countrows() == 0)
					break;
			}
	
			$db->dosql("INSERT INTO groups (id, name) " .
							"VALUES ({$db->number($id)}, " .
					 				"'{$db->string($name)}')");
					 
			// Now clone rights to new group
	
			$db->dosql("SELECT groupid, ". 
						  "category, ".
						  "rights ".
						  "FROM rights ".
						  "WHERE groupid = {$db->number($clone)}");
						  
			while ($val = $db->row()) {
		
				$db->dosql("INSERT INTO rights ".
								"(groupid, category, rights) ".
								"VALUES ({$db->number($id)}, ".
							 			"{$db->number($val['category'])}, ".
							 			"{$db->number($val['rights'])})", 1); 
		
			}	
			
			$db->commit();
	
			if ($db->success())
				return $id;
		
			throw new CodeKBException(__METHOD__, "admin", "failedaddgroup", $name);

		} // addgroup


		public function deletegroup($group) {

			$db = new CodeKBDatabase();
			
			$db->start();
	
			$db->dosql("DELETE FROM group_user " .
						"WHERE groupid = {$db->number($group)}");
			
			$db->dosql("DELETE FROM rights " .
						"WHERE groupid = {$db->number($group)}");			
			
			$db->dosql("DELETE FROM groups " .
						"WHERE id = {$db->number($group)}");
			
			$db->commit();
	
			if ($db->success())
				return true;
				
			throw new CodeKBException(__METHOD__, "admin", "faileddelgroup");				
	
		}


		public function deleteuser($user) {

			// return values
			// 1 trying to delete admin user

			global $conf;
	
			$db = new CodeKBDatabase();
	
			$db->start();
	
			$db->dosql("SELECT id " .
							"FROM users " .
							"WHERE name = '{$db->string($conf['access']['admin'])}' AND " .
								  "id = {$db->number($user)}");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "deleteadmin", null, 1);
			}
	
			$db->dosql("DELETE FROM group_user " .
						"WHERE userid = {$db->number($user)}");
			
			$db->dosql("DELETE FROM users " .
						"WHERE id = {$db->number($user)}");
			
			$db->commit();
	
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "admin", "faileddeluser");
	
		} // deleteuser

		
		public function joingroup($user, $group) {

			// return values
			// 1 already in group
		
	
			$db = new CodeKBDatabase();
	
			$db->start();
	
			$db->dosql("SELECT userid " .
								"FROM group_user " .
								"WHERE userid = {$db->number($user)} AND " .
									  "groupid = {$db->number($group)}");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "alreadyingroup", null, 1);
			}
	
	
			$db->dosql("INSERT INTO group_user (groupid, userid) " .
							"VALUES ({$db->number($group)}, " .
									"{$db->number($user)})");
			
			$db->commit();
	
			if ($db->success())
				return true;
				
			throw new CodeKBException(__METHOD__, "admin", "failedjoin");
	
		} // joingroup


		public function partgroup($user, $group) {

			$db = new CodeKBDatabase();	
			
			$db->dosql("DELETE FROM group_user " .
							"WHERE userid = {$db->number($user)} AND " .
				  			"groupid = {$db->number($group)}");
	
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "admin", "failedpart");
	
		} // partgroup
		

	} // class CodeKBAdmin


?>
