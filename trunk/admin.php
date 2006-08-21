<?php

require_once("includes/global.php");
	
	$user = null;	
	$site = null;
	$admin = null;
	$group = null;
	$userid = null;

	try {
		$user = new CodeKBUser();
		$site = new CodeKBSite($user);
	} catch (Exception $e) {
		CodeKBException::backtrace();
	}

	$site->registermain("main");
	$site->registerfunction("menu", "showmenu", true);
	$site->registerfunction("groups", "showgroups");
	$site->registerfunction("users", "showusers");
	$site->registerfunction("modifygroup", "showgroupmod");
	$site->registerfunction("modifyuser", "showusermod");
	
	$site->registervariable("group", $group);		
	$site->registervariable("user", $userid);

	$site->start();
	
	$site->output();
	
	
	
	function main() {
		
		global $lang;
		global $user;
		global $admin;
		global $site;
		
		try {
			$admin = new CodeKBAdmin($user);
		} catch (Exception $e) {
			$site->addcontent(notice($lang['admin']['accessdenied']));
			return false;
		}
		
		return true;
		
	} // main

	function showmenu() {

		global $lang;
		global $conf;
		global $user;
		global $admin;
		global $site;

		$site->title($lang['admin']['menu']);
		
		$site->addfooter("help.php?on=admin", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['admin']['menu']);
		
		$dialogitem = new CodeKBTemplate("dialogitem"); 

		$dialogitem->push("head", $lang['admin']['menuexplain']);
		
		$content = "<br /><br />\n";
		$content .= icon("group", $lang['admin']['modifygroups'])." ".url("admin.php?action=groups", $lang['admin']['modifygroups'])."<br />\n";
		$content .= icon("user", $lang['admin']['modifyusers'])." ".url("admin.php?action=users", $lang['admin']['modifyusers'])."<br />\n";
		$content .= icon("lock",$lang['admin']['modifynobody'])." ".url("admin.php?group=0&action=modifygroup", $lang['admin']['modifynobody'])."<br />\n";		

		$dialogitem->push("content1", $content);
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);
	
		return true;
	
	} // showmenu


	function showgroups() {

		global $lang;
		global $conf;
		global $user;
		global $admin;
		global $site;

		$site->title($lang['admin']['modifygroups']);
		
		$site->addfooter("help.php?on=admin#group", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("admin.php");
    
		$groups = $admin->listgroups();
		
		$form1 = new CodeKBForm("admin.php", "groups");
		
		$form1->addtext("name");
		$form1->addlabel("name", $lang['admin']['groupname']);
		$form1->setrequired("name");
		
		foreach ($groups as $val)
			$form1->addcombo("clone", $val['id'], ($val['name']==null?$lang['admin']['nobody']:$val['name'])); 
		
		$form1->addlabel("clone", $lang['admin']['clonefrom']);

		$form1->addbutton("addgroup", $lang['general']['submit']);
		$form1->addbutton("cancel");

		$form2 = new CodeKBForm("admin.php", "groups");
		
		foreach ($groups as $val)
			if (!is_null($val['name']))
				$form2->addcheckbox("group_".$val['id'], $val['name']." (".url("admin.php?group=".$val['id']."&action=modifygroup",$lang['general']['modify']).")");
		
		$form2->addbutton("removegroup", $lang['general']['delete']);
		$form2->addbutton("cancel");
		
		if ($_POST['addgroup']) {
			
			if (!$form1->fill())
				$site->addcontent(notice($lang['general']['missing']));
			else {
				try {
					$ret = $admin->addgroup($form1->value("name"), $form1->value("clone"));
					$form1->addcombo("clone", $ret, $form1->value("name"));
					$form2->addcheckbox("group_".$ret, $form1->value("name")." (".url("admin.php?group=".$ret."&action=modifygroup", $lang['general']['modify']).")");
					$form1->addtext("name", "");
				} catch (Exception $e) {
					if ($e->getCode() == 1) {
						$site->addcontent(notice($lang['admin']['duplicategroup']));
						$form1->setmissing("name");	
					}
				}		
			}
		}
		
		if ($_POST['removegroup'] && $form2->fill()) {
			
			try {
				foreach ($groups as $val)
					if ($form2->value("group_".$val['id'])) {
						$admin->deletegroup($val['id']);
						$notice = $lang['admin']['delgroupsucc'];
						$form1->remove("clone", $val['id']);
						$form2->remove("group_".$val['id']); 
					}
			} catch (Exception $e) {
				$notice = $lang['admin']['faileddelgroup'];
			}
			
			$site->addcontent(notice($notice));
		}
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['admin']['modifygroups']);
		
		$dialogitem1 = new CodeKBTemplate("dialogitem");
		
		$content = $form1->head();
		$content .= $lang['admin']['addgroupexplain']."<br /><br />\n"; 
		$dialogitem1->push("head", $content);
		
		$dialogitem1->push("content1", $form1->get());
		$dialogitem1->push("tail", $form1->tail());

		$dialogitem2 = new CodeKBTemplate("dialogitem");
		
		$content = $form2->head();
		$content .= $lang['admin']['delgroupexplain']."<br /><br />\n"; 
		$dialogitem2->push("head", $content);
		
		$content = "<div class = \"forms\">";
		$content .= $form2->get();
		$content .= "</div>";
		$dialogitem2->push("content1", $content);
		$dialogitem2->push("tail", $form2->tail());
		
		$dialogcode = $dialogitem1->__toString();
		$dialogcode .= $dialogitem2->__toString();
		
		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);

		return true;
	
	}  // showgroups	

	function showgroupmod() {

		global $lang;
		global $conf;
		global $user;
		global $admin;
		global $site;
		global $group;

		$site->title($lang['admin']['changegroup']);
		
		$site->addfooter("help.php?on=admin#group", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("admin.php");
    
		if ($group != 0) {
			$form1 = new CodeKBForm("admin.php", "modifygroup");
			$form1->addhidden("group", $group);
		
			$form1->addtext("name", $admin->groupname($group));
			$form1->addlabel("name", $lang['admin']['groupname']);
			$form1->setrequired("name");
		
			$form1->addbutton("changename", $lang['general']['submit']);
			$form1->addbutton("cancel");	

			$form2 = new CodeKBForm("admin.php", "modifygroup");
			$form2->addhidden("group", $group);
		
			$groupsusers = $admin->groupsusers($group);
		
			foreach ($groupsusers as $val)
				if (!is_null($val['name']))
					$form2->addcheckbox("user_".$val['id'], $val['name']." (".url("admin.php?user=".$val['id']."&action=modifyuser",$lang['general']['modify']).")");
		
			$form2->addbutton("removeuser", $lang['general']['delete']);
			$form2->addbutton("cancel");
		
			$form3 = new CodeKBForm("admin.php", "modifygroup");
			$form3->addhidden("group", $group);
		
			$users = $admin->listusers();

			foreach ($users as $val)
				if (!is_null($val['name']))
					$form3->addcombo("user", $val['id'], $val['name']);
		
			$form3->addbutton("adduser", $lang['general']['submit']);
			$form3->addbutton("cancel");
		}

		$form4 = new CodeKBForm("admin.php", "modifygroup");
		$form4->addhidden("group", $group);
		$categories = $admin->listcategories();

		$form4->addcombo("cat", 0, $lang['category']['root']);

		foreach ($categories as $val) 
			$form4->addcombo("cat", $val['id'], str_repeat("-", ($val['reclevel']) *2)." ".$val['name']);  

		$form4->addbutton("choosecat", $lang['general']['submit']);
		$form4->addbutton("cancel");
		
		if ($_POST['choosecat'] && $form4->fill()) {
		
			$form5 = new CodeKBForm("admin.php", "modifygroup");
			$form5->addhidden("group", $group);
			$form5->addhidden("choosecat", "1");
			$form5->addhidden("cat", $_POST['cat']);
			
			$form5->addcheckbox("recursive", $lang['admin']['recursiverights']);
			
			$val = $admin->getrights($group, $_POST['cat']);
			if ($val >= 128) {
				$val -= 128;
				$rights["cdel"] = true;
			}
			if ($val >= 64) {
				$val -= 64;
				$rights["cadd"] = true;
			}
			if ($val >= 32) {
				$val -= 32;
				$rights["cchange"] = true;
			}
			if ($val >= 16) {
				$val -= 16;
				$rights["edel"] = true;
			}
			if ($val >= 8) {
				$val -= 8;
				$rights["eadd"] = true;
			}			
			if ($val >= 4) {
				$val -= 4;
				$rights["echange"] = true;
			}
			if ($val >= 2) {
				$val -= 2;
				$rights["down"] = true;
			}
			if ($val == 1) {
				$val -= 1;
				$rights["see"] = true;
			}
			
			$form5->addcheckbox("see_".$_POST['cat'], null, $rights["see"]);
			$form5->addcheckbox("down_".$_POST['cat'], null, $rights["down"]);
			$form5->addcheckbox("echange_".$_POST['cat'], null, $rights["echange"]);
			$form5->addcheckbox("eadd_".$_POST['cat'], null, $rights["eadd"]);
			$form5->addcheckbox("edel_".$_POST['cat'], null, $rights["edel"]);
			$form5->addcheckbox("cchange_".$_POST['cat'], null, $rights["cchange"]);
			$form5->addcheckbox("cadd_".$_POST['cat'], null, $rights["cadd"]);
			$form5->addcheckbox("cdel_".$_POST['cat'], null, $rights["cdel"]);
			
			$form5->addbutton("changerights", $lang['general']['submit']);
			$form5->addbutton("cancdl");
		}

		if ($_POST['changename']) {
			
			if (!$form1->fill())
				$site->addcontent(notice($lang['general']['missing']));
			else {
			
				try {
					$admin->changegroup($group, $form1->value("name"));
					$site->addcontent(notice($lang['admin']['groupchangesucc']));
				} catch (Exception $e) {
					if ($e->getCode() == 1)
						$site->addcontent(notice($lang['admin']['duplicategroup']));
					else
						$site->addcontent(notice($lang['admin']['failedgroupchange']));
				}
			}
		}

		if ($_POST['removeuser'] && $form2->fill()) {
			
			try {
				foreach ($groupsusers as $val)
					if ($form2->value("user_".$val['id'])) {
						$admin->partgroup($val['id'], $group);
						$form2->remove("user_".$val['id']);
						$notice = $lang['admin']['partsucc'];
					}						
			} catch (Exception $e) {
				$notice = $lang['admin']['failedpart'];	
			}
			$site->addcontent(notice($notice));
		}

		if ($_POST['adduser'] && $form3->fill()) {
			
			try {
				$admin->joingroup($form3->value("user"), $group);
				$form2->addcheckbox("user_".$form3->value("user"), $admin->username($form3->value("user"))." (".url("admin.php?user=".$form3->value("user")."&action=modifyuser",$lang['general']['modify']).")");
				$site->addcontent(notice($lang['admin']['joinsucc']));						
			} catch (Exception $e) {
				if ($e->getCode() == 1) 
					$site->addcontent(notice($lang['admin']['alreadyingroup']));
				else
					$site->addcontent(notice($lang['admin']['failedjoin']));	
			}
		}
		
		if ($_POST['changerights'] && $form5->fill()) {
			
			try {
				$rightval = 0;
				if ($form5->value("see_".$_POST['cat'])) {
					$rights["see"] = true;
					$rightval += 1;
				} else
					$rights["see"] = false;
				if ($form5->value("down_".$_POST['cat'])) {
					$rights["down"] = true;
					$rightval += 2;
				} else
					$rights["down"] = false;
				if ($form5->value("echange_".$_POST['cat'])) {
					$rights["echange"] = true;
					$rightval += 4;
				} else
					$rights["echange"] = false;
				if ($form5->value("eadd_".$_POST['cat'])) {
					$rights["eadd"] = true;
					$rightval += 8;
				} else
					$rights["eadd"] = false;
				if ($form5->value("edel_".$_POST['cat'])) {
					$rights["edel"] = true;
					$rightval += 16;
				} else
					$rights["edel"] = false;
				if ($form5->value("cchange_".$_POST['cat'])) {
					$rights["cchange"] = true;
					$rightval += 32;
				} else
					$rights["cchange"] = false;
				if ($form5->value("cadd_".$_POST['cat'])) {
					$rights["cadd"] = true;
					$rightval += 64;
				} else
					$rights["cadd"] = false;
				if ($form5->value("cdel_".$_POST['cat'])) {
					$rights["cdel"] = true;
					$rightval += 128;
				} else
					$rights["cdel"] = false;
					
				$admin->changerights($group, $_POST['cat'], $rightval, $form5->value("recursive"));					

				$form5->addcheckbox("see_".$_POST['cat'], null, $rights["see"]);
				$form5->addcheckbox("down_".$_POST['cat'], null, $rights["down"]);
				$form5->addcheckbox("echange_".$_POST['cat'], null, $rights["echange"]);
				$form5->addcheckbox("eadd_".$_POST['cat'], null, $rights["eadd"]);
				$form5->addcheckbox("edel_".$_POST['cat'], null, $rights["edel"]);
				$form5->addcheckbox("cchange_".$_POST['cat'], null, $rights["cchange"]);
				$form5->addcheckbox("cadd_".$_POST['cat'], null, $rights["cadd"]);
				$form5->addcheckbox("cdel_".$_POST['cat'], null, $rights["cdel"]);
				
				$site->addcontent(notice($lang['admin']['changerightssucc']));

			} catch (Exception $e) {
				$site->addcontent(notice($lang['admin']['failedchangerights']));		
			}
		}
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['admin']['changegroup']);
		
		if ($group != 0) {
			$dialogitem1 = new CodeKBTemplate("dialogitem");
		
			$content = $form1->head();
			$content .= $lang['admin']['groupchangeexplain']."<br /><br />\n"; 
			$dialogitem1->push("head", $content);
		
			$dialogitem1->push("content1", $form1->get());
			$dialogitem1->push("tail", $form1->tail());

			$dialogitem2 = new CodeKBTemplate("dialogitem");
		
			$content = $form2->head();
			$content .= $lang['admin']['groupdeluserexplain']."<br /><br />\n"; 
			$dialogitem2->push("head", $content);
		
			$content = "<div class = \"forms\">";
			$content .= $form2->get();
			$content .= "</div>";
			$dialogitem2->push("content1", $content);
			$dialogitem2->push("tail", $form2->tail());
		
			$dialogitem3 = new CodeKBTemplate("dialogitem");
		
			$content = $form3->head();
			$content .= $lang['admin']['groupadduserexplain']."<br /><br />\n"; 
			$dialogitem3->push("head", $content);
		
			$content = $form3->get();
			$dialogitem3->push("content1", $content);
			$dialogitem3->push("tail", $form3->tail());
		}

		$dialogitem4 = new CodeKBTemplate("dialogitem");
		
		$content = $form4->head();
		$content .= $lang['admin']['grouprightsexplain']."<br /><br />\n"; 
		$dialogitem4->push("head", $content);
		$content = $form4->get();
		$content .= $form4->tail();

		if ($form5) {
			$content .= "<br />\n";
			$content .= $form5->head();
			$content .= $form5->get("recursive");
			$content .= "<br /><br />\n";
			$content .= "<table style = \"width: 100%; text-align: center\">\n";
			$content .= "<tr><td colspan=\"4\">\n";
			$content .= "<em>".$lang['admin']['category']."</em>";
			$content .= "\n</td><td>|</td><td colspan=\"4\">\n";
			$content .= "<em>".$lang['admin']['entry']."</em>";
			$content .= "\n</td></tr>\n";
			$content .= "\n<tr>\n";
			$content .= "<td>".$lang['admin']['see']."</td>\n";
			$content .= "<td>".$lang['admin']['add']."</td>\n";
			$content .= "<td>".$lang['admin']['change']."</td>\n";
			$content .= "<td>".$lang['admin']['del']."</td>\n";
			$content .= "<td>|</td>\n";
			$content .= "<td>".$lang['admin']['download']."</td>\n";
			$content .= "<td>".$lang['admin']['add']."</td>\n";
			$content .= "<td>".$lang['admin']['change']."</td>\n";
			$content .= "<td>".$lang['admin']['del']."</td></tr>\n";
			$content .= "\n<tr>";
			$content .= "<td>".$form5->get("see_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("cadd_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("cchange_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("cdel_".$_POST['cat'])."</td>\n";
			$content .= "<td>|</td>\n";
			$content .= "<td>".$form5->get("down_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("eadd_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("echange_".$_POST['cat'])."</td>\n";
			$content .= "<td>".$form5->get("edel_".$_POST['cat'])."</td></tr>\n";
			$content .= "\n</table><br />\n";
			$content .= $lang['admin']['groupmustsee']."<br /><br />\n";
			$content .= $form5->tail();
			
		}		

		$dialogitem4->push("content1", $content);
		
		
		$dialogcode = "";
		if ($group != 0) {
			$dialogcode = $dialogitem1->__toString();
			$dialogcode .= $dialogitem2->__toString();
			$dialogcode .= $dialogitem3->__toString();
		}
		$dialogcode .= $dialogitem4->__toString();
		
		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);

		return true;
	
	}  // showgroupmod	

	function showusers() {

		global $lang;
		global $conf;
		global $user;
		global $admin;
		global $site;

		$site->title($lang['admin']['modifyusers']);
		
		$site->addfooter("help.php?on=admin#user", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("admin.php");
    
		$users = $admin->listusers();

		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['admin']['modifyusers']);
		
		$dialogitem = new CodeKBTemplate("dialogitem");
	
		$content = $lang['admin']['chooseuserexplain']."<br /><br />\n";
		foreach ($users as $val)
			if (!is_null($val['name']))
				$content .= url("admin.php?user=".$val['id']."&action=modifyuser",$lang['general']['modify']." ".$val['name'])."<br />\n";
		$content .= "<br />\n";				
		
		$dialogitem->push("content1", $content);
		
		$dialog->push("content", $dialogitem);
		
		$site->addcontent($dialog);

		return true;
	
	}  // showusers	

	function showusermod() {

		global $lang;
		global $conf;
		global $user;
		global $admin;
		global $site;
		global $userid;

		$site->title($lang['admin']['modifyusers']);
		
		$site->addfooter("help.php?on=admin#user", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("admin.php");
    
		$form1 = new CodeKBForm("admin.php", "modifyuser");
		$form1->addhidden("user", $userid);
		
		$form1->addtext("name", $admin->username($userid));
		$form1->addlabel("name", $lang['general']['username']);
		$form1->setrequired("name");
		
		$form1->addpassword("password");
		$form1->addlabel("password", $lang['admin']['password']);
		
		$form1->addcheckbox("delete", $lang['admin']['deleteuser']);
		
		$form1->addbutton("changeuser", $lang['general']['submit']);
		$form1->addbutton("cancel");
		
		$form2 = new CodeKBForm("admin.php", "modifyuser");
		$form2->addhidden("user", $userid);
		
		$groups = $admin->listgroups();
		
		foreach ($groups as $val)
			if (!is_null($val['name']))
				$form2->addcombo("group", $val['id'], $val['name']);
		
		$form2->addbutton("joingroup", $lang['general']['submit']);
		$form2->addbutton("cancel");		

		$form3 = new CodeKBForm("admin.php", "modifyuser");
		$form3->addhidden("user", $userid);
		
		$usersgroups = $admin->usersgroups($userid);
		
		foreach ($usersgroups as $val)
			if (!is_null($val['name']))
				$form3->addcheckbox("group_".$val['id'], $val['name']." (".url("admin.php?group=".$val['id']."&action=modifygroup",$lang['general']['modify']).")");
		
		$form3->addbutton("partgroup", $lang['general']['delete']);
		$form3->addbutton("cancel");
		
		
		if ($_POST['changeuser']) {
			
			if (!$form1->fill())
				$site->addcontent(notice($lang['general']['missing']));
			else {
				if ($form1->value("delete")) {
					try {
						$admin->deleteuser($userid);
						redirect("admin.php?action=users");
					} catch (Exception $e) {
						if ($e->getCode() == 1)
							$site->addcontent(notice($lang['admin']['deleteadmin']));
						else
							$site->addcontent(notice($lang['admin']['faileddeluser']));
					}
				} else {
					try {
						$admin->changeuser($userid, $form1->value("name"), $form1->value("password"));
						$site->addcontent(notice($lang['admin']['changeusersucc']));
						$form1->addpassword("password", "");
					} catch (Exception $e) {
						if ($e->getCode() == 1) {
							$site->addcontent(notice($lang['admin']['duplicateuser']));
							$form1->setmissing("name");	
						} else
							$site->addcontent(notice($lang['admin']['failesuserchange']));
					}		
				}
			}
		}

		if ($_POST['joingroup'] && $form2->fill() && $form2->value("group")) {
			
			try {
				$admin->joingroup($userid, $form2->value("group"));
				$site->addcontent(notice($lang['admin']['joinsucc']));
				$form3->addcheckbox("group_".$form2->value("group"), $admin->groupname($form2->value("group"))." (".url("admin.php?group=".$form2->value("group")."&action=modifygroup",$lang['general']['modify']).")");
			} catch (Exception $e) {
				if ($e->getCode() == 1) {
					$site->addcontent(notice($lang['admin']['alreadyingroup']));
				} else
					$site->addcontent(notice($lang['admin']['failedjoin']));
			}		
		}

		
		if ($_POST['partgroup'] && $form3->fill()) {
			
			try {
				foreach ($usersgroups as $val)
					if ($form3->value("group_".$val['id'])) {
						$admin->partgroup($userid, $val['id']);
						$notice = $lang['admin']['partsucc'];
						$form3->remove("group_".$val['id']); 
					}
			} catch (Exception $e) {
				$notice = $lang['admin']['failedpart'];
			}
			
			$site->addcontent(notice($notice));
		}
		
		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['admin']['modifyusers']);
		
		$dialogitem1 = new CodeKBTemplate("dialogitem");
		
		$content = $form1->head();
		$content .= $lang['admin']['changeuserexplain']."<br /><br />\n"; 
		$dialogitem1->push("head", $content);
		
		$dialogitem1->push("content1", $form1->get());
		$dialogitem1->push("tail", $form1->tail());

		$dialogitem2 = new CodeKBTemplate("dialogitem");
		
		$content = $form2->head();
		$content .= $lang['admin']['joinuserexplain']."<br /><br />\n"; 
		$dialogitem2->push("head", $content);
		$dialogitem2->push("content1", $form2->get());
		$dialogitem2->push("tail", $form2->tail());

		$dialogitem3 = new CodeKBTemplate("dialogitem");
		
		$content = $form3->head();
		$content .= $lang['admin']['partuserexplain']."<br /><br />\n"; 
		$dialogitem3->push("head", $content);
		
		$content = "<div class = \"forms\">";
		$content .= $form3->get();
		$content .= "</div>";
		$dialogitem3->push("content1", $content);
		$dialogitem3->push("tail", $form3->tail());
		
		$dialogcode = $dialogitem1->__toString();
		$dialogcode .= $dialogitem2->__toString();
		$dialogcode .= $dialogitem3->__toString();
		
		$dialog->push("content", $dialogcode);
		
		$site->addcontent($dialog);

		return true;
	
	}  // showusermod	

	
?>