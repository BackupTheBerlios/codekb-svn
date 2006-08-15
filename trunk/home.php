<?php

	require_once("includes/global.php");
	
	$user = null;	
	$site = null;

	$user = new CodeKBUser();
	$site = new CodeKBSite($user);

	$site->registermain("main");
	
	
	$site->start();
	
	$site->output();
	
	
	function main() {
		
		global $site; 
		
		$help = new CodeKBHelp();
		$help->load("home");
		
		$site->addcontent($help);
		
	} // main

?>

