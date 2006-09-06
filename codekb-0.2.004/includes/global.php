<?php

// Global file for inclusions
// include everything we have in the right order

if (!defined('codeKB_global')) {
	
define ('codeKB_global',true);

require_once("config.php");
require_once($conf['general']['language']);

require_once("class_error.php");
require_once("class_database.php");
require_once("class_user.php");
require_once("class_admin.php");
require_once("class_category.php");
require_once("class_entry.php");
require_once("class_file.php");
require_once("class_form.php");
require_once("class_templates.php");
require_once("class_site.php");
require_once("class_help.php");
require_once("functions.php");

}

?>
