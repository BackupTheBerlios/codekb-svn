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

