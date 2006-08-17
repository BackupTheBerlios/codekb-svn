<?php

// general
$conf['general']['basepath'] = ".";							// absolute path to codeKB
$conf['general']['wwwpath'] = "/codeKB";											// www document root of codeKB
$conf['general']['imagepath'] = $conf['general']['wwwpath']."/images";				// path to images
$conf['general']['language'] = "phrases.php";										// language phrase file
$conf['general']['rewrite'] = true;												// use mod_rewrite?
$conf['general']['title'] = "codeKB - Source Code Knowledge Base";					// browser title
$conf['general']['stylesheet'] = "codekb.css";										// relative stylesheet path 

// database settings
$conf['db']['type'] = "pgsql";								// pgsql or mysql
$conf['db']['host'] = "";
$conf['db']['port'] = "";
$conf['db']['name'] = "";
$conf['db']['user'] = "";
$conf['db']['pass'] = "";

// access settings
$conf['access']['admin'] = array("");											// admin user - empty for no restrictions

// file settings
$conf['file']['path'] = $conf['general']['basepath']."/data/"; 					// secured file storage

// syntax highlighting
$conf['highlight']['path'] = $conf['general']['basepath']."/includes/highlight/";	// path to GeSHi
$conf['highlight']['linenumbers'] = true;											// show line numbers
$conf['highlight']['tab'] = 4;														// tab width
$conf['highlight']['languages'] = array("bash", "batch", "c", "cpp", "diff", 
										"html", "ini", "java", "javascript", 
										"perl", "php", "python", "sql", "tcl", 
										"text", "vb", "xml"); 						// available highlight languages
$conf['highlight']['binary'] = "binary";											// The descriptor for binary files										

// bbcode parser
$conf['bbcode']['path'] = $conf['general']['basepath']."/includes/bbcode/";		// path to bbcode parser

// errors and debugging

$conf['err']['phperrors'] = true;													// showing php errors?
$conf['err']['debug'] = true;														// show backtrace

// some layout settings
$conf['layout']['showcounts'] = true;												// Show counts behind categories
$conf['layout']['showcountsrecursive'] = true;										// Show counts of subcategories too - maybe slow!!
$conf['layout']['dateformat'] = "Y-m-d H:i:s";										// PHP format string for timestamps
$conf['layout']['entriesperpage'] = 25;											// Default number of entries per page

// performance
$conf['perf']['rightscache'] = 30;													// How many entries in the access right cache



?>
