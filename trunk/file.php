<?php

	require_once("includes/global.php");
	
	$id = null;
	$cat = null;
	$category = null;
	$file = null;
	$user = null;	
	$site = null;

	$user = new CodeKBUser();
	$site = new CodeKBSite($user);

	$site->registermain("main");
	$site->registerfunction("show", "showfile", true);
	$site->registerfunction("modify", "showinput");
	$site->registerfunction("download", "showdownload");

	$site->registervariable("id", $id);
	$site->registervariable("cat", $cat);
	
	
	$site->start();
	
	$site->output();
	
	
	
	function main() {
		
		global $lang;
		global $id;
		global $cat;		
		global $user;
		global $site;
		global $category;
		global $file;
		
		if (!is_bool($cat) && is_numeric($cat))
			try {
				$category = new CodeKBCategory($cat, $user);
			} catch (Exception $e) {
				$site->addcontent(notice($lang['category']['nosuchcat']));
				return false;
			}

		try {
			$file = new CodeKBFile($id, $user);
		} catch (Exception $e) {
			$site->addcontent(notice($lang['file']['nosuchfile']));
			return false;
		}
		
		if (!$file->downloadable()) {
			$site->addcontent(notice($lang['file']['nosuchfile']));
			return false;
		}
					
		if ($category)
			$site->navigation($category, $file->entry());

		return true;
		
	} // main
	
	function showdownload() {
	
		global $file;

		$content = $file->content();
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		$finfo = finfo_open(FILEINFO_MIME);
		header('Content-type: '.finfo_buffer($finfo, $content));
		finfo_close($finfo);
		header("Content-Disposition: attachment; filename=".htmlentities($file->name()));
		echo $content; 
		die();
	
	} // showdownload	
	
	function showfile () {
		
		global $lang;
		global $conf;		
		global $user;
		global $site;
		global $category;
		global $file;
		
		$site->title($file->name());
		
		$site->addfooter("help.php?on=file", "help", $lang['menu']['help'], $lang['menu']['helpalt']);		
		
		if ($file->highlight() == $conf['highlight']['binary'])
			redirect("file.php?id=".$file->id()."&action=download");
			
		if ($category)
			$cat = $category->id();
			
		if ($user->entrycan("changeentry", $file->entry())) {
			$site->addmenu("file.php?id=".$file->id()."&cat=".$cat."&action=modify", $lang['menu']['file'], $lang['menu']['filealt']);
			$site->addfooter("file.php?id=".$file->id()."&cat=".$cat."&action=modify", "configure", $lang['menu']['file'], $lang['menu']['filealt']);
			$site->addfooter("entry.php?id=".$file->entry()->id()."&cat=".$cat."&action=files", "files", $lang['menu']['attach'], $lang['menu']['attachalt']);
		} 
		
		$entrytpl = new CodeKBTemplate("entry");		
		
		$entrytpl->push("icon", icon($file->symbol(), $file->name()));
		$entrytpl->push("name", $file->name());		
		$content = $lang['file']['download'].": ";
		$content .= url("file.php?id=".$file->id()."&action=download", $file->name());
		$content .= " (";
		$unit = "b";
		$size = $file->size();
		if ( $size > 1024 ) { $size /= 1024; $unit = "kb"; }
		if ( $size > 1024 ) { $size /= 1024; $unit = "mb"; }
		$content .= round($size).$unit.")";
		$entrytpl->push("subheader", $content);
	
		$code = "[code=".$file->highlight()."]";
		$code .= $file->content();
		$code .= "[/code]";
		$entrytpl->push("documentation", parsebbcode($code));
		
		$site->addcontent($entrytpl);	
			
		return true;
		

	} // showfile
	
	function showinput() {
	
		global $lang;
		global $conf;		
		global $user;
		global $site;
		global $category;
		global $file;

		$site->title($lang['file']['modify']);
		
		$site->addfooter("help.php?on=file#change", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
	
		if ( ! $user->entrycan("changeentry", $file->entry()) ) {
			$site->addcontent(notice($lang['entry']['nochangeallowed']));
			return false;
		}
	
		if ($_POST['cancel'])
    		redirect("entry.php?id=".$file->entry()->id());

		if ($category)
			$cat = $category->id();
	
		
		$form = new CodeKBForm("file.php", "modify");
		$form->addhidden("id", $file->id());
		$form->addhidden("cat", $cat);

		$form->addtext("name", $file->name());
		$form->addlabel("name",	$lang['file']['name']);
		$form->setrequired("name");

		$form->addfile("upload");
		$form->addlabel("upload", $lang['file']['upload']);
		
		$form->addcombo("highlight", $conf['highlight']['binary'], null, $conf['highlight']['binary']==$file->highlight());
		while ($language = next($conf['highlight']['languages']))
			$form->addcombo("highlight", $language, null, $language==$file->highlight());
		 		
		$form->addlabel("highlight", $lang['file']['language']);
		
		$db = new CodeKBDatabase();
		$db->dosql("SELECT name, symbol ".
							"FROM symbols ".
							"WHERE symbol LIKE 'type_%'");
		while ($val = $db->row())
			$form->addradio("symbol", $val['name'], icon($val['name'], $val['name']), $val['name']==$file->symbol(), false); 

		$form->addsubmit();
		$form->addcancel();		

		if ($_POST['submit']) {
			if (!$form->fill()) 
				$site->addcontent(notice($lang['general']['missing']));
			else {
				global $HTTP_POST_FILES;
				if (is_uploaded_file($HTTP_POST_FILES['upload']['tmp_name']))
					$upload = "upload";
				else
					$upload = false;
				
				try {
					$file->change($form->value("name"), $form->value("highlight"), $form->value("symbol"), $upload);
					if ($form->value("highlight") == $conf['highlight']['binary'])
						redirect("entry.php?id=".$file->entry()->id()."&cat=".$cat);
					else
						redirect("file.php?id=".$file->id()."&cat=".$cat);
				} catch (Exception $e) {
					$site->addcontent(notice($lang['entry']['failedfilechange']));	
				}
			}
		}
		
		$dialog = new CodeKBTemplate("dialog");
		$dialog->push("legend",$lang['file']['modify']);
		
		$dialogitem = new CodeKBTemplate("dialogitem");
		
		$content = $form->head();
		$content .= $form->get("name");
		$dialogitem->push("top", $content);
		
		$dialogitem->push("head", "(".$lang['file']['newuploadexplain'].")<br />\n");
		$dialogitem->push("content1", $form->get());
		$dialogitem->push("tail", $form->tail());
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);

		return true;

	} // showinput	

?>