<?php

	require_once("includes/global.php");
	
	$help = 0;
	$user = null;	
	$site = null;

	$user = new CodeKBUser();
	$site = new CodeKBSite($user);

	$site->registerfunction("show", "showhelp", true);

	$site->registervariable("on", $help);
	
	$site->start();
	
	$site->output();
	
	
	function showhelp() {
		
		global $lang;
		global $user;
		global $site;
		global $conf;
		global $help;
		
		$site->title($lang['help']['title']);
		
		try {
			$topic = new CodeKBHelp();
			if (!$help)
				$help = "index";
			$topic->load($help);
		} catch (Exception $e) {
			$site->addcontent(notice($lang['help']['nosuchtopic']));
			return false;
		}
		
		$site->addmenu("help.php", $lang['menu']['helpbrowse'], $lang['menu']['helpbrowsealt']);
		$site->addfooter("help.php", "search", $lang['menu']['helpbrowse'], $lang['menu']['helpbrowsealt']);
				


		$site->addcontent($topic);

		return true;
	
	} // showlhelp


?>