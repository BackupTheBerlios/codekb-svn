<?php

	// Database stuff

	class CodeKBDatabase {
		
		private static $_connection = false;
		
		private static $_global_query_count = 0;
		
		private $_results = array();
		
		private $_success = true;
		
		private $_dbtype = null;
		
		
		public function __construct() {
			
			if (!is_null($this->_connection))
        		return true;
			
			global $conf;
			
			switch ($conf['db']['type']) {
				
				case "pgsql": 	$this->_dbtype = 1;
								break;
				case "mysql": 	$this->_dbtype = 2;
								break;				
			}
			
			if ($this->_dbtype == 1) {
				$this->_connection = pg_connect(($conf['db']['host']?"host=".$conf['db']['host']:"").
												($conf['db']['port']?" port=".$conf['db']['port']:"").
												($conf['db']['name']?" dbname=".$conf['db']['name']:"").
												($conf['db']['user']?" user=".$conf['db']['user']:"").
												($conf['db']['pass']?" password=".$conf['db']['pass']:"") );
				if (!$this->_connection) 
       				throw new CodeKBException(__METHOD__, "db", "failedconnect", pg_last_notice($this->_connection));
			}
			
			if ($this->_dbtype == 2) {
				$this->_connection = mysql_connect($conf['db']['host'].($conf['db']['port']?":".$conf['db']['port']:""),
												   $conf['db']['user'], 
												   $conf['db']['pass']);
				if (!$this->_connection) 
       				throw new CodeKBException(__METHOD__, "db", "failedconnect", mysql_error($this->_connection));												   
				
				mysql_select_db($conf['db']['name'], $this->_connection); 
			}
			
			
		} // construct
		
		public function type() {

			switch ($this->_dbtype) {
				
				case 1: 	return "pgsql";
				case 2: 	return "mysql";
			}
			
		} // type
		
		public function dosql($query, $index = 0) {

			global $lang;
        
			CodeKBDatabase::$_global_query_count++;
			
			if ($this->_dbtype == 1)
				$this->_result[$index] = pg_query($this->_connection, $query);
				
			if ($this->_dbtype == 2)
				$this->_result[$index] = mysql_query($query, $this->_connection);
			
		//	echo $query."<br />";

			if (!$this->_result[$index]) {
				$this->_success = false;
				throw new CodeKBException(__METHOD__, "db", "failedquery", $query);
			}
			
			return true;
			
		} // dosql
		
		public function start() {
			
			if ($this->_dbtype == 1)
				$this->dosql("START TRANSACTION");
				
			if ($this->_dbtype == 2)
				$this->dosql("BEGIN");
			
		} // start
		
		public function commit() {
			
			if ($this->_dbtype == 1)
				$this->dosql("COMMIT TRANSACTION");
				
			if ($this->_dbtype == 2)
				$this->dosql("COMMIT");
			
		} // commit
		
		public function abort() {
			
			$this->_success = false;
			
			if ($this->_dbtype == 1)
				$this->dosql("ABORT TRANSACTION");
				
			if ($this->_dbtype == 2)
				$this->dosql("ROLLBACK");
			
		} // abort
		
		public function success() {
			
			return $this->_success;
			
		} // success

		public function row($index = 0) {
		
			if (!$this->_result[$index])
			 return array();
			 
			if ($this->_dbtype == 1)
				$array = pg_fetch_array($this->_result[$index]);
				
			if ($this->_dbtype == 2)
				$array = mysql_fetch_array($this->_result[$index]);
			
			if (is_array($array))
				return array_map(array($this,'decode'), $array);
			else
				return array();
			
		} // row
		
		public function column($column, $index= 0) {
			
			if (!$this->_result[$index])
			 return null;
		
			if ($this->countrows($index) > 0) {
				if ($this->_dbtype == 1)
					$row = pg_fetch_array($this->_result[$index], 0);
				if ($this->_dbtype == 2) {
					$row = mysql_fetch_array($this->_result[$index], MYSQL_ASSOC);
					mysql_data_seek($this->_result[$index], 0);
				}
			}
			
			return $this->decode($row[$column]);
			
		} // column
		
		
		public function all($index = 0) {

			if (!$this->_result[$index])
			 return array();
		
			if ($this->_dbtype == 1)
				$array = pg_fetch_all($this->_result[$index]);
			if ($this->_dbtype == 2) {
				while ($val = mysql_fetch_array($this->_result[$index]))
					$array[] = $val;
			}				
			if (is_array($array))
				return array_map(array($this,'decode'), $array);
			else
				return array();
			
		} // all

		
		public function countrows($index = 0) {
			
			if (!$this->_result[$index])
			 return null;
	
			if ($this->_dbtype == 1)
				return pg_num_rows($this->_result[$index]);
				
			if ($this->_dbtype == 2)
				return mysql_num_rows($this->_result[$index]);
			
		} // countrows

		private function decode($item) {
			
			if ($item == null)
				return null;

			if (is_array($item)) {
				return array_map(array($this,'decode'), $item);
			}

			$item = stripslashes($item);

			return $item;
			
		} // decode
		
		public static function string($str) {	

			if (is_null($str))
				return null;
   	     	
   	     	$str = addslashes($str);
			return $str;
        	
		} // string

		public static function number($num) {

			if (is_null($num))
				return null;
			if (is_numeric($num))
				return $num;
        	
			return null;
        
		} // number
	
		public static function datetime($timestamp) {
			
			if (is_null($timestamp))
				return null;
		
			global $conf;		

			return date($conf['layout']['dateformat'], strtotime($timestamp));

		} // sqltotime		
		
		public static function querycount() {
			
			return CodeKBDatabase::$_global_query_count;
			
		} // querycount

	} // class CodeKBDatabase

?>
