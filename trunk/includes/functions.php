<?php

// Using StringParser_BBCode to parse bbcode

require_once $conf['bbcode']['path']."/stringparser_bbcode.class.php";

$bbcode = new StringParser_BBCode ();

function parsebbcode($text) {
	
	global $bbcode;
	
	return $bbcode->parse ($text);
	
	
} // parsebbcode

// Using GeSHi to highlight code

function bbcodehighlight ($action, $attributes, $content, $params, &$node_object) {	
	
	global $conf;
	
	if ($action == "validate") {
    	if (!isset ($attributes['default']) || (isset ($attributes['default']) && in_array($attributes['default'], $conf['highlight']['languages'])))
	    	return true;
	    return false;
	}
	
	//$content = stripslashes($content);
	
	if (!isset ($attributes['default']))
		$attributes['default'] = "text"; 
		
	require_once $conf['highlight']['path']."geshi.php";
	$geshi =& new GeSHi($content, $attributes['default']); //$attributes['default']);
	$geshi->set_header_type(GESHI_HEADER_NONE);
	if ($conf['highlight']['linenumbers'])
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
	$geshi->set_tab_width($conf['highlight']['tab']);
	//$geshi->enable_strict_mode(true);
	$content =  $geshi->parse_code();

	$code =  "<table class=\"bb_codebox\">\n\t<tr>\n\t\t<th>";
	$code .= "<strong>".$attributes['default'].":</strong> \n\t\t</th>\n\t</tr><tr>\n";
	$code .= "\t\t<td>";
	$code .= $content;
	$code .= "\t\t</td>\n\t</tr>\n</table>";

	return $code;
	
} // bbcodehighlight

function bbcodeurl ($action, $attributes, $content, $params, &$node_object) {

	if ($action == "validate")
    	return true;
	$target = "";
	if (!isset ($attributes['default'])) {
		if (stripos($content, "http") === 0)
			$target = "target = \"_blank\"";
		return "<a href=\"".htmlspecialchars($content)."\" {$target}>".htmlspecialchars($content)."</a>";
	}
	else {
		if (stripos($attributes['default'], "http") === 0)
			$target = "target = \"_blank\"";
		return "<a href=\"".htmlspecialchars($attributes['default'])."\" {$target}>".$content."</a>";
	}

} // bbcodeurl

function bbcodeimg ($action, $attributes, $content, $params, &$node_object) {

	if ($action == "validate")
    	return true;

	if (!isset ($attributes['default']))
		return "<img src=\"".htmlspecialchars($content)."\" style=\"border: 0px; vertical-align: middle;\" />";
	else
		return "<img src=\"".htmlspecialchars($attributes['default'])."\" style=\"border: 0px; vertical-align: middle;\" alt=\"".htmlspecialchars($content)."\" title=\"".htmlspecialchars($content)."\" />";

} // bbcodeimg

function bbcodesize ($action, $attributes, $content, $params, &$node_object) {

	if ($action == "validate")
    	return true;

	switch ($attributes['default']) {
		
		case "1": 	$size = "xx-small";
					break;
		case "2": 	$size = "x-small";
					break;
		case "3": 	$size = "small";
					break;
		case "4": 	$size = "medium";
					break;
		case "5": 	$size = "large";
					break;
		case "6": 	$size = "x-large";
					break;
		case "7": 	$size = "xx-large";
					break;				
		default: 	return $content;																										
	}
	
	return "<span style=\"font-size: ".htmlspecialchars($size)."\">".$content."</span>";

} // bbcodesize

function bbcodeanker ($action, $attributes, $content, $params, &$node_object) {

	if ($action == "validate")
    	return true;
	
	return "<a name=\"".htmlspecialchars($attributes['default'])."\"></a>";

} // bbcodeanker

function bbcodepre ($action, $attributes, $content, $params, &$node_object) {

	if ($action == "validate")
    	return true;
	
	return "<span style=\"font-family: monospace \">".$content."</span>";

} // bbcodepre


$bbcode->addParser (array ("block", "inline", "list", "link", "img"), "htmlspecialchars");
$bbcode->addParser (array ("block", "inline", "list", "link", "img"), "nl2br");

$bbcode->addCode ("b", "simple_replace", null, array ("start_tag" => "<strong>", "end_tag" => "</strong>"),
                  "inline", array ("block", "inline", "link", "list", "img"), array ());
$bbcode->addCode ("i", "simple_replace", null, array ("start_tag" => "<em>", "end_tag" => "</em>"),
                  "inline", array ("block", "inline", "link", "list", "img"), array ());                  
$bbcode->addCode ("u", "simple_replace", null, array ("start_tag" => "<u>", "end_tag" => "</u>"),
                  "inline", array ("block", "inline", "list", "link", "img"), array ());                  
$bbcode->addCode ("-", "simple_replace_single", null, array ("start_tag" => "&nbsp;&nbsp;&nbsp;&nbsp;"),
                  "inline", array ("block", "inline", "list", "link", "img"), array ());                  
$bbcode->addCode ("center", "simple_replace", null, array ("start_tag" => "<div style=\"text-align: center\">", "end_tag" => "</div>"),
                  "inline", array ("block", "inline", "list", "link", "img"), array ());                  
$bbcode->addCode ("--", "simple_replace_single", null, array ("start_tag" => "<hr style=\"width: 70%; text-align: center\">"),
                  "inline", array ("block", "inline", "list", "link", "img"), array ());                  
$bbcode->addCode ("list", "simple_replace", null, array ("start_tag" => "<ul>", "end_tag" => "</ul>"),
                  "list", array ("block", "inline", "list"), array ());                  
$bbcode->addCode ("olist", "simple_replace", null, array ("start_tag" => "<ol>", "end_tag" => "</ol>"),
                  "list", array ("block", "inline", "list"), array ());                  
$bbcode->addCode ("*", "simple_replace_single", null, array ("start_tag" => "<li>"),
                  "listelem", array ("block", "list"), array ());                  
$bbcode->addCode ("url", "usecontent?", "bbcodeurl", array ("usecontent_param" => "default"),
                  "link", array ("block", "inline", "list"), array ("link"));
$bbcode->addCode ("img", "usecontent?", "bbcodeimg", array ("usecontent_param" => "default"),
                  "img", array ("block", "inline", "link", "list"), array ("img"));                  
$bbcode->addCode ("code", "usecontent?", "bbcodehighlight", array ("usecontent_param" => "default"),
                  "code", array ("block", "inline"), array ("code"));
$bbcode->addCode ("size", "usecontent?", "bbcodesize", array ("usecontent_param" => "default"),
                  "inline", array ("block", "inline", "link", "list", "img"), array ());
$bbcode->addCode ("anker", "callback_replace_single", "bbcodeanker", array ("default"),
                  "inline", array ("block", "inline", "link", "list", "img"), array ());                  
$bbcode->addCode ("pre", "usecontent", "bbcodepre", array (),
                  "inline", array ("block", "inline", "list", "link", "img"), array ());                  


function phrasereplace($text, $where, $what) {
	
	// first test if there is something to replace
	$pos = strpos($text, $where);
	if (is_null($pos))
		return $text;
	
	$text = substr_replace($text, $what, $pos, 3);
	
	return $text; 
	
} // phrasereplace

function img($src, $text = null, $style = null, $width = null, $height = null) { 
	
	global $conf;
	
	if (!strpos($src, "://"))
		$src = $conf['general']['imagepath']."/".$src;
		
	$image = "<img src=\"".htmlentities($src)."\" style=\"border: 0px; ";
	if ($style)
		$image .= htmlentities($style);
	if (!is_null($width))
		$image .= "width: ".htmlentities($width)."; ";
	if (!is_null($height))
		$image .= "height: ".htmlentities($height)."; ";
	$image .= "\" ";
	if (!is_null($text))
		$image .= "alt = \"".htmlentities($text)."\" title = \"".htmlentities($text)."\" ";
	
	$image .= " />";
	
	return $image;

} // img

function icon($name, $text) {

	$db = new CodeKBDatabase();
	
	$db->dosql("SELECT symbol ".
						"FROM symbols ".
						"WHERE name = '{$db->string($name)}'");
								
	$symbol = $db->column("symbol");
	if (is_null($symbol))
		return "";
		
	global $conf;
	
	return img("/icons/".$symbol, ($text?$text:$name), "vertical-align: middle;");
	
} // icon

function url($url, $text, $title = null, $extern = null) {

	global $conf; 
	
	$target = "";
	if (strpos($url, "://") || $extern)
		$target .= "target = \"_blank\" ";
	else {
		if ($conf['general']['rewrite'])
			$url = rewrite($url);
	}
			
	$link = "<a href = \"".htmlentities($url)."\" ";
	if (!is_null($title))
		$link .= "title = \"".htmlentities($title)."\"";
	$link .= $target.">";
	$link .= $text;
	$link .= "</a>";
	
	return $link;
	
} // url

function notice($message) {
	
	$notice = new CodeKBTemplate("notice");
	$notice->push("message", htmlentities($message));
	return $notice;
	
} // notice

function redirect($url) {
	
	header("Location: ".$url);
    die();
	
} // redirect

// Beautify url when using mod_rewrite

function rewrite($url) {

	// TODO!
	
	return $url;
	
} // rewrite


?>
