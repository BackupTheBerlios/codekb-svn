<?php

	require_once("includes/global.php");
	
	$entryid = null;
	$catid = null;
	$category = null;
	$entry = null;
	$user = null;	
	$site = null;

	try {
		$user = new CodeKBUser();
		$site = new CodeKBSite($user);
	} catch (Exception $e) {
		CodeKBException::backtrace();
	}
	
	$site->registermain("main");
	$site->registerfunction("show", "showentry", true);
	$site->registerfunction("new", "showinput");
	$site->registerfunction("modify", "showinput");
	$site->registerfunction("change", "showchange");
	$site->registerfunction("link", "showlinks");
	$site->registerfunction("files", "showfiles");	

	$site->registervariable("id", $entryid);
	$site->registervariable("cat", $catid);
	
	
	$site->start();
	
	$site->output();
	
	
	
	function main() {
		
		global $lang;
		global $entryid;
		global $catid;		
		global $user;
		global $site;
		global $category;
		global $entry;
		
		if (!is_bool($catid) && is_numeric($catid))
			try {
				$category = new CodeKBCategory($catid, $user);
			} catch (Exception $e) {
				$site->addcontent(notice($lang['category']['nosuchcat']));
				return false;
			}

		if (!is_bool($entryid) && is_numeric($entryid))
			try {
				$entry = new CodeKBEntry($entryid, $user);
			} catch (Exception $e) {
				$site->addcontent(notice($lang['entry']['nosuchentry']));
				return false;
			}
		
		if ($category) {
			$site->navigation($category, $entry);
			$catid = $category->id();
			$site->catid($catid);
		}
			
		return true;
		
	} // main
	
	function showentry () {
		
		global $lang;
		global $user;
		global $site;
		global $catid;
		global $entry;
		
		$site->title($entry->name());
	
		if ($user->entrycan("changeentry", $entry)) {
			$site->addmenu("entry.php?id=".$entry->id()."&cat=".$catid."&action=change", $lang['menu']['modifyentry'], $lang['menu']['modifyentryalt']);
			$site->addfooter("entry.php?id=".$entry->id()."&cat=".$catid."&action=modify", "configure", $lang['menu']['configureentry'], $lang['menu']['configureentryalt']);
			$site->addfooter("entry.php?id=".$entry->id()."&cat=".$catid."&action=files", "files", $lang['menu']['attach'], $lang['menu']['attachalt']);
		} 

		if ($user->entrycan("addentry", $entry) || $user->entrycan("delentry", $entry))
			$site->addfooter("entry.php?id=".$entry->id()."&cat=".$catid."&action=link", "links", $lang['menu']['linkentry'], $lang['menu']['linkentryalt']);
			
		$site->addfooter("help.php?on=entry", "help", $lang['menu']['help'], $lang['menu']['helpalt']);			
		
		$entrytpl = new CodeKBTemplate("entry");
		
		$entrytpl->push("icon", icon($entry->symbol(), $entry->name())." "); 
		$entrytpl->push("name", htmlentities($entry->name()));
		
		$content = $lang['entry']['author'].": <em>".htmlentities($entry->author())."</em> | ";
		$content .= $lang['entry']['createdate'].": <em>".$entry->created()."</em> | ";
		$content .= $lang['entry']['modifydate'].": <em>".($entry->modified()?$entry->modified():$lang['general']['never'])."</em>\n";
		
		$entrytpl->push("subheader", $content);
		$entrytpl->push("description", htmlentities($entry->description()));
		$entrytpl->push("documentation", parsebbcode($entry->documentation()));

		if ( $user->entrycan("download", $entry) ) {
		
			$filesofentry = $entry->listfiles();
			if (count($filesofentry)) {
				$entrytpl->push("attachments", $lang['entry']['attachments']);

				$i = 0;
				$count = 3;
				$listcode = "";
				foreach ($filesofentry as $val) {
				
					$listitem = new CodeKBTemplate("listitem");
			
					if ($i%$count==0)
						$listitem->push("first", true);
					
					$listitem->push("icon", url("file.php?id=".$val['id']."&cat=".$catid, icon($val['symbol'], $val['name']), $val['name']));
					$content = url("file.php?id=".$val['id']."&cat=".$catid, $val['name']);
					$content .= " (";
					$size = $val['size'];
					$unit = "b";
					if ( $size > 1024 ) { $size /= 1024; $unit = "kb"; }
					if ( $size > 1024 ) { $size /= 1024; $unit = "mb"; }
					$content .= round($size).$unit.")";
					$listitem->push("name", $content);
					$i++;
					if ($i%$count==0)
						$listitem->push("last", true);
					
					$listcode .= $listitem->__toString();
					
					unset($listitem);  
				}
				$entrytpl->push("files", $listcode);	
			} 	
		}
	
		$site->addcontent($entrytpl);
		
		return true;
		
	} // showentry
	
	function showinput() {

		global $lang;
		global $conf;
		global $user;
		global $site;
		global $catid;
		global $category;
		global $entry;
		
		$site->addfooter("help.php?on=entry#add", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
		if ($site->action() == "modify") {
			$change = true;
			$site->title($lang['entry']['change']);
		} else {
			$change = false;
			$site->title($lang['entry']['add']);
		}

    	if ( ! $change && ! $user->can("addentry", $category )) { 
			$site->addcontent(notice($lang['entry']['noaddallowed']));
			return false;
    	}

		if ( $change && ! $user->entrycan("changeentry", $entry) ) { 
			$site->addcontent(notice($lang['entry']['nochangeallowed']));
			return false;
		}
	
		if ($_POST['cancel'])
			if ($change)
				redirect("entry.php?id=".$entry->id()."&cat=".$catid);
			else
				redirect("category.php?id=".$catid);
			
		$form = new CodeKBForm("entry.php", ($change?"modify":"new"));
		
		$form->addhidden("cat", $catid);
		if ($change)
			$form->addhidden("id", $entry->id());
		
		$form->addtext("title", ($change?$entry->name():""));
		$form->addlabel("title", $lang['entry']['name']);
		$form->setrequired("title");
		
		$form->addtext("author", ($change?$entry->author():""));
		$form->addlabel("author", $lang['entry']['author']);
		
		$form->addtext("description", ($change?$entry->description():""));
		$form->addlabel("description", $lang['entry']['description']);

		$db = new CodeKBDatabase();
		$db->dosql("SELECT name, symbol ".
							"FROM symbols ".
							"WHERE symbol LIKE 'type_%'");
			
		if ($entry)
			$symbol = $entry->symbol();
		else
			$symbol = false;
		while ($val = $db->row())
			$form->addradio("symbol", $val['name'], icon($val['name'], $val['name']), !$change&&$val['name']=="Unkown"||$val['name']==$symbol, false); 
				
		$form->addtextarea("documentation", ($change?$entry->documentation():""));
		$form->addlabel("documentation", $lang['entry']['documentation']." (".url("help.php?on=bbcode", $lang['entry']['bbcode'], null, true).")");
		
		$form->addbutton("submit");
		$form->addbutton("preview", $lang['general']['preview']);
		$form->addbutton("cancel");
		
		if ($_POST['submit'] || $_POST['preview']) {
			$fill = $form->fill();
			if (!$fill)
					$site->addcontent(notice($lang['general']['missing']));
		}
			
		if ($_POST['submit'] && $fill) {
			
			if ($change) {
				// Change the entry
				try {
					$entry->change($form->value("title"), $form->value("author"), $form->value("symbol"), $form->value("description"), $form->value("documentation"));
					redirect("entry.php?id=".$entry->id()."&cat=".$catid);
				} catch (Exception $e) {
					$site->addcontent(notice($lang['entry']['failedchange']));
				}
			} else {
				// Add the new entry
				try {
					$ret = $category->addentry($form->value("title"), $form->value("author"), $form->value("symbol"), $form->value("description"), $form->value("documentation"));
					if (is_numeric($ret))
						if ($user->entrycan("changeentry", $ret))
							redirect("entry.php?id=".$ret."&cat=".$category->id()."&action=change");
						else
							redirect("entry.php?id=".$ret."&cat=".$category->id());
					else
						throw new CodeKBException(__METHOD__, "entry", "failedadd");
				} catch (Exception $e) {
					$site->addcontent(notice($lang['entry']['failedadd']));
				}				
			}
		} 
	
		$dialog = new CodeKBTemplate("dialog");

		if ($change)
			$dialog->push("legend", $lang['entry']['change']);
		else
			$dialog->push("legend", $lang['entry']['add']);
	
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$content = $form->head();
		$content .= $form->get("title")."<br />\n";
		$content .= $form->get("author");
		
		$dialogitem->push("top", $content);
	 
		$content = $form->get("description")."<br /><br />\n";
		$content .= $form->get("symbol")."<br /><br />\n";
		
		if ($conf['general']['javascript']) {
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[b][/b]';\" value=\"{$lang['bbcode']['bold']}\" title=\"{$lang['bbcode']['boldalt']}\" style=\"width: auto; font-weight: bold; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[i][/i]';\" value=\"{$lang['bbcode']['italic']}\" title=\"{$lang['bbcode']['italicalt']}\" style=\"width: auto; font-style: italic; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[u][/u]';\" value=\"{$lang['bbcode']['underline']}\" title=\"{$lang['bbcode']['underlinealt']}\" style=\"width: auto; text-decoration:underline; padding-left:3px; padding-right:3px;\" />\n";
			$content .= $lang['bbcode']['size'];
			$content .= "<select name=\"bbsize\" size=\"1\" onchange=\"this.form.documentation.value=this.form.documentation.value+'[size='+this.form.bbsize.value+'][/size]';\">\n";
			$content .= "<option value=\"1\">1</option>\n";
			$content .= "<option value=\"2\">2</option>\n";
			$content .= "<option value=\"3\">3</option>\n";
			$content .= "<option value=\"4\">4</option>\n";
			$content .= "<option value=\"5\">5</option>\n";
			$content .= "<option value=\"6\">6</option>\n";
			$content .= "<option value=\"7\">7</option>\n";
			$content .= "</select>\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[center][/center]';\" value=\"{$lang['bbcode']['center']}\" title=\"{$lang['bbcode']['centeralt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n"; 
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[-]';\" value=\"{$lang['bbcode']['indent']}\" title=\"{$lang['bbcode']['indentalt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";			
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[--]';\" value=\"{$lang['bbcode']['line']}\" title=\"{$lang['bbcode']['linealt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[list]\\n[*]{$lang['bbcode']['listitem']}\\n[/list]';\" value=\"{$lang['bbcode']['list']}\" title=\"{$lang['bbcode']['listalt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[olist]\\n[*]{$lang['bbcode']['listitem']}\\n[/olist]';\" value=\"{$lang['bbcode']['olist']}\" title=\"{$lang['bbcode']['olistalt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[url][/url]';\" value=\"{$lang['bbcode']['url']}\" title=\"{$lang['bbcode']['urlalt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[anker= ]';\" value=\"{$lang['bbcode']['anker']}\" title=\"{$lang['bbcode']['ankeralt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[img][/img]';\" value=\"{$lang['bbcode']['image']}\" title=\"{$lang['bbcode']['imagealt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= "<input type=\"button\" onclick=\"this.form.documentation.value=this.form.documentation.value+'[pre][/pre]';\" value=\"{$lang['bbcode']['pre']}\" title=\"{$lang['bbcode']['prealt']}\" style=\"width: auto; padding-left:3px; padding-right:3px;\" />\n";
			$content .= $lang['bbcode']['code'];
			$content .= "<select name=\"bbhigh\" size=\"1\" onchange=\"this.form.documentation.value=this.form.documentation.value+'[code='+this.form.bbhigh.value+'][/code]';\">\n";
			foreach ($conf['highlight']['languages'] as $val)
				$content .= "<option value=\"".htmlentities($val)."\">{$val}</option>\n";
			$content .= "</select>\n";			
		}
		
		$content .= $form->get("documentation")."<br /><br />\n";
		
		$dialogitem->push("content1", $content);
		$dialogitem->push("tail", $form->tail());
		
		$dialogcode = $dialogitem->__toString();
		
		if ($_POST['preview']) {
			
			$dialogitem2 = new CodeKBTemplate("dialogitem");
			
			$dialogitem2->push("head", "<em><strong>".$lang['general']['preview']."</strong></em><br /><br />");
			$dialogitem2->push("content1", parsebbcode($form->value("documentation")));	
			
			$dialogcode .= $dialogitem2->__toString();	
		}
		
		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);

		return true;
	
	} // showinput
	
	function showchange() {

		global $lang;
		global $user;
		global $site;
		global $catid;
		global $entry;
		
		$site->addfooter("help.php?on=entry#change", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
	
		$site->title($lang['entry']['change']);
	
 	   if ( ! $user->entrycan("changeentry", $entry) ) { 
			$site->addcontent(notice($lang['entry']['nochangeallowed']));
			return false;
    	}
		
		$dialog = new CodeKBTemplate("dialog");
		$dialog->push("legend", $lang['entry']['change']);
		
		$dialogitem = new CodeKBTemplate("dialogitem"); 
		
		$dialogitem->push("head", phrasereplace($lang['entry']['choosechange'], "%1%", htmlentities($entry->name())));
		
		$content = "<br /><br />\n";
		$content .= icon("configure", $lang['entry']['modify'])." ".url("entry.php?id=".$entry->id()."&cat=".$catid."&action=modify", $lang['entry']['modify'])."<br />\n";
		if ($user->entrycan("addentry", $entry) || $user->entrycan("delentry", $entry))
			$content .=icon("links", $lang['entry']['link'])." ".url("entry.php?id=".$entry->id()."&cat=".$catid."&action=link", $lang['entry']['link'])."<br />\n";
		$content .= icon("files", $lang['entry']['files'])." ".url("entry.php?id=".$entry->id()."&cat=".$catid."&action=files", $lang['entry']['files'])."<br />\n";
		$content .= "<div style=\"text-align: right\">";
		$content .= url("entry.php?id=".$entry->id()."&cat=".$catid, phrasereplace($lang['general']['backto'], "%1%", htmlentities($entry->name())), $entry->name());
		
		$dialogitem->push("content1", $content);
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);

		return true;
	
	} // showchange

	function showlinks() {

		global $lang;
		global $user;
		global $site;
		global $catid;
		global $entry;
	
		$site->title($lang['entry']['link']);
		
		$site->addfooter("help.php?on=entry#link", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
	
 	   if ( ! $user->entrycan("delentry", $entry) && ! $user->entrycan("addentry", $entry)) { 
			$site->addcontent(notice($lang['entry']['nochangeallowed']));
			return false;
	    }
    
    	if ($_POST['cancel'])
    		redirect("entry.php?id=".$entry->id()."&cat=".$catid);
    

		if ($user->entrycan("addentry", $entry)) {  
			
			$form1 = new CodeKBForm("entry.php", "link");
			$form1->addhidden("id", $entry->id());
			$form1->addhidden("cat", $catid);
			
			$tmpcat = new CodeKBCategory(0, $user);
			
			if ($user->can("addentry", $tmpcat))
				$form1->addcombo("newcat", "0", $lang['category']['root']);
			
			$array = $tmpcat->listcategories("name", 1);
			foreach ($array as $val) 
				if ($user->can("addentry", $val['id']))
					$form1->addcombo("newcat", $val['id'], str_repeat("-", ($val['reclevel']) *2)." ".$val['name']);
			unset($tmpcat);

			$form1->addlabel("newcat", $lang['entry']['linkadd']);				
			
			$form1->addbutton("addlink", $lang['general']['submit']);
			$form1->addbutton("cancel");
		}
		
		if ($user->entrycan("delentry", $entry)) { 
			
			$form2 = new CodeKBForm("entry.php", "link");
			$form2->addhidden("id", $entry->id());
			$form2->addhidden("cat", $catid);
			
			$catsofentry = $entry->categories();
			foreach ($catsofentry as $val) {
				$thiscat = new CodeKBCategory($val, $user);
				$form2->addcheckbox("cat_".$thiscat->id(), url("category.php?id=".$thiscat->id(), $thiscat->name()));
				unset($thiscat);  
			}	

			$form2->addbutton("unlink", $lang['general']['delete']);
			$form2->addbutton("cancel");
		}
		
    	if ($_POST['addlink'] && $form1->fill()) {
			try {
				$newcat = new CodeKBCategory($form1->value("newcat"), $user);
				if ($entry->addlink($form1->value("newcat"))) {
					$site->addcontent(notice(phrasereplace($lang['entry']['linkaddsucc'], "%1%", $newcat->name())));
					if ($form2)
						$form2->addcheckbox("cat_".$newcat->id(), url("category.php?id=".$newcat->id(), $newcat->name()));
				} else
					$site->addcontent(notice($lang['entry']['failedchange']));
				unset ($newcat);
			} catch (Exception $e) {
				if ($e->getCode() == 1) {
					$site->addcontent(notice($lang['entry']['duplicate']));
					$form1->setmissing("newcat");
				} else
					$site->addcontent(notice($lang['entry']['failedchange']));
			}
		} 
    
	    if ($_POST['unlink'] && is_object($form2) && $form2->fill()) {

			$id = $entry->id();
			
			foreach ($catsofentry as $val) {

				if ($form2->value("cat_".$val) == "1") {
					try {
						$entry->delink($val);
						$notice = $lang['entry']['linkremovesucc'];
						$form2->remove("cat_".$val);
					} catch (Exception $e) {
						$form2->setmissing("cat_".$val);
						$notice = $lang['entry']['failedunlink'];
						break;
					}
				}
			}
					
			if (!$user->entrycan("see", $entry, false))
				redirect("category.php?id=".$catid);

				
			$site->addcontent(notice($notice));
							
		}
		
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['entry']['link']);
		
		$dialogcode = ""; 
		
		if ($form1) {
			
			$dialogitem1 = new CodeKBTemplate("dialogitem");
			
			$content = $form1->head();
			$content .= $lang['entry']['linkaddexplain']."<br /><br />\n";
			
			$dialogitem1->push("head", $content);
			
			$dialogitem1->push("content1", $form1->get());
			
			$dialogitem1->push("tail", $form1->tail());
			
			$dialogcode .= $dialogitem1->__toString();
			
		}
		
		if ($form2) {
			
			$dialogitem2 = new CodeKBTemplate("dialogitem");
			
			$content = $form2->head();
			$content .= $lang['entry']['linkremoveexplain']."<br /><br />\n";
			
			$dialogitem2->push("head", $content);
			
			$content = "<div class = \"forms\">\n";
			$content .= $form2->get();
			$content .= "</div>";
			$dialogitem2->push("content1", $content);
			
			$dialogitem2->push("tail", $form2->tail());
			
			$dialogcode .= $dialogitem2->__toString(); 
			
		}
		
		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);

		return true;
	
	} //showlinks
	

	function showfiles() {

		global $lang;
		global $conf;
		global $user;
		global $site;
		global $catid;
		global $entry;
	
		$site->title($lang['entry']['files']);
		
		$site->addfooter("help.php?on=file", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
	    if ( ! $user->entrycan("changeentry", $entry) ) { 
			$site->addcontent(notice($lang['entry']['nochangeallowed']));
			return false;
    	}
    
    	if ($_POST['cancel'])
    		redirect("entry.php?id=".$entry->id()."&cat=".$catid);

		$form1 = new CodeKBForm("entry.php", "files");
		$form1->addhidden("id", $entry->id());
		$form1->addhidden("cat", $catid);

		$form1->addfile("upload");
		$form1->addlabel("upload", $lang['file']['upload']);
		
		$form1->addcombo("highlight", $conf['highlight']['binary']);
		foreach ($conf['highlight']['languages'] as $language)
			$form1->addcombo("highlight", $language, null, $language=="text");
		 		
		$form1->addlabel("highlight", $lang['file']['language']);
		
		$db = new CodeKBDatabase();
		$db->dosql("SELECT name, symbol ".
							"FROM symbols ".
							"WHERE symbol LIKE 'type_%'");
		while ($val = $db->row())
			$form1->addradio("symbol", $val['name'], icon($val['name'], $val['name']), $val['name']=="Unkown", false); 
		
		$form1->addbutton("addfile", $lang['general']['submit']);
		$form1->addbutton("cancel");
	
		$form2 = new CodeKBForm("entry.php", "files");
		$form2->addhidden("id", $entry->id());
		$form2->addhidden("cat", $catid);
		
		$filesofentry = $entry->listfiles();
		
		foreach ($filesofentry as $val)
			$form2->addcheckbox("file_".$val['id'], icon($val['symbol'], $val['name'])." ".url("file.php?id=".$val['id']."&cat=".$catid, $val['name'])." (".url("file.php?id=".$val['id']."&cat=".$catid."&action=modify",$lang['general']['modify']).")");  

		$form2->addbutton("removefile", $lang['general']['delete']);
		$form2->addbutton("cancel");
		
    
	    if ($_POST['addfile'] && $form1->fill()) {
			try {
				$ret = $entry->addfile("upload", $form1->value("highlight"), $form1->value("symbol")); 
				$newfile = new CodeKBFile($ret, $user);
				$site->addcontent(notice($lang['file']['addsucc']));
				
				$form2->addcheckbox("file_".$newfile->id(), icon($newfile->symbol(), $newfile->name())." ".url("file.php?id=".$newfile->id()."&cat=".$catid, $newfile->name())." (".url("file.php?id=".$newfile->id()."&cat=".$catid."&action=modify",$lang['general']['modify']).")");
				unset ($newfile);
			} catch (Exception $e) {
				if ($e->getCode() == 1) {
					$site->addcontent(notice($lang['file']['uploadfailed']));
				} else
					$site->addcontent(notice($lang['file']['failedadd']));
			}
	    }
    	
		if ($_POST['removefile'] && $form2->fill()) {

			foreach ($filesofentry as $val) {

				try {
					if ($form2->value("file_".$val['id']) == "1") { 
						$tmpfile = new CodeKBFile($val['id'], $user);
						$tmpfile->delete();
						unset($tmpfile);
						$notice = $lang['file']['delsucc'];
						$form2->remove("file_".$val['id']);
					}
				} catch (Exception1 $e) {
					$notice = $lang['file']['failedremove'];
					break;
				}
			}
			
			$site->addcontent(notice($notice));
			
		}

		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['entry']['files']);
		$dialogcode = ""; 
		
		$dialogitem1 = new CodeKBTemplate("dialogitem");
			
		$content = $form1->head();
		$content .= $lang['file']['addexplain']."<br /><br />\n";
			
		$dialogitem1->push("head", $content);
		$dialogitem1->push("content1", $form1->get());
		$dialogitem1->push("tail", $form1->tail());
			
		$dialogcode .= $dialogitem1->__toString(); 
		
		$content = $form2->head();
		$content .= $lang['file']['removeexplain']."<br /><br />\n";
		
		$dialogitem2 = new CodeKBTemplate("dialogitem");		
		$dialogitem2->push("head", $content);
			
		$content = "<div class = \"forms\">\n";
		$content .= $form2->get();
		$content .= "</div>";
		$dialogitem2->push("content1", $content);
		$dialogitem2->push("tail", $form2->tail());
			
		$dialogcode .= $dialogitem2->__toString();

		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);
				

		return true;
	
	} // showfiles	
	
?>
