<?php

	class CodeKBSite {
		
		private $_title = "";
		
		private $_actions = array();
		
		private $_defaultaction = null;
		
		private $_currentaction = null;
		
		private $_main = null;		
		
		private $_variables = array();
		
		private $_menu = array();
		
		private $_footer = array();
		
		private $_content = "";
		
		private $_navigation = null;
		
		private $_user = null;
		
		private $_starttime = 0;
		
		public function __construct(CodeKBUser &$user) {
			
			global $conf;
			
			$this->_starttime = microtime(true); 
			
			$this->_title = $conf['general']['title'];
			$this->_user =& $user;
			
		} // constructor
		
		public function title($text) {
			
			global $conf; 
			
			if ($text)
				$this->_title = htmlentities($text)." - ".$conf['general']['title'];
			
		} // title
		
		public function action() {
			
			return $this->_currentaction;
			
		} // action
		
		public function registermain($func) {
			
			$this->_main = $func;
			
		} // registermain		

		public function registerfunction($action, $func, $default = false) {
			
			$this->_actions[$action] = $func;
			if ($default)
				$this->_defaultaction = $action;
			
		} // registerfunction
		
		public function registervariable($id, &$var) {
			
			$this->_variables[$id] =& $var;
			
		} // registervariable
		
		public function start() {
			
			if ($_POST) {
				$action = $_POST['action'];
				
				foreach ($this->_variables as &$val) {
					if (!is_null($_POST[key($this->_variables)]))
						$val = $_POST[key($this->_variables)]; 
					next($this->_variables);
				}
				
			} else {
				$action = $_GET['action'];

				foreach ($this->_variables as &$val) {
					if (!is_null($_GET[key($this->_variables)]))
						$val = $_GET[key($this->_variables)]; 
					next($this->_variables);
				}
			
			}
			
			if (!$action)
				$action = $this->_defaultaction;
				
			$this->_currentaction = $action;
			
			$ret = false;
			
			if (!is_null($this->_main)) { 
				if (is_callable($this->_main, true))
					$ret = call_user_func($this->_main);
				else
					throw new CodeKBException(__METHOD__, "general", "error");
			
				if (!$ret)
					return false;
			}
			
			$ret = false;
			
			if (!is_null($this->_actions[$action])) {
				if (is_callable($this->_actions[$action], true))
					$ret = call_user_func($this->_actions[$action]);
				else
					throw new CodeKBException(__METHOD__, "general", "error");
			} else {
				global $lang;
				$this->addcontent(notice($lang['general']['noaction']));
				return false;
			}
			
			return $ret;
			
		} // start
		
		public function navigation(&$category, &$entry = null) {
			
			global $lang;
			
			$navigate = new CodeKBTemplate("navigation");
			
			$naviid = $category->id();
			
			while ($naviid != 0) {
			
				$curcat = new CodeKBCategory($naviid, $this->_user);
				$navi[] = array("id" => $curcat->id(), "name" => $curcat->name());
				$naviid = $curcat->parent();
				unset($curcat);
			}
			
			$content = "";
			
			$content .= url("category.php", $lang['category']['root'], $lang['category']['root'])."\n";
		
			while (is_array($navi) && $val = array_pop($navi))
				$content .= " / ".url("category.php?id=".$val['id'], htmlentities($val['name']), $val['name'])."\n";

			if ($entry)
				$content .= " / <em>".url("entry.php?id=".$entry->id()."&cat=".$category->id(), htmlentities($entry->name()), $entry->name())."</em>\n";
			
			$navigate->push("navi", $content);
			$this->_navigation = $navigate;
			unset($navigate);
			
		} // navigation
		
		public function addmenu($url, $text, $alt = null) {
			
			$this->_menu[] = array($url, $text, $alt);
			
		} //addmenu
		
		public function addfooter($url, $icon, $text, $alt = null) {
			
			$this->_footer[] = array($url, $icon, $text, $alt);
			
		} //addfooter
		
		public function addcontent($content) {
			
			$this->_content .= $content->__toString();
			
		} // addcontent
		
		public function output() {
			
			global $conf;
			global $lang;
			
			$header = new CodeKBTemplate("header");
			$header->push("stylesheet", $conf['general']['stylesheet']);
			$header->push("favicon", $conf['general']['imagepath']."/codekb.ico");
			$header->push("title", $this->_title);
			$header->push("headline", $conf['general']['title']);
			
			array_unshift($this->_menu, array("home.php", $lang['menu']['home'], $lang['menu']['homealt']), 
						 array("category.php", $lang['menu']['browse'], $lang['menu']['browsealt']));
			
			if ($this->_user->isadmin())
					array_push($this->_menu, array("admin.php", $lang['menu']['admin'], $lang['menu']['adminalt']));
			
			if ($this->_user->valid())
				array_push($this->_menu, array("login.php?action=logout", $lang['menu']['logout']." [<em>".htmlentities($this->_user->name())."</em>]", $lang['menu']['logoutalt']));
			else
				array_push($this->_menu, array("login.php", $lang['menu']['login'], $lang['menu']['loginalt']));
	
			$menuitems = "";
			while ($menuentry = array_shift($this->_menu)) {
	
				$menuitems .= "\t\t".url($menuentry[0], $menuentry[1], $menuentry[2]);
				if (count($this->_menu) != 0)
					$menuitems .= " | \n";	
			}

			$search = new CodeKBForm("search.php", "search");
			$search->addtext("query");
		
			$menuitems .= $search->head();
			
			$searchstring = url("help.php", $lang['menu']['help'], $lang['menu']['helpalt'])." | \n";
			$searchstring .= url("search.php", $lang['menu']['search'], $lang['menu']['searchalt'])." \n";
			$searchstring .= $search->get("query");
			$tail = $search->tail();

			
			$menu = new CodeKBTemplate("menu");
			$menu->push("menu", $menuitems);
			$menu->push("search", $searchstring);
			$menu->push("tail", $tail);
			
			echo $header;
			echo $menu;
			if ($this->_navigation)
				echo $this->_navigation;
			echo $this->_content;
			
			$footeritems = "";
			while ($menuentry = array_shift($this->_footer)) {
	
				$footeritems .= "\t\t".url($menuentry[0], icon($menuentry[1], $menuentry[2]), $menuentry[3])." \n";
				$footeritems .= "\t\t".url($menuentry[0], $menuentry[2], $menuentry[3])."&nbsp; \n";
			}
			
			if ($conf['layout']['jumptonavigation']) {
				
				$footerform = new CodeKBForm("category.php", "list");
				$tmpcat = new CodeKBCategory(0, $this->_user);
			
				$footerform->addcombo("id", "0", $lang['category']['root']);
			
				$array = $tmpcat->listcategories("name", 1);
				foreach ($array as $val) 
					if ($this->_user->can("see", $val['id']))
						$footerform->addcombo("id", $val['id'], str_repeat("-", ($val['reclevel']) *2)." ".$val['name']);
				unset($tmpcat);
				
				$footerform->addbutton("jump", $lang['general']['go']);
				
				$navi = $footerform->head();
				$navi .= $footerform->get("id");
				$navi .= $footerform->tail();
				
				
			}
	
			$query_num = CodeKBDatabase::querycount();
			$endtime = microtime(true);
			
			$debug = "";
			if ($conf['err']['debug']) {
				CodeKBException::backtrace();
				$debug = "<span style=\"font-size: xx-small;\">(Execution time: ". round(($endtime - $this->_starttime), 4)." / ".$query_num." Queries)</span>";
			}
			
			$footer = new CodeKBTemplate("footer");
			$footer->push("footer", $footeritems);
			$footer->push("runtime", $debug);
			$footer->push("navigation", $navi);
			echo $footer;			
			
			
		} // output
	
	} // class CodeKBSite

?>
