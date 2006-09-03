<?php

	
	class CodeKBForm {
		
		private $_target = "";
		
		private $_action = "";
		
		private $_fields = array();
		
		private $_multipart = false; 
		
		public function __construct($target, $action) {
			
			$this->_target = $target;
			$this->_action = $action;
			$this->addhidden("action", $action);
			
		} // construct
		
		public function fill() {
			
			if (!$_POST)
				return true;
				
			$succ = true;
			
			foreach ($this->_fields as $val) {
					$id = key($this->_fields);

					switch ($val['_type_']) {
						
						case "radio":		foreach ($this->_fields[$id] as $val1) {
												$id1 = key($this->_fields[$id]);
												if (is_array($val1)) {
													if ($_POST[$id] == $id1)
														$this->_fields[$id][$id1]['_checked_'] = true;
													else
														$this->_fields[$id][$id1]['_checked_'] = false;
												}
												next($this->_fields[$id]);
											}
											break;
						case "checkbox":	$this->_fields[$id]['_checked_'] = $_POST[$id];
											break;
						case "multiselect":	foreach ($this->_fields[$id] as $val1) {
												$id1 = key($this->_fields[$id]);
												if (is_array($val1)) {
													if (is_array($_POST[$id]) && in_array($id1, $_POST[$id]))
														$this->_fields[$id][$id1]['_selected_'] = true;
													else
														$this->_fields[$id][$id1]['_selected_'] = false;
												}
												next($this->_fields[$id]);
											}
											break;
						case "combo":		foreach ($this->_fields[$id] as $val1) {
												$id1 = key($this->_fields[$id]);
												if (is_array($val1)) {
													if ($_POST[$id] == $id1)
														$this->_fields[$id][$id1]['_selected_'] = true;
													else
														$this->_fields[$id][$id1]['_selected_'] = false;
												}
												next($this->_fields[$id]);
											}
											break;
						case "file":
						case "button":
						case "hidden":		break;
						default:			$this->_fields[$id]['_value_'] = stripslashes($_POST[$id]);
						
					}
					if (!$_POST[$id] && $this->_fields[$id]['_required_']) {
						$this->_fields[$id]['_missing_'] = true;
						$succ = false;		
					}			
					next($this->_fields);

			}
			return $succ;
			
		} // fill
		
		public function value($id) {
			
			return $_POST[$id];
			
		} // value
		
		public function addlabel($id, $text) {
			
			$this->_fields[$id]['_label_'] = $text;
			
		} // addlabel
		
		public function setmissing($id) {
			
			$this->_fields[$id]['_missing_'] = true;
			
		} // setmissing
		
		public function setrequired($id) {
			
			$this->_fields[$id]['_required_'] = true;
			
		} // setrequired
		
		public function addhidden($id, $value) {

			$this->_fields[$id]['_type_'] = "hidden";
			$this->_fields[$id]['_value_'] = $value;			
			
		} // addhidden
		
		public function addtext($id, $value = null) {
			
			$this->_fields[$id]['_type_'] = "text";
			$this->_fields[$id]['_value_'] = $value;
			
		} // addtext

		public function addpassword($id, $value = null) {
			
			$this->_fields[$id]['_type_'] = "password";
			$this->_fields[$id]['_value_'] = $value;
			
		} // addtext
		
		public function addcheckbox($id, $text = null, $checked = null) {
			
			$this->_fields[$id]['_type_'] = "checkbox";
			$this->_fields[$id]['_checked_'] = $checked;
			$this->_fields[$id]['_text_'] = $text;		
		
		} // addcheckbox
		
		public function addradio($id, $value, $text = null, $checked = null, $break = true) {
			
			$this->_fields[$id]['_type_'] = "radio";
			$this->_fields[$id][$value]['_checked_'] = $checked;
			$this->_fields[$id][$value]['_text_'] = $text;
			$this->_fields[$id][$value]['_break_'] = $break;		
		
		} // addradio
		
		public function addfile($id) {
			
			$this->_fields[$id]['_type_'] = "file";
			$this->_multipart = true;
		
		} // addfile
		
		public function addtextarea($id, $value = null) {
			
			$this->_fields[$id]['_type_'] = "textarea";
			$this->_fields[$id]['_value_'] = $value;
		
		} // addtextarea

		public function addcombo($id, $value, $text = null, $selected = null, $autosend = null) {
			
			$this->_fields[$id]['_type_'] = "combo";
			$this->_fields[$id][$value]['_selected_'] = $selected;
			if ($text)
				$this->_fields[$id][$value]['_text_'] = $text;
			else
				$this->_fields[$id][$value]['_text_'] = $value;
			if ($autosend)
				$this->_fields[$id]['_autosend_'] = $autosend;
		
		} // addcombo

		public function addmultiselect($id, $value, $text = null, $selected = null) {
			
			$this->_fields[$id]['_type_'] = "multiselect";
			$this->_fields[$id][$value]['_selected_'] = $selected;
			if ($text)
				$this->_fields[$id][$value]['_text_'] = $text;
			else
				$this->_fields[$id][$value]['_text_'] = $value;
		
		} // addmultiselect
		
		public function addbutton($id, $text = null) {
			
			global $lang;
			
			if ($id == "submit" && !$text)
				$text = $lang['general']['submit'];
			if ($id == "cancel" && !$text)
				$text = $lang['general']['cancel'];
			
			$this->_fields[$id]['_type_'] = "button";
			$this->_fields[$id]['_text_'] = $text;				
			
		} // addbutton

		public function remove($id, $subid = null) {
			
			if ($subid)
				unset($this->_fields[$id][$subid]);
			else
				unset($this->_fields[$id]);

		} // remove
		
		private function output($id) {
			
			global $conf;
			
			$out = "";
			
			if ($this->_fields[$id]['_missing_'])
				$out .= "\t<span class=\"notice\">\n";
			
			if ($this->_fields[$id]['_label_']) {
				$out .= "\t<label for = \"".htmlentities($id)."\">".$this->_fields[$id]['_label_'];
				if ($this->_fields[$id]['_required_'])
					$out .= "*";	
				$out .= "</label>\n";
			}
			
			switch ($this->_fields[$id]['_type_']) {
				
				case "text": 		$out .= "\t<input type = \"text\" name = \"".htmlentities($id)."\" value = \"".htmlentities($this->_fields[$id]['_value_'])."\" maxlength = \"255\">\n";
									break;
				case "password": 		$out .= "\t<input type = \"password\" name = \"".htmlentities($id)."\" value = \"".htmlentities($this->_fields[$id]['_value_'])."\" maxlength = \"255\">\n";
									break;
				case "checkbox": 	$out .= "\t<label></label>\n";
									$out .= "\t<nobr><input type = \"checkbox\" name = \"".htmlentities($id)."\" value = \"1\" class = \"radio\" ";
									if ($this->_fields[$id]['_checked_'])
										$out .= "checked = \"checked\"";
									$out .= "> ".$this->_fields[$id]['_text_']."</nobr>\n";
									break;
				case "radio":		foreach($this->_fields[$id] as $val) {
										if (is_array($val)) {
											$out .= "<nobr>";
											if ($val['_break_'])
												$out .= "\t<label></label>\n";
											$out .= "\t<input type = \"radio\" name = \"".htmlentities($id)."\" value = \"".htmlentities(key($this->_fields[$id]))."\" class = \"radio\" ";
											if ($val['_checked_'])
												$out .= "checked = \"checked\"";
												
											$out .= "> ".$val['_text_']."</nobr>\n";
										}
										next($this->_fields[$id]);
									}
									$out .= "\t\n";
									break;
				case "combo":		$combo = true;
				case "multiselect": if ($combo) {
										$out .= "\t<select name = \"".htmlentities($id)."\" size =\"1\" ";
										if ($conf['general']['javascript'] && $this->_fields[$id]['_autosend_'])
											$out .= "onchange=\"this.form.".htmlentities($this->_fields[$id]['_autosend_']).".click();\"";
										$out .= ">\n";
									}
									else
										$out .= "\t<select name = \"".htmlentities($id)."[]\" size =\"5\" multiple=\"multiple\">\n";
										 
									foreach ($this->_fields[$id] as $val) {
										if (is_array($val)) {
											$out .= "\t\t<option value = \"".htmlentities(key($this->_fields[$id]))."\" ";
											if ($val['_selected_'])
												$out .= "selected = \"selected\"";
											$out .= ">".htmlentities($val['_text_'])."</option>\n";
										}
										next($this->_fields[$id]);
									}
									$out .= "\t</select>\n";
									break;
				case "textarea":	$out .= "\t<textarea name = \"".htmlentities($id)."\">";
									$out .= htmlentities($this->_fields[$id]['_value_']);
									$out .= "</textarea>\n";
									break;
				case "file": 		$out .= "\t<input type = \"file\" name = \"".htmlentities($id)."\" class = \"file\">\n";
									break;
				case "button":		$out .= "\t<input type = \"submit\" name = \"".htmlentities($id)."\" ";
									if ($this->_fields[$id]['_text_'])
										$out .= "value = \"".htmlentities($this->_fields[$id]['_text_'])."\" ";
									$out .= "class = \"button\">\n";
									break;
				case "hidden":		$out .= "\t<input type = \"hidden\" name = \"".htmlentities($id)."\" value = \"".htmlentities($this->_fields[$id]['_value_'])."\">\n";
									break;

			}
			
			if ($this->_fields[$id]['_missing_'])
				$out .= "\t</span>\n";
				
			unset($this->_fields[$id]);
			
			return $out;
			
		} // output
		
		public function get($id = false) {
			
			$out = "";
					
			if ($id) {
				$out = $this->output($id);
				unset($this->_fields[$id]);
			}
			else {
				foreach ($this->_fields as $val)
					if ($val['_type_'] != "button") {
                    	$code = $this->output(key($this->_fields));
                        $out .= $code.($code?"<br /><br />":"");
                    } else
                    	next($this->_fields);
			}
				
			return $out;
			
		} // get
		
		public function head() {
			
			$out = "\n<form action = \"".htmlentities($this->_target)."?action=".htmlentities($this->_action)."\" method = \"POST\" ";
			if ($this->_multipart)
				$out .= "enctype = \"multipart/form-data\"";			
			$out .= ">\n\n";
			
			foreach ($this->_fields as $val) {
				if ($val['_type_'] == "hidden")
					$out .= $this->output(key($this->_fields));
				else
					next($this->_fields);
			}	
							
			return $out;		
			
		} // head
		
		public function tail() {
			
			$out = "";
			foreach ($this->_fields as $val)
				if ($val['_type_'] == "button") {
					$code = $this->output(key($this->_fields));
					$out .= $code."";
				}  else
                    	next($this->_fields);

			$out .= "\n</form>\n";
			
			return $out;
			
		} // tail


	} // class CodeKBForm

?>
