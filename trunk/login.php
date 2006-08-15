<?php

	require_once("includes/global.php");
	
	$user = null;	
	$site = null;

	$user = new CodeKBUser();
	$site = new CodeKBSite($user);

	$site->registerfunction("login", "showlogin", true);
	$site->registerfunction("logout", "showlogout");
	$site->registerfunction("register", "showregister");
	$site->registerfunction("registered", "showregistered");

	
	$site->start();
	
	$site->output();
	
	
	function showlogin() {

		global $lang;
		global $site;
		global $user;
		
		$site->title($lang['login']['title']);
		
		$site->addfooter("help.php?on=login", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("home.php");

		$form = new CodeKBForm("login.php", "login");
		$form->addtext("user");
		$form->addlabel("user", $lang['general']['username']);
		$form->setrequired("user");
		
		$form->addpassword("password");
		$form->addlabel("password", $lang['general']['password']);
		$form->setrequired("password");
		
		$form->addcheckbox("cookie", $lang['login']['cookie']);
		
		$form->addsubmit();
		$form->addcancel();
		
		if ($_POST['submit']) {
			if (!$form->fill())
				$site->addcontent(notice($lang['general']['missing']));
			else {
				try {
			
					$user->login($form->value("user"), $form->value("password"));
					if ($form->value("cookie"))
						$user->cookie();
					redirect("home.php");				
			
				} catch (Exception $e) {
				
					// A small penalty
					sleep(3);
					$site->addcontent(notice($lang['login']['failed']));
					$form->setmissing("user");
					$form->setmissing("password");
				}
			}
		}

		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['login']['title']);
		
		$content = $form->head();
		$content .= phrasereplace($lang['login']['description'], "%1%", url("login.php?action=register", $lang['login']['register']))."<br /><br />\n";
		$content .= $form->get();
		$content .= $form->tail();
	
		$dialog->push("content", $content);
		
		$site->addcontent($dialog); 		
			
		return true;

	} // showlogin
			
	
	function showlogout() {

		global $lang;
		global $user;
		global $site;

		$site->title($lang['logout']['title']);
		
		$site->addfooter("help.php?on=login", "help", $lang['menu']['help'], $lang['menu']['helpalt']);
		
		$user->logout();

		$site->addcontent(notice($lang['logout']['description']));
	
		return true;

	} // showlogout

	function showregister() {

		global $lang;
		global $site;
		global $user;
		
		$site->title($lang['register']['title']);
		
		$site->addfooter("help.php?on=login", "help", $lang['menu']['help'], $lang['menu']['helpalt']);

		if ($_POST['cancel'])
			redirect("home.php");

		$form = new CodeKBForm("login.php", "register");
		$form->addtext("user");
		$form->addlabel("user", $lang['general']['username']);
		$form->setrequired("user");
		
		$form->addpassword("password");
		$form->addlabel("password", $lang['general']['password']);
		$form->setrequired("password");
		
		$form->addpassword("password2");
		$form->addlabel("password2", $lang['register']['passwordagain']);
		$form->setrequired("password2");
		
		$form->addsubmit();
		$form->addcancel();
		
		if ($_POST['submit']) {
			if (!$form->fill())
				$site->addcontent(notice($lang['general']['missing']));
			else {
				if ($form->value("password") != $form->value("password2")) {
					$site->addcontent(notice($lang['register']['wrongpass']));
					$form->setmissing("password");
					$form->setmissing("password2");
				} else {
					try {
			
						$user->register($form->value("user"), $form->value("password"));
						redirect("login.php?action=registered");				
					
					} catch (Exception $e) {
				
						if ($e->getCode() == 1) {
							$site->addcontent(notice($lang['register']['duplicate']));
							$form->setmissing("user");
							$form->setmissing("password");
							$form->setmissing("password2");
						} else 
							$site->addcontent(notice($lang['register']['failed']));
					}
				}
			}
		}

		$dialog = new CodeKBTemplate("dialog");
		
		$dialog->push("legend", $lang['register']['title']);
		
		$content = $form->head();
		$content .= $lang['register']['description']."<br /><br />\n";
		$content .= $form->get();
		$content .= $form->tail();
	
		$dialog->push("content", $content);
		
		$site->addcontent($dialog); 		
			
		return true;

	} // showregister

	function showregistered() {

		global $lang;
		global $user;
		global $site;

		$site->title($lang['register']['title']);
		
		$site->addcontent(notice($lang['register']['success']));
	
		return true;

	} // showlogout
	
	
?>
