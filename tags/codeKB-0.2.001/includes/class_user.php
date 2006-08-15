<?php

	// Access stuff


	class CodeKBUser {

		private $_valid = false;
		
		private $_cache = array();
		
		private $_id = null;
		
		private $_name = null;
		
		
		public function __construct() {
		
			session_start();
			
			if (!$_SESSION['user'] || !$_SESSION['id']) {

				if ($_COOKIE['codekb_user'] && $_COOKIE['codekb_id']) {
			
					$succ = true;
					if (!session_name('codeKB')) $succ = false;
					if (!$_SESSION['user'] = gzuncompress(urldecode($_COOKIE['codekb_user']))) $succ = false;
					if (!$_SESSION['id'] = gzuncompress(urldecode($_COOKIE['codekb_id']))) $succ = false;
		
					if (!$succ)
						throw new CodeKBException(__METHOD__, "admin", "sessionfailed");
				
				}
			}
			
			$user = $_SESSION['user'];
			$pass = $_SESSION['id'];

			$db = new CodeKBDatabase();

			$db->dosql("SELECT id " .
							"FROM users " .
							"WHERE name = '{$db->string($user)}' AND " .
								  "pass = '{$db->string($pass)}'");

			if ($db->countrows() != 1)
				return false;
			
			$this->_name = $user;
			$this->_id = $db->column("id");
	
			$this->_valid = true;		
				return true;
			
		
		} // construct

		public function name() {

			return $this->_name;
	
		} // name
		
		public function id() {

			return $this->_id;
	
		} // id

		public function valid() {

			return $this->_valid;
	
		} // valid


		public function login($user, $pass) {
	
			// Policy: When trying to login again kill the old session before
		
			$this->logout();
			
			session_start();
			
			$pass = sha1($pass);			

			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT id " .
							"FROM users " .
							"WHERE name = '{$db->string($user)}' AND " .
								  "pass = '{$db->string($pass)}'");

			if ($db->countrows() != 1)
				throw new CodeKBException(__METHOD__, "login", "failed");
			
			$succ = true;
			if (!session_name('codeKB')) $succ=false;
			if (!$_SESSION['user'] = $user) $succ=false;
			if (!$_SESSION['id'] = $pass) $succ=false;
	
			if (!$succ)
				throw new CodeKBException(__METHOD__, "admin", "sessionfailed");
				
			$this->_name = $user;
			$this->_id = $db->column("id");
	
			$this->_valid = true;		
			
			return true;
								
		} // login

		public function cookie() {

			if ($this->_valid) {
	
				global $conf;
				setcookie("codekb_user", urlencode(gzcompress($_SESSION['user'])), time()+60*60*24*365, $conf['general']['wwwpath']);
				setcookie("codekb_id", urlencode(gzcompress($_SESSION['id'])), time()+60*60*24*365, $conf['general']['wwwpath']);
			}
	
		} // cookie

		public function logout() {
	
			global $conf;

			$this->_valid = false;  
		
			unset($_SESSION);
			session_destroy();
			setcookie("codekb_user", " ", 1, $conf['general']['wwwpath']);
			setcookie("codekb_id", " ", 1, $conf['general']['wwwpath']);
			
		} // logout


		public function isadmin() {
	
			global $conf;

			if (count($conf['access']['admin']) == 0 || in_array($this->name(), $conf['access']['admin']))
				return true;
		
			return false;
	
		} // isadmin
		
		public function register($name, $pass) {

			// return values
			// 1 duplicate user
			
			$pass = sha1($pass);

			global $lang;
			
			if ($name == $lang['admin']['nobody'])
				throw new CodeKBException(__METHOD__, "admin", "duplicateuser", $name, 1);
	
			$db = new CodeKBDatabase();	
	
			$db->start();
	
			$db->dosql("SELECT id " .
							"FROM users " .
							"WHERE name = '{$db->string($name)}'");
								
			if ($db->countrows() > 0) {
				$db->abort();
				throw new CodeKBException(__METHOD__, "admin", "duplicateuser", $name, 1);
			}
	
			// We need a random id
			$succ = false;
			while($succ == false) {
				$id = mt_rand();
				$db->dosql("SELECT id ".
								"FROM users ".
								"WHERE id = {$db->number($id)}");
				if ($db->countrows() == 0)
					break;
			}
			
			$db->dosql("INSERT INTO users (id, name, pass) " .
							"VALUES ({$db->number($id)}, " .
									"'{$db->string($name)}', " .
									"'{$db->string($pass)}')");
			
			$db->commit();
	
			if ($db->success())
				return true;
		
			throw new CodeKBException(__METHOD__, "admin", "failedadduser", $name);

		} // register
		

		private function getrights($cat, $cache = true) {
	
			// 1 see
			// 2 download
			// 4 change entries
			// 8 add entries
			// 16 delete entries
			// 32 change categories
			// 64 add categories
			// 128 delete categories
	
			// First look if we have these rights in the cache already
	
			if ($cache && !is_null($this->_cache[$cat]))
				return $this->_cache[$cat];
				
			$rights = array();
	
			// Get the maximum rights from given user's groups
									
			$db = new CodeKBDatabase();
			
			$db->dosql("SELECT max(rights.rights) AS rightval " .
							"FROM rights, users, categories, groups, group_user " .
							"WHERE (".($this->_name!=null?"users.name = '{$db->string($this->_name)}' OR":"")." users.name is null) AND " .
								"users.id = group_user.userid AND " .
								"groups.id = group_user.groupid AND " .
								"categories.id = {$db->number($cat)} AND " .
								"categories.id = rights.category AND " .
								"groups.id = rights.groupid");
	
			$val = $db->column("rightval");
	
			if ($val >= 128) {
				$val -= 128;
				$rights[] = "delcat";
			}
			if ($val >= 64) {
				$val -= 64;
				$rights[] = "addcat";
			}
			if ($val >= 32) {
				$val -= 32;
				$rights[] = "changecat";
			}
			if ($val >= 16) {
				$val -= 16;
				$rights[] = "delentry";
			}
			if ($val >= 8) {
				$val -= 8;
				$rights[] = "addentry";
			}			
			if ($val >= 4) {
				$val -= 4;
				$rights[] = "changeentry";
			}
			if ($val >= 2) {
				$val -= 2;
				$rights[] = "download";
			}
			if ($val == 1) {
				$val -= 1;
				$rights[] = "see";
			}
	
			global $conf;
	
			// In case we want to cache the access rights
			if ($conf['perf']['rightscache'] > 0) {
				$this->_cache[$cat] = $rights;
				if (count($this->_cache) > $conf['perf']['rightscache'])
					array_shift($this->_cache);
			}
			return $rights; 
	
		} // getrights


		public function can($what, &$cat, $cache = true) {

			if (is_null($cat))
				return false;
			
			if (is_object($cat))
				$id = $cat->id();
			else
				$id = $cat;
				
			$rights = $this->getrights($id, $cache);
			
			if (is_array($rights) && in_array($what, $rights))
				return true;

			return false;
	
		} // can

		function entrycan($what, &$entry, $cache = true) {
	
			// Do something for a bit more performance:
			// Cache the last request because we often query 
			// just one entry per page 
			static $lastentry;
			static $lastcat;
			
			if (is_null($entry))
				return false;
			
			if (is_object($entry))
				$id = $entry->id();
			else
				$id = $entry;
	
			if ($cache && $id == $lastentry) 
				$array = $lastcat;
			else {
				$db = new CodeKBDatabase();
				
				$db->dosql("SELECT cat " .
			 				"FROM entry_cat " .
							"WHERE entry = {$db->number($id)}");

				$lastentry = $id;
				$array = $db->all();
				$lastcat = $array; 
			}
	
			$succ = false;						
			while (is_array($array) && $val = array_pop($array)) {
		
				if ( $this->can($what, $val['cat'], $cache) ) {
					$succ = true;
					break;
				}
			}
	
			if ($succ)
				return true;
		
			return false;	

		} // entrycan
		
	} // class CodeKBUser

?>
