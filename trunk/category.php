<?php

	require_once("includes/global.php");
	
	$id = 0;
	$category = null;
	$user = null;	
	$site = null;

	try {
		$user = new CodeKBUser();
		$site = new CodeKBSite($user);
	} catch (Exception $e) {
		CodeKBException::backtrace();
	}
	
	$site->registermain("main");
	$site->registerfunction("list", "showlisting", true);
	$site->registerfunction("sort", "showsort");
	$site->registerfunction("cookie", "showcookie");
	$site->registerfunction("new", "showinput");
	$site->registerfunction("change", "showinput");
	$site->registerfunction("delete", "showdelete");	

	$site->registervariable("id", $id);
	
	$site->start();
	
	$site->output();
	
	
	
	function main() {
		
		global $lang;
		global $id;
		global $user;
		global $site;
		global $category;
		
		if (is_bool($id) || !is_numeric($id))
			$id = 0;
		try {
			$category = new CodeKBCategory($id, $user);
		} catch (Exception $e) {
			$site->addcontent(notice($lang['category']['nosuchcat']));
			return false;
		}
		
		$site->navigation($category);
		
		return true;
		
	} // main

	function showlisting() {
		
		global $lang;
		global $user;
		global $site;
		global $conf;
		global $category;
		
		$site->title($category->name());
		
		if ($user->can("addcat", $category)) {
			$site->addmenu("category.php?id=".$category->id()."&action=new", $lang['menu']['addcat'], $lang['menu']['addcatalt']);
			$site->addfooter("category.php?id=".$category->id()."&action=new", "newcat", $lang['menu']['addcat'], $lang['menu']['addcatalt']);
		} 

		if ($user->can("addentry", $category)) {
			$site->addmenu("entry.php?cat=".$category->id()."&action=new", $lang['menu']['addentry'], $lang['menu']['addentryalt']);
			$site->addfooter("entry.php?cat=".$category->id()."&action=new", "newentry", $lang['menu']['addentry'], $lang['menu']['addentryalt']);
		}
		
		if ($category->id() != 0 && $user->can("changecat", $category))
			$site->addfooter("category.php?id=".$category->id()."&action=change", "configure", $lang['menu']['changecat'], $lang['menu']['changecatalt']); 
			
		if ($category->id() != 0 && $user->can("delcat", $category))
			$site->addfooter("category.php?id=".$category->id()."&action=delete", "delete", $lang['menu']['delcat'], $lang['menu']['delcatalt']);
		
		$site->addfooter("help.php?on=category", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		// Sorting stuff
	
		$sort = $_SESSION['sort']?$_SESSION['sort']:urldecode($_COOKIE['codekb_sort']);
		$order = $_SESSION['order']!=""?$_SESSION['order']:urldecode($_COOKIE['codekb_order']);
		$age = $_SESSION['age']!=""?$_SESSION['age']:urldecode($_COOKIE['codekb_age']);
		$whichage = $_SESSION['wage']!=""?$_SESSION['wage']:urldecode($_COOKIE['codekb_wage']);
		$entriesperpage = $_SESSION['epp']!=""?$_SESSION['epp']:urldecode($_COOKIE['codekb_epp']);
		if (!$entriesperpage) 
			$entriesperpage = $conf['layout']['entriesperpage'];
	
		switch ($sort) {
			case $lang['sort']['sortbycreatedate']:	$sort = "created";
														break;
			case $lang['sort']['sortbymodifydate']:	$sort = "modified";
														break;			
			case $lang['sort']['sortbyname']: 		
				default: 	$sort = "name";
		}
		switch ($order) {
			case $lang['sort']['descending']:	$order = "DESC";
												break;
			case $lang['sort']['ascending']:
			default:	$order = "ASC";
		}
		
		$sortorder = $sort." ".$order;
	
		if ($age <> $lang['search']['all'] && $whichage) {
			if ($whichage == $lang['sort']['sortbymodifydate'])
				$wage = "modified";
			else
				$wage = "created";
		
			switch ($age) {
				case $lang['search']['1day']:	 	$age = 86400;
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
		
			$filter = "AND entries.".CodeKBDatabase::string($wage)." > '".CodeKBDatabase::string(date("Y-m-d H:i:s", time() - $age))."' "; 	 
		
		}
	
		if ($_GET['offset'])
			$offset = $_GET['offset'];
		else
			$offset = 0;


		$list = new CodeKBTemplate("listing");
		
		// Categories		
		
		$categories = $category->listcategories();
		$i = 0;
		$count = 4;
		
		$listcode = "";
		foreach ($categories as $cat) {
		
			$listitem = new CodeKBTemplate("listitem");
			
			if ($i%$count==0)
				$listitem->push("first", true);
				
			$listitem->push("icon", url("category.php?id=".$cat['id'], icon("category", $cat['name']), $cat['name'])."\n");
			$listitem->push("name", url("category.php?id=".$cat['id'], htmlentities($cat['name']), $cat['name']));
		
			// Do we want to show subcategory and entry counts?
			if ($conf['layout']['showcounts']) {
				$listitem->push("catdescr", $lang['category']['subcats']);
				$listitem->push("entdescr", $lang['category']['entries']);
				$catcount = $category->listcategories(null, ($conf['layout']['showcountsrecursive']?"1":"0"), $cat['id']);
				
				$listitem->push("count", true);
				$listitem->push("catcount", count($catcount));
				
				$entrycount = count($category->listentries(null, null, $cat['id']));
				
				if ($conf['layout']['showcountsrecursive'])
					foreach ($catcount as $val)
						$entrycount += count($category->listentries(null, null, $val['id']));					
				$listitem->push("entrycount", $entrycount); 
			} 
		
			$listitem->push("description", htmlentities($cat['description']));

			$i++;
			if ($i%$count==0)
				$listitem->push("last", true);
			
			$listcode .= $listitem->__toString();
		
			unset($listitem);
	
		}
		
		$list->push("categories", $listcode);
		
		// Entries
		
		$entries = $category->listentries($sortorder, $filter);
		
		$list->push("changeview", url("category.php?id=".$category->id()."&action=sort", $lang['sort']['changeview'], $lang['sort']['changeviewalt']));
		
		$entriescount = count($entries);

		if ($entriesperpage == $lang['search']['all'])
			$entriesperpage = $entriescount;
		if ($offset >= $entriescount)
			$offset = $entriescount - 1;
		if (!is_numeric($offset) || $offset <= 0)
			$offset = 0;

		if ($entriescount > 0) {
		
			$pages = ceil($entriescount / $entriesperpage);
			$currentpage = ceil($offset / $entriesperpage) + 1;

	  		$pagesting = "";
	  		if ($pages > 1) {
  				// Go to the given offset
				for ($i = 0; $i < $offset*$entriesperpage-1; $i++) 
					if (is_array($entries))
						array_shift($entries);
  			
  				$pagestring .= url("category.php?id=".$category->id()."&offset=".($offset<=0?"0":$offset-1), $lang['search']['last'], $lang['search']['lastalt'])." ";
				for ($i = 1; $i <= $pages; $i++)
					if ($i == $currentpage)
						$pagestring .= $i." ";
					else
						$pagestring .= url("category.php?id=".$category->id()."&offset=".($i-1), $i, phrasereplace($lang['search']['page'], "%1%", $i))." "; 
				$pagestring .= url("category.php?id=".$category->id()."&offset=".($offset>=$entriescount-1?$entriescount-1:$offset+1), $lang['search']['next'], $lang['search']['nextalt']);  			
  			} 
				
			$list->push("pages", $pagestring);
		}		

		$i = 0;
		$count = 3;

		$listcode = "";
		foreach ($entries as $entry) {
		
			$listitem = new CodeKBTemplate("listitem");
			
			if ($i%$count==0)
				$listitem->push("first", true);
				
			$listitem->push("icon", url("entry.php?id=".$entry['id']."&cat=".$category->id(), icon($entry['symbol'], $entry['name']), $entry['name'])."\n");
			$listitem->push("name", url("entry.php?id=".$entry['id']."&cat=".$category->id(), htmlentities($entry['name']), $entry['name']));
		
			$listitem->push("description", htmlentities($entry['description']));

			$i++;
			if ($entriesperpage != $lang['search']['all'] && $i == $entriesperpage) {
				$listitem->push("last", true);
				break;
			}
			if ($i%$count==0 || $i == $entriescount )
				$listitem->push("last", true);
			
			$listcode .= $listitem->__toString();
		
			unset($listitem);
	
		}
		
		$list->push("entries", $listcode);		
		
		
		$site->addcontent($list);						
		
		return true;
	
	} // showlisting
	
	function showsort() {
		
		global $lang;
		global $user;
		global $site;
		global $conf;
		global $category;
		
		$site->title($lang['sort']['changeview']);
		
		$site->addfooter("help.php?on=category#view", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
		$sort = $_SESSION['sort']?$_SESSION['sort']:urldecode($_COOKIE['codekb_sort']);
		$order = $_SESSION['order']!=""?$_SESSION['order']:urldecode($_COOKIE['codekb_order']);
		$age = $_SESSION['age']!=""?$_SESSION['age']:urldecode($_COOKIE['codekb_age']);
		$whichage = $_SESSION['wage']!=""?$_SESSION['wage']:urldecode($_COOKIE['codekb_wage']);
		$entriesperpage = $_SESSION['epp']!=""?$_SESSION['epp']:urldecode($_COOKIE['codekb_epp']);
		if (!$entriesperpage) 
			$entriesperpage = $conf['layout']['entriesperpage'];
			
		$dialog = new CodeKBTemplate("dialog");
		
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$form = new CodeKBForm("category.php", "cookie");
		
		$form->addhidden("id", $category->id());
		
		$form->addcombo("sort", $lang['sort']['sortbyname'], null, !$sort||$sort==$lang['sort']['sortbyname']);
		$form->addcombo("sort", $lang['sort']['sortbycreatedate'], null, $sort==$lang['sort']['sortbycreatedate']);
		$form->addcombo("sort", $lang['sort']['sortbymodifydate'], null, $sort==$lang['sort']['sortbymodifydate']);
		$form->addlabel("sort", $lang['sort']['sortby']); 		 
		
		$form->addcombo("order", $lang['sort']['ascending'], null, !$order||$order==$lang['sort']['ascending']);
		$form->addcombo("order", $lang['sort']['descending'], null, $order==$lang['sort']['descending']);

		$form->addcheckbox("save", $lang['sort']['save']);

		$form->addcombo("age", $lang['search']['1day'], null, $age==$lang['search']['1day']);
		$form->addcombo("age", $lang['search']['7days'], null, $age==$lang['search']['7days']);
		$form->addcombo("age", $lang['search']['1month'], null, $age==$lang['search']['1month']);
		$form->addcombo("age", $lang['search']['3months'], null, $age==$lang['search']['3months']);  
		$form->addcombo("age", $lang['search']['6months'], null, $age==$lang['search']['6months']);
		$form->addcombo("age", $lang['search']['1year'], null, $age==$lang['search']['1year']);
		$form->addcombo("age", $lang['search']['all'], null, !$age||$age==$lang['search']['all']);
		
		$form->addlabel("age", $lang['search']['notolder']);

		$form->addradio("whichage", $lang['sort']['sortbycreatedate'], $lang['sort']['sortbycreatedate'], !$whichage||$whichage==$lang['sort']['sortbycreatedate']);
		$form->addradio("whichage", $lang['sort']['sortbymodifydate'], $lang['sort']['sortbymodifydate'], $whichage==$lang['sort']['sortbymodifydate']);
		
		$form->addcombo("epp", "5", null, $entriesperpage==5);
		$form->addcombo("epp", "10", null, $entriesperpage==10);
		$form->addcombo("epp", "15", null, $entriesperpage==15);
		$form->addcombo("epp", "20", null, $entriesperpage==20);
		$form->addcombo("epp", "25", null, $entriesperpage==25);
		$form->addcombo("epp", "30", null, $entriesperpage==30);
		$form->addcombo("epp", "50", null, $entriesperpage==50);
		$form->addcombo("epp", "100", null, $entriesperpage==100);
		$form->addcombo("epp", $lang['search']['all'], null, $entriesperpage==$lang['search']['all']);
		$form->addlabel("epp", $lang['search']['entriesperpage']);				
		
		$form->addsubmit();
		$form->addcancel();
		
		$dialogitem->push("head", $form->head());
		$content = $form->get("sort");
		$content .= $form->get("order");
		$content .= "<br /><br /><br />".$form->get("save");
		$dialogitem->push("content1", $content);
		
		$content = $form->get("age");
		$content .= $form->get("whichage");
		$dialogitem->push("content2", $content);
		
		$content = $form->get("epp");
		$dialogitem->push("content3", $content);
		
		$dialogitem->push("tail", "<br />".$form->tail());
	
		$dialog->push("legend", $lang['sort']['changeview']);
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);
		
		return true;
	
	} // showsort


	function showcookie() {

		global $lang;
		global $user;
		global $site;
		global $conf;
		global $category;
	
		if ($_POST['cancel'])
    		redirect("category.php?id=".$category->id());

		if ($_POST['save']) {
			setcookie("codekb_sort", urlencode($_POST['sort']), time()+60*60*24*365, $conf['general']['wwwpath']);
			setcookie("codekb_order", urlencode($_POST['order']), time()+60*60*24*365, $conf['general']['wwwpath']);
			setcookie("codekb_age", urlencode($_POST['age']), time()+60*60*24*365, $conf['general']['wwwpath']);
			setcookie("codekb_wage", urlencode($_POST['whichage']), time()+60*60*24*365, $conf['general']['wwwpath']);
			setcookie("codekb_epp", urlencode($_POST['epp']), time()+60*60*24*365, $conf['general']['wwwpath']);
		} else {
			$_SESSION['sort'] = $_POST['sort'];
			$_SESSION['order'] = $_POST['order'];
			$_SESSION['age'] = $_POST['age'];
			$_SESSION['wage'] = $_POST['whichage'];	
			$_SESSION['epp'] = $_POST['epp'];
		}
	
		redirect("category.php?id=".$category->id());
		
	} // showcookie
	
	function showinput () {
	
		global $lang;
		global $user;
		global $site;
		global $conf;
		global $category;
		
		$site->addfooter("help.php?on=category#add", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
		if ($site->action() == "change")
			$change = true;
		else
			$change = false;

			if ($change) 
				$site->title($lang['category']['change']);
			else
				$site->title($lang['category']['add']);

		if ( $change && ! $user->can("changecat", $category) ) { 
			$site->addcontent(notice($lang['category']['nochangeallowed']));
			return false;
		}
	
		if ( ! $change && ! $user->can("addcat", $category) ) { 
			$site->addcontent(notice($lang['category']['noaddallowed']));
			return false;
		}
	
		if ($_POST['cancel'])
    		redirect("category.php?id=".$category->id());
	
		$form = new CodeKBForm("category.php", ($change?"change":"new"));
		
		$form->addhidden("id", $category->id());				
		
		$form->addtext("name", ($change?$category->name():""));
		$form->addlabel("name", $lang['category']['name']);
		$form->setrequired("name");
		
		$form->addtext("description", ($change?$category->description():""));
		$form->addlabel("description", $lang['category']['description']);
		
		if ($change) {
			
			if ($user->can("addcat", $a=0))
				$form->addcombo("parent", "0", $lang['category']['root'], $category->parent()==0);
			
			$array = $category->listcategories("name", 1, 0);
			foreach ($array as $val) 
				if ($user->can("addcat", $val['id']))
					$form->addcombo("parent", $val['id'], str_repeat("-", ($val['reclevel']) *2)." ".$val['name'], $category->parent()==$val['id']);  
				
			$form->addlabel("parent", $lang['category']['parent']);
		}

		$form->addsubmit();
		$form->addcancel();
		
		if ($_POST['submit']) {
			
			if ($change) {
				// Change category
				if (!$form->fill())
					$site->addcontent(notice($lang['general']['missing']));
				else {
					try {
						$category->change($form->value("name"), $form->value("description"), $form->value("parent"));
						redirect("category.php?id=".$category->id());
					} catch (Exception $e) {
						switch ($e->getCode()) {
							case 1:		$site->addcontent(notice($lang['category']['childnoparent']));
										$form->setmissing("parent");
										break;
							case 2:		$site->addcontent(notice($lang['category']['duplicate']));
										$form->setmissing("name");
										break;
							default: 	$site->addcontent(notice($lang['category']['failedchange']));
						} 
					}
				}
			} else {
				// Add category
				if (!$form->fill())
					$site->addcontent(notice($lang['general']['missing']));
				else {
					try {
						$category->addsubcat($form->value("name"), $form->value("description"));
						redirect("category.php?id=".$category->id());
					} catch (Exception $e) {
						switch ($e->getCode()) {
							case 1:		$site->addcontent(notice($lang['category']['duplicate']));
										$form->setmissing("name");
										break;
							default: 	$site->addcontent(notice($lang['category']['failedadd']));
						} 
					}
				}
			}
		}

		$dialog = new CodeKBTemplate("dialog");
		if ($change) 
				$dialog->push("legend", $lang['category']['change']);
			else
				$dialog->push("legend", $lang['category']['add']);
				
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$content = $form->head();
		$content .= $form->get("name");
		$dialogitem->push("top", $content);
		
		$dialogitem->push("content1", $form->get());
		$dialogitem->push("tail", $form->tail());
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);
		
		
		return true;
		
	} // showinput
	

	function showdelete() {
	
		global $lang;
		global $user;
		global $site;
		global $conf;
		global $category;

		$site->title($lang['category']['delete']);
		
		$site->addfooter("help.php?on=category#del", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ( ! $user->can("delcat", $category) ) { 
			$site->addcontent(notice($lang['category']['nodelallowed']));
			return false;
		}

		if ($_POST['cancel'])
			redirect("category.php?id=".$category->id());

		$form = new CodeKBForm("category.php", "delete");
		$form->addhidden("id", $category->id());
		$form->addsubmit();
		$form->addcancel();
		
		if ($_POST['submit']) {
			
			try {
				$category->delete();
				redirect("category.php?id=".$category->parent());
			} catch (Exception $e) {
				if ($e->getCode() == 1)
					$site->addcontent(notice($lang['category']['faileddelrecursion']));
				else
					$site->addcontent(notice($lang['category']['faileddel']));
			}
		}
		
		$dialog = new CodeKBTemplate("dialog");
		$dialog->push("legend", $lang['category']['delete']);
		
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$content = $form->head();
		$content .= phrasereplace($lang['category']['deleteexplain'], "%1%", htmlentities($category->name()))."<br />\n";
		if (count($category->listcategories()) || count($category->listentries()))
			$content .= $lang['category']['deletenotempty']."<br />\n";
		$content .= $lang['general']['areyousure']."<br />\n";
		
		$dialogitem->push("head", $content);
		
		$dialogitem->push("tail", "<br />\n".$form->tail());
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);

		return true;
	
	} // showdelete


?>