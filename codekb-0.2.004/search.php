<?php

	require_once("includes/global.php");
	
	$user = null;	
	$site = null;

	try {
		$user = new CodeKBUser();
		$site = new CodeKBSite($user);
	} catch (Exception $e) {
		CodeKBException::backtrace();
	}

	$site->registerfunction("extended", "showextended", true);
	$site->registerfunction("search", "showsearch");

	
	$site->start();
	
	$site->output();
	
	
	function showextended () {
	
		global $lang; 
		global $conf;
		global $site;
		global $user;

		$site->title($lang['search']['extended']);
	
		$form = new CodeKBForm("search.php", "search");
			
		$form->addtext("query");
		$form->addlabel("query", $lang['search']['keywords']);
		$form->addtext("author");
		$form->addlabel("author", $lang['search']['author']);
		
		$form->addmultiselect("cats", "0", $lang['category']['root']);
		 
		$tmpcat = new CodeKBCategory(0, $user);
		$array = $tmpcat->listcategories("name", 1);
		while (is_array($array) && $val = array_shift($array)) 
			$form->addmultiselect("cats", $val['id'], str_repeat("-", ($val['reclevel']) *2)." ".$val['name']);
		unset($tmpcat);
		$form->addlabel("cats", $lang['search']['category']);

		$form->addcombo("sort", $lang['sort']['sortbyname'], null, true);
		$form->addcombo("sort", $lang['sort']['sortbycreatedate']);
		$form->addcombo("sort", $lang['sort']['sortbymodifydate']);
		$form->addlabel("sort", $lang['sort']['sortby']); 		 
		
		$form->addcombo("order", $lang['sort']['ascending'], null, true);
		$form->addcombo("order", $lang['sort']['descending']);

		$form->addcombo("age", $lang['search']['1day']);
		$form->addcombo("age", $lang['search']['7days']);
		$form->addcombo("age", $lang['search']['1month']);
		$form->addcombo("age", $lang['search']['3months']);  
		$form->addcombo("age", $lang['search']['6months']);
		$form->addcombo("age", $lang['search']['1year']);
		$form->addcombo("age", $lang['search']['all'], null, true);
		
		$form->addlabel("age", $lang['search']['notolder']);

		$form->addradio("whichage", $lang['sort']['sortbycreatedate'], $lang['sort']['sortbycreatedate'], true);
		$form->addradio("whichage", $lang['sort']['sortbymodifydate'], $lang['sort']['sortbymodifydate']);

		$form->addbutton(null, $lang['search']['search']);
		$form->addbutton("cancel");
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['search']['extended']);
		
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$content = $form->head();
		$content .= $form->get("query")."<br />\n";
		$content .= $form->get("author")."<br />\n";  
		$dialogitem->push("top", $content);
		
		$dialogitem->push("content1", $form->get("cats"));
		
		$content = $form->get("sort");
		$content .= $form->get("order");
		$content .= "<br /><br />\n";
		$content .= $form->get("age");
		$content .= $form->get("whichage");
		
		$dialogitem->push("content2", $content);
		
		
		$content = "<br />\n";
		$content .= $form->tail();
		$dialogitem->push("tail", $content);
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);

		return true;
	} // showextended
	
	function showsearch() {
	
		global $lang; 
		global $conf;
		global $site;
		global $user;	

		$site->title($lang['search']['results']);
		
		$site->addfooter("search.php", "search", $lang['search']['extended']);
		
		if ($_POST['cancel'])
    		redirect("category.php");
	
		if (!$_POST['query'] && !$_POST['author'] && !$_POST['cats']) {
			$site->addcontent(notice($lang['search']['noquery']));
			return false;
		} 
	
		$start_search = microtime(true);
		$db = new CodeKBDatabase();
		$searchquery = buildsearchquery($db->type());
		try {
			$db->dosql($searchquery);
		} catch (Exception $e) {
			$site->addcontent(notice($lang['search']['wrongquery']));	
		}
		$end_search = microtime(true);
	
		$search = new CodeKBTemplate("search");
		
		$search->push("extended", url("search.php", $lang['search']['extended']));
		
		$text = phrasereplace($lang['search']['xresultsiny'], "%1%", $db->countrows());
		$text = phrasereplace($text, "%2%", round(($end_search - $start_search), 2));
		
		$search->push("info", $text);
	
		$resultcode = "";
		while ($val = $db->row()) {
		
			try {
				$tmpentry = new CodeKBEntry($val['id'], $user);
				unset($tmpentry);
			} catch (Exception $e) {
				continue;
			}
			
			$resultitem = new CodeKBTemplate("result");
			
			$content = url("entry.php?id=".$val['id'], icon($val['symbol'], $val['name']))." \n";
			$content .= url("entry.php?id=".$val['id'], htmlentities($val['name']), $val['name']); 
			$resultitem->push("title", $content);
			
			$content = $db->datetime($val['created'])." (".htmlentities($val['author']).")";
			$resultitem->push("subtitle", $content);
		
			$resultitem->push("description", htmlentities($val['description']));
			
			$resultcode .= $resultitem->__toString();
			unset($resultitem);
		
		}
		
		$search->push("results", $resultcode);
		
		$site->addcontent($search);
	
		return true;

	} // showsearch

	function buildsearchquery($type) {
	
		global $lang;
	
		$query = "SELECT DISTINCT entries.id, ".
								 "entries.name, ".
								 "entries.author, ".
								 "entries.description, ".
								 "entries.symbol, ".
								 "entries.created, ".
								 "entries.modified ".
							 		"FROM ";
	
		$keywords = preg_split ("/\s+/",trim($_POST['query']));
		$count = count($keywords);
		
		if ($type == "pgsql")
			for ($i = 0; $i < $count; $i++)
				$query .= "entries_fti i".$i.", ";	
	
		if (is_array($_POST['cats']))
			$query .= " entry_cat, ";

		$query .= "entries WHERE "; 
		
		if ($type == "pgsql")
			$query .= "entries.oid = i0.id AND ";
	
		if ($_POST['author'])
			$query .= "lower(entries.author) = lower('".CodeKBDatabase::string($_POST['author'])."') AND ";
		
		$a = 1;
		$b = count($_POST['cats']);

		while (is_array($_POST['cats']) && !is_null($val = array_shift($_POST['cats']))) {
			if ($a == 1)
				$query .= "entries.id = entry_cat.entry AND ( ";
		
			$query .= "entry_cat.cat = ".CodeKBDatabase::number($val)." ";
		
			if ($a != $b)
				$query .= "OR ";
			else
				$query .= ") AND ";
			
			$a++;
	
		}
	
		if ($_POST['age'] <> $lang['search']['all'] && $_POST['whichage']) {
			if ($_POST['whichage'] == $lang['sort']['sortbymodifydate'])
				$wage = "modified";
			else
				$wage = "created";
		
			switch ($_POST['age']) {
				case $lang['search']['1day']: 		$age = 86400;
													break;
				case $lang['search']['7days']: 		$age = 604800;
													break;											
				case $lang['search']['1month']: 	$age = 2592000;
													break;
				case $lang['search']['3months']: 	$age = 7776000;
													break;																							
				case $lang['search']['6months']: 	$age = 15552000;
													break;																							
				case $lang['search']['1year']: 		$age = 31536000;
													break;				
				default: $age = time();																			
			}
		
			$query .= "entries.".CodeKBDatabase::string($wage)." > '".CodeKBDatabase::string(date("Y-m-d H:i:s", time() - $age))."' AND "; 	 
		
		}
	
		$i = 0;
		
		if ($type == "mysql")
			$query .= "(";
					
		while (is_array($keywords) && !is_null($val = array_shift($keywords))) {
			if ($val == "*" || $val == "?")
				$val = "";

			if ($type == "pgsql") {
				$query .= ($i==0?"":"AND ")."i".$i.".string ~ lower('^".CodeKBDatabase::string($val)."') ";
				if ($i > 0)
					$query .= "AND i".($i-1).".id = i".$i.".id ";
				$i++;
			}
			
			if ($type == "mysql")
				$query .= ($i==0?"":"OR ")." entries.description LIKE '%".CodeKBDatabase::string($val)."%' OR entries.documentation LIKE '%".CodeKBDatabase::string($val)."%' ";
				
			$i++;	
		
		}
		
		if ($type == "mysql")
			$query .= ") ";
	
		$sortorder = false;
		switch ($_POST['sort']) {
			case $lang['sort']['sortbycreatedate']:	$sort = "entries.created";
														break;
			case $lang['sort']['sortbymodifydate']:	$sort = "entries.modified";
														break;			
			case $lang['sort']['sortbyname']: 	
			default: 	$sort = "entries.name";
		}
		switch ($_POST['order']) {
			case $lang['sort']['descending']:	$order = "DESC";
												break;
			case $lang['sort']['ascending']:
			default:	$order = "ASC";
		}
	
		$query .= "ORDER BY ".$sort." ".$order;
	echo $query;
		return $query;
		
	} // buildsearchquery
		
	
?>
