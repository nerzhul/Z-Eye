<?php
	/*
	* Copyright (c) 2010-2014, Loïc BLOT, CNRS <http://www.unix-experience.fr>
	* All rights reserved.
	*
	* Redistribution and use in source and binary forms, with or without
	* modification, are permitted provided that the following conditions are met:
	*
	* 1. Redistributions of source code must retain the above copyright notice, this
	*    list of conditions and the following disclaimer.
	* 2. Redistributions in binary form must reproduce the above copyright notice,
	*    this list of conditions and the following disclaimer in the documentation
	*    and/or other materials provided with the distribution.
	*
	* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	* DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	* ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*
	* The views and conclusions contained in the software and documentation are those
	* of the authors and should not be interpreted as representing official policies,
	* either expressed or implied, of the FreeBSD Project.
    */

	require_once(dirname(__FILE__)."/FS.main.php");
	require_once(dirname(__FILE__)."/HTMLTableMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/objects/Locales".CLASS_EXT);
	require_once(dirname(__FILE__)."/objects/Rules".CLASS_EXT);

	abstract class FSInterfaceMgr {
		function __construct() {
			$this->InitComponents();
		}

		public function InitComponents() {
			$this->arr_css = array();
			$this->arr_js = array();
			$this->title = "";

			$this->echo_buffer = "";

			// Create 2 JS buffers
			$this->js_buffer = array();
			for ($i=0;$i<2;$i++) {
				$this->js_buffer[] = "";
			}
			$this->js_buffer_idx = 0;
			$this->moduleIdBuf = array();
		}

		// header/footer/content

		public function header() {
			$output = sprintf("<!DOCTYPE html><html lang=\"%s\">
				<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
				<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />
				<head><title>%s%s</title>",
				Config::getSysLang(),Config::getWebsiteName(),(strlen($this->title) > 0 ? " - ".$this->title : ""));

			$count = count($this->arr_css);
			for ($i=0;$i<$count;$i++) {
				$output = sprintf("%s<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />",
					$output,$this->arr_css[$i]);
			}

			if (Config::hasFavicon()) {
				$output = sprintf("%s<link rel=\"shortcut icon\" href=\"/styles/images/favicon.png\" />",
					$output);
			}

			$count = count($this->arr_js);
			for ($i=0;$i<$count;$i++) {
				$output = sprintf("%s<script type=\"text/javascript\" src=\"%s\"></script>",
					$output,$this->arr_js[$i]);
			}

			return sprintf("%s</head><body>",$output);
		}

		public function footer() {
			return "</body></html>";
		}

		public function content() {
			return "<div id=\"main\"></div>";
		}

		public function loadMenus() {
			$output = "";
			$found = false;
			$moduleid = 0;
			$menus = array();

			$dir = opendir(dirname(__FILE__)."/../../modules/");
			while($elem = readdir($dir)) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) {
					$moduleid++;
					$dir2 = opendir($dirpath);
					while($elem2 = readdir($dir2)) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "module.php") {
							$path = $elem;
							require(dirname(__FILE__)."/../../modules/".$path."/module.php");
							$menuname = $module->getMenu();
							if ($menuname && $module->getRulesClass()->canAccessToModule() === true) {
								if (!isset($menus[$menuname])) {
									$menus[$menuname] = array();
								}
								if ($module->getRulesClass()->canAccessToModule() === true) {
									$menus[$menuname][] =
										sprintf("<div class=\"menuItem\" onclick=\"loadInterface('%s');\">%s</div>",
										$moduleid,$module->getMenuTitle());
								}
							}
						}
					}
				}
			}

			foreach ($menus as $menuname => $elemts) {
				$locoutput = "";

				for ($i=0;$i<count($elemts);$i++) {
					$locoutput = sprintf("%s%s",$locoutput,$elemts[$i]);
				}

				$output = sprintf("%s<div id=\"menuStack\"><div id=\"menuTitle\">%s</div>
					<div class=\"menupopup\">%s</div></div>",
					$output,$menuname,$locoutput);
			}
			return $output;
		}

		public function loadFooterPlugins() {
			FS::$iMgr->js("clearFooterPlugins();");

			$dir = opendir(dirname(__FILE__)."/../../modules/");
			while($elem = readdir($dir)) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) {
					$dir2 = opendir($dirpath);
					while($elem2 = readdir($dir2)) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "module.php") {
							require(dirname(__FILE__)."/../../modules/".$elem."/module.php");
							if ($module->getRulesClass()->canAccessToModule() === true) {
								FS::$iMgr->setCurrentModule($module);
								$module->loadFooterPlugin();
							}
						}
					}
				}
			}
		}

		private function findModulePath($id) {
			$dir = opendir(dirname(__FILE__)."/../../modules/");
			$moduleid = 0;
			while(($elem = readdir($dir))) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) {
					$moduleid++;
				}
				if (is_dir($dirpath) && $moduleid == $id) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2))) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "module.php")
							return $elem;
					}
				}
			}

			return NULL;
		}

		public function loadModule($id,$act=1) {
			if ($path = $this->findModulePath($id)) {
				require(dirname(__FILE__)."/../../modules/".$path."/module.php");

				$ruleAccess = $module->getRulesClass()->canAccessToModule();
				// Bypass access for Android
				if ($ruleAccess === true || $act == 3) {
					$this->setCurrentModule($module);
					$module->setModuleId($id);
					switch($act) {
						case 1: default: return $module->Load(); break;
						case 2: return $module->getIfaceElmt(); break;
						case 3: return $module->LoadForAndroid(); break;
					}
				}
				else if($ruleAccess === -1) {
					$this->js(sprintf("setLoginCbkMsg('<span style=\"color: red;\">%s</span>');openLogin();",
						$this->getLocale("err-must-be-connected")));
					return "";
				}
				else {
					return $this->printNoRight("access to module");
				}
			}
			return $this->printError("err-unk-module");
		}

		public function getModuleByPath($path) {
			if ($path = $this->findModulePath($this->getModuleIdByPath($path))) {
				require(dirname(__FILE__)."/../../modules/".$path."/module.php");
				return $module;
			}
			return NULL;
		}

		public function getRealNameById($id) {
			$user = new User();
			$user->LoadFromDB($id);
			return $user->getSubName()." ".$user->getName();
		}

		public function backLink() {
			return sprintf("<div id=\"backarrow\"><a href=\"javascript:history.back()\">%s</a></div>",
				FS::$iMgr->img("styles/back.png",32,32));
		}

		public function aLink($link,$label,$raw=false) {
			return sprintf("<a href=\"%s\">%s</a>",
				$raw ? $link : sprintf("?mod=%s",$link),
				$label
			);
		}

		public function h1($str,$raw=false,$id="") {
			return sprintf("<h1%s>%s</h1>",
				($id ? " id=\"".$id."\"" : ""),($raw ? $str : $this->getLocale($str)));
		}

		public function h2($str,$raw=false,$id="") {
			return sprintf("<h2%s>%s</h2>",
				($id ? " id=\"".$id."\"" : ""),($raw ? $str : $this->getLocale($str)));
		}

		public function h3($str,$raw=false,$id="") {
			return sprintf("<h3%s>%s</h3>",
				($id ? " id=\"".$id."\"" : ""),($raw ? $str : $this->getLocale($str)));
		}

		public function h4($str,$raw=false,$id="") {
			return sprintf("<h4%s>%s</h4>",
				($id ? " id=\"".$id."\"" : ""),($raw ? $str : $this->getLocale($str)));
		}

		public function hr() { return "<div id=\"hr\"></div>"; }

		public function js($js) {
			$js = preg_replace("#[\n]#","",$js);
			$js = preg_replace("#[\r]#","",$js);
			$js = preg_replace("#[\t]#"," ",$js);
			$js = trim($js);
			if (!isset($this->js_buffer[$this->js_buffer_idx])) {
				$this->js_buffer[$this->js_buffer_idx] = "";
			}
			$this->js_buffer[$this->js_buffer_idx] .= $js;
		}

		public function setJSBuffer($idx) {
			$this->js_buffer_idx = $idx;
		}

		public function renderJS() {
			$jsBuf = "";
			for ($i=0;$i<2;$i++) {
				if (strlen($this->js_buffer[$i]) > 0) {
					$jsBuf = sprintf("%s%s",
						$jsBuf,
						$this->js_buffer[$i]);
				}
			}
			return $jsBuf;
		}

		public function label($for,$value,$class = "") {
			return sprintf("<label class=\"%s\" for=\"%s\">%s</label>",
				$class,$for,$this->getLocale($value));
		}

		public function tooltip($text) {
			return sprintf(" onmouseover=\"showTooltip('%s');\" onmouseout=\"hideTooltip();\" ",
				FS::$secMgr->cleanForJS($this->getLocale($text)));
		}

		public function tip($text,$raw=false) {
			return sprintf("<div id=\"tip\">%s</div>",
				($raw ? $text : $this->getLocale($text)));
		}

		public function textarea($name, $def_value = "", $options=array()) {
			$output = "";
			if (isset($options["label"])) {
				$output = $this->label($name,$options["label"]);
			}

			$output = sprintf("%s<textarea name=\"%s\" id=\"%s\" style=\"width:%spx;height:%spx\" ",
				$output,$name,$name,(isset($options["width"]) ? $options["width"] : 400),
				(isset($options["height"]) ? $options["height"] : 300));

			if (isset($options["tooltip"])) {
				$output = sprintf("%s%s",$output,$this->tooltip($options["tooltip"]));
			}
			if (isset($options["length"])) {
				$output = sprintf("%s maxlength=\"%s\"",$output,$options["length"]);
			}
			$output = sprintf("%s>%s</textarea>",$output,$def_value);
			return $output;
		}

		public function tabledTextArea($label,$name,$options = array()) {
			return sprintf("<tr><td>%s</td><td class=\"ctrel\">%s</td></tr>",
				$label,$this->textarea($name,(isset($options["value"]) ? $options["value"] : ""),$options));
		}

		public function input($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
			if ($label) {
				$output = $this->label($name,$label);
			}

			$output = sprintf("%s<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"%s\" maxlength=\"%s\" ",
				$output,$name,$name,$def_value,$size,$length);

			if ($tooltip) {
				$output = sprintf("%s%s",$output,$this->tooltip($tooltip));
			}

			return sprintf("%s />",$output);
		}

		public function numInput($name, $def_value = "", $options = array()) {
			$output = "";
			if (isset($options["label"])) {
				$output = $this->label($name,$options["label"]);
			}

			$output = sprintf("%s<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"%s\" maxlength=\"%s\" onkeyup=\"javascript:ReplaceNotNumeric(this);\" ",
				$output,$name,$name,$def_value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40));

			if (isset($options["tooltip"])) {
				$output = sprintf("%s%s",$output,$this->tooltip($options["tooltip"]));
			}

			return sprintf("%s />",$output);
		}

		public function IPInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
            if ($label) {
				$output = $this->label($name,$label);
			}

			$output = sprintf("<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"%s\" maxlength=\"%s\" onkeyup=\"javascript:checkIP(this);\" ",
				$name,$name,$def_value,$size,$length);

			if ($tooltip) {
				$output = sprintf("%s%s",$output,$this->tooltip($tooltip));
			}

			return sprintf("%s />",$output);
		}

		public function MacInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL) {
			$output = "";
            if ($label) {
				$output = $this->label($name,$label);
			}
			return sprintf("%s<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"%s\" maxlength=\"%s\" onkeyup=\"javascript:checkMAC(this);\" />",
				$output,$name,$name,$def_value,$size,$length);
		}

		public function IPMaskInput($name, $def_value = "", $size = 20, $length = 40) {
			return sprintf("<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"%s\" maxlength=\"%s\" onkeyup=\"javascript:checkMask(this);\" />",
				$name,$name,$def_value,$size,$length);
		}

		public function colorInput($name, $def_value) {
			return sprintf("<input type=\"textbox\" name=\"%s\" id=\"%s\" value=\"%s\" size=\"6\" maxlength=\"6\" onfocus=\"showColorPicker(this,'%s');\"/>",
				$name,$name,$def_value,$def_value);
		}

		public function autoComplete($name, $options = array()) {
			$output = $this->input($name,"",(isset($options["size"]) ? $options["size"] : 20),
				(isset($options["length"]) ? $options["length"] : 40), (isset($options["label"]) ? $options["label"] : NULL),
				(isset($options["tooltip"]) ? $options["tooltip"] : NULL));

			$this->js(sprintf("$('#%s').autocomplete({source: '?mod=%s&at=2&nojson=1',minLength: 3});",
				$name,$this->getModuleIdByPath("search")));
			return $output;
		}

		public function slider($slidername, $name, $min, $max, $options = array()) {
			if (isset($options["hidden"])) {
				$output = FS::$iMgr->hidden($name,(isset($options["value"]) ? $options["value"] : 0));
			}
			else {
				$output = FS::$iMgr->input($name,(isset($options["value"]) ? $options["value"] : 0));
			}
			$js = sprintf("$(function() {
				$('#%s').slider({ range: 'min', min: %s, max: %s,%sslide: function(event,ui) { $('#%s').val(%s);%s}});});",
				$slidername, $min, $max,
				isset($options["value"]) ? sprintf("value: %s,", $options["value"]) : "",
				$name,
				isset($options["valoverride"]) ? $options["valoverride"] : "ui.value",
				isset($options["hidden"]) ? sprintf("$('#%slabel').html(ui.value);", $name) : ""
			);

			$this->js($js);

			return sprintf("%s<div id=\"%s\" %s></div>%s",
				$output, $slidername,
				isset($options["width"]) ? sprintf("style=\"width: %s\"", $options["width"]) : "",
				isset($options["hidden"]) ? sprintf("<br /><span id=\"%slabel\">%s</span>%s<br />",
					$name,
					isset($options["value"]) ? $options["value"] : 0,
					$options["hidden"]) : ""
			);
		}

		public function hidden($name, $value) {
			return sprintf("<input type=\"hidden\" id=\"%s\" name=\"%s\" value=\"%s\" />",
				$name,$name,$value);
		}

		public function password($name, $def_value = "", $label=NULL) {
			return sprintf("%s<input type=\"password\" id=\"%s\" name=\"%s\" value=\"%s\" />",
				$label ? $this->label($name, $label) : "",
				$name,
				$name,
				$def_value
			);
		}

		public function calendar($name, $def_value, $label=NULL) {
			$output = sprintf("%s<input type=\"textbox\" value=\"%s\" name=\"%s\" id=\"%s\" size=\"20\" />",
				$label ? $this->label($name, $label) : "",
				$def_value, $name, $name);

			$js = sprintf("$('#%s').datepicker($.datepicker.regional['fr']);
				$('#%s').datepicker('option', 'dateFormat', 'dd-mm-yy');%s",
				$name, $name,
				$def_value ? sprintf("$('#%s').datepicker('setDate','%s');", $name, $def_value) : ""
			);

			$this->js($js);
			return $output;
		}

		public function hourlist($hname, $mname, $hselect=0, $mselect=0) {
			$output = $this->select($hname);

			for ($i=0;$i<24;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
				$output = sprintf("%s%s",
					$output,
					$this->selElmt($txt,$i,$hselect == $i)
				);
			}
			$output .= "</select> h ".$this->select($mname);
			for ($i=0;$i<60;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
				$output = sprintf("%s%s",
					$output,
					$this->selElmt($txt,$i,$mselect == $i)
				);
			}
			$output = sprintf("%s</select>", $output);

			return $output;
		}

		public function raddbCondSelectElmts($select = "") {
			return FS::$iMgr->selElmt("=","=",$select == "=").
				FS::$iMgr->selElmt("==","==",$select == "==").
				FS::$iMgr->selElmt(":=",":=",$select == ":=").
				FS::$iMgr->selElmt("+=","+=",$select == "+=").
				FS::$iMgr->selElmt("!=","!=",$select == "!=").
				FS::$iMgr->selElmt(">",">",$select == ">").
				FS::$iMgr->selElmt(">=",">=",$select == ">=").
				FS::$iMgr->selElmt("<","<",$select == "<").
				FS::$iMgr->selElmt("<=","<=",$select == "<=").
				FS::$iMgr->selElmt("=~","=~",$select == "=~").
				FS::$iMgr->selElmt("!~","!~",$select == "!~").
				FS::$iMgr->selElmt("=*","=*",$select == "=*").
				FS::$iMgr->selElmt("!*","!*",$select == "!*");
		}

		public function submit($name, $value, $options = array()) {
			return sprintf("<input class=\"buttonStyle\" type=\"submit\" name=\"%s\" id=\"%s\" value=\"%s\" %s />",
				$name, $name, $value,
				isset($options["tooltip"]) ? $this->tooltip($options["tooltip"]) : ""
			);
		}

		public function JSSubmit($name, $value, $function) {
			return sprintf("<input class=\"buttonStyle\" type=\"submit\" name=\"%s\" id=\"%s\" value=\"%s\" onclick=\"%s\" />",
				$name, $name, $value, $function);
		}

		public function button($name, $value, $js) {
			return sprintf("<input class=\"buttonStyle\" type=\"button\" name=\"%s\" value=\"%s\" onclick=\"%s\" />",
				$name,$value,$js);
		}

		public function radio($name, $value, $checked = false, $label="") {
			return sprintf("<input id=\"%s\" type=\"radio\" value=\"%s\" name=\"%s\"%s>%s",
				$name,$value,$name,
				$checked ? " checked=\"checked\"" : "",
				$label
			);
		}

		public function radioList($name,$values, $labels, $checkid = NULL) {
			if (!is_array($values)) {
				return "";
			}

			$output = "";

			$count = count($values);
			for ($i=0;$i<$count;$i++) {
				$output = sprintf("%s%s<br />", $output,
					$this->radio($name, $values[$i], ($checkid == $values[$i]), $labels[$i]));
			}

			return $output;
		}

		public function form($link,$options=array()) {
			return sprintf("<form action=\"%s\" %smethod=\"%s\"%s>",
				$link,
				isset($options["id"]) && strlen($options["id"]) > 0 ?
					sprintf("id=\"%s\" ",$options["id"]) : "",
				isset($options["get"]) && $options["get"] ? "GET" : "POST",
				isset($options["js"]) ?
					sprintf(" onsubmit=\"return %s;\" ", $options["js"]) : ""
			);
		}

		public function cbkForm($link, $textid = "Modification",$raw = false, $options=array()) {
			if($raw == false) {
				$link = sprintf("?mod=%s&act=%s",
					$this->cur_module->getModuleId(), $link);
			}
			if (!isset($options["method"]) || $options["method"] != "GET" && $options["method"] != "POST") {
				$options["method"] = "POST";
			}
			return sprintf("<form action=\"%s\" method=\"%s\" onsubmit=\"return callbackForm('%s',this,{'snotif':'%s'});\" >",
				$link,$options["method"],$link,FS::$secMgr->cleanForJS($this->getLocale($textid)));
		}

		public function idxLine($text,$name,$options = array()) {
			$value = (isset($options["value"]) ? $options["value"] : "");
			$label = ((isset($options["rawlabel"]) && $options["rawlabel"] == true) ? $text : $this->getLocale($text));
			$star = (isset($options["star"]) ? $options["star"] : 0);

			$output = "<tr ";
			if (isset($options["tooltip"])) {
				$output .= $this->tooltip($options["tooltip"]);
				unset($options["tooltip"]);
			}

			$output .= "><td>".$label;
			// Stars for tips
			if ($star > 0) {
				$output .= " (";
				for ($i=0;$i<$star;$i++) {
					$output .= "*";
				}
				$output .= ")";
			}
			$output .= "</td><td class=\"ctrel\">";

			if (isset($options["type"])) {
				switch($options["type"]) {
					case "idxedit":
						if (isset($options["edit"]) && $options["edit"]) {
							$output .= $value.FS::$iMgr->hidden($name,$value).FS::$iMgr->hidden("edit",1);
						}
						else {
							$output .= $this->input($name,$value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40),
								(isset($options["label"]) ? $options["label"] : NULL));
						}
						break;
					case "idxipedit":
						if (isset($options["edit"]) && $options["edit"])
							$output .= $value.FS::$iMgr->hidden($name,$value).FS::$iMgr->hidden("edit",1);
						else
							$output .= $this->IPInput($name,$value);
						break;
					case "chk": $options["check"] = $value; $output .= $this->check($name, $options); break;
					case "ip": $output .= $this->IPInput($name,$value); break;
					case "ipmask": $output .= $this->IPMaskInput($name,$value); break;
					case "num": $output .= $this->numInput($name,$value,$options); break;
					case "pwd": $output .= $this->password($name,$value); break;
					case "color": $output .= $this->colorInput($name,$value); break;
					case "area": $output .= $this->textarea($name,$value, $options); break;
					case "calendar": $output .= $this->calendar($name, $value); break;
					// Raw type, to normalize all non idxLine entries
					case "raw": $output .= $value; break;
					default: break;
				}
			}
			else
				$output .= $this->input($name,$value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40),
					(isset($options["label"]) ? $options["label"] : NULL));
			$output .= "</td></tr>";
			return $output;
		}

		public function idxLines($lines = array()) {
			$output = "";
			$lCount = count($lines);

			for ($i=0;$i<$lCount;$i++) {
				$opts = (isset($lines[$i][2]) ? $lines[$i][2] : array());
				$output = sprintf("%s%s",$output,$this->idxLine($lines[$i][0],$lines[$i][1],$opts));
			}
			return $output;
		}

		public function idxIdLine($label,$name,$value = "",$options = array()) {
			if ($value) {
				return sprintf("<tr><td>%s</td><td>%s%s%s</td></tr>",
					$this->getLocale($label), $value,
					FS::$iMgr->hidden($name,$value), FS::$iMgr->hidden("edit",1)
				);
			}

			return $this->idxLine($label,$name,"",$options);
		}

		public function ruleLine($label,$rulename,$rulelist,$idx = "") {
			return sprintf("<tr><td>%s</td><td>%s</td></tr>",
				$idx,$this->check($rulename,array("check" => in_array($rulename,$rulelist),"label" => $label))
			);
		}

		public function ruleLines($idx,$rulelist,$rules = array()) {
			$output = "";

			$count = count($rules);
			for ($i=0;$i<$count;$i++) {
				$output = sprintf("%s%s",$output,
					$this->ruleLine($rules[$i][0],$rules[$i][1],$rulelist,$i == 0 ? $idx : ""));
			}

			return $output;
		}

		public function tableSubmit($label,$options = array()) {
			return sprintf("<tr><th colspan=\"%d\" class=\"ctrel\">%s</th></tr></table></form>",
				isset($options["size"]) ? $options["size"] : 2,
				isset($options["js"]) ?
					$this->JSSubmit((isset($options["name"]) ? $options["name"] : ""),$this->getLocale($label),$options["js"]) :
					$this->submit((isset($options["name"]) ? $options["name"] : ""),$this->getLocale($label))
			);
		}

		// Helper for tabled Add/Submit forms
		public function aeTableSubmit($add = true, $options = array()) {
			return $this->tableSubmit($add ? "Add" : "Save", $options);
		}

		public function jsSortTable($id) {
			$this->js("$('#".$id."').tablesorter();");
		}

		public function formatHTMLId($str) {
			return preg_replace("#[ .]#","-",$str);
		}

		public function progress($name,$value,$max=100,$label=NULL) {
			$output = sprintf("%s<progress id=\"%s\" value=\"%s\" max=\"%s\"></progress><span id=\"prog%sval\"></span>",
				$label ? sprintf("<label for=\"%s\">%s</label> ",$name,$label) : "",
				$name, $value, $max, $name);

			$this->js(sprintf("eltBar = document.getElementById(\"%s\");
				eltPct = document.getElementById(\"prog%sval\");
				eltPct.innerHTML = ' ' + Math.floor(eltBar.position * 100) + \"%%\";",$name,$name));
			return $output;
		}

		public function select($name, $options = array()) {
			$selId = preg_replace("#\[|\]#","",$name);
			$multi = (isset($options["multi"]) && $options["multi"] == true);

			$this->js(sprintf("$('#%s').select2();",$name));

			return sprintf("%s<select name=\"%s%s\" id=\"%s\"%s%s%s%s%s>",
				isset($options["label"]) ?
					sprintf("<label for=\"%s\">%s</label> ",$name,$options["label"]) : "",
				$name, ($multi ? "[]" : ""), $selId,
				isset($options["js"]) ?
					sprintf(" onchange=\"javascript:%s;\" ",$options["js"]) : "",
				$multi ? " multiple=\"multiple\" " : "",
				isset($options["size"]) && FS::$secMgr->isNumeric($options["size"]) ?
					sprintf(" size=\"%s\" ",$options["size"]) : "",
				isset($options["style"]) ?
					sprintf(" style=\"%s\" ",$style) : "",
				isset($options["tooltip"]) ?
					$this->tooltip($options["tooltip"]) : ""
			);

			return $output;
		}

		public function selElmt($name,$value,$selected = false,$disabled = false) {
			return sprintf("<option value=\"%s\"%s%s>%s</option>",
				$value,
				$selected ? " selected=\"selected\"" : "",
				$disabled ? " disabled=\"disabled\"" : "",
				$name);
		}

		public function selElmtFromDB($table,$valuefield,$options = array()) {
			$output = "";

			$sqlopts = array();
			$sqlcond = "";
			$selected = array();
			$lf = $valuefield;

			if (isset($options["sqlopts"])) {
				$sqlopts = $options["sqlopts"];
			}

			if (isset($options["selected"])) {
				$selected = $options["selected"];
			}

			if (isset($options["sqlcond"])) {
				$sqlcond = $options["sqlcond"];
			}

			if (isset($options["labelfield"])) {
				$lf = $options["labelfield"];
				$query = FS::$dbMgr->Select($table,$options["labelfield"].",".$valuefield,$sqlcond,$sqlopts);
			}
			else {
				$query = FS::$dbMgr->Select($table,$valuefield,$sqlcond,$sqlopts);
			}

			while($data = FS::$dbMgr->Fetch($query)) {
				$output = sprintf("%s%s",$output,
					FS::$iMgr->selElmt($data[$lf],$data[$valuefield],in_array($data[$valuefield],$selected)));
			}
			return $output;
		}

		public function check($name,$options = array()) {
			$output = "";
			if (isset($options["label"])) {
				$output = sprintf("<label for=\"%s\">%s</label> ",
					$name,$options["label"]);
			}

			return sprintf("%s<input type=\"checkbox\" name=\"%s\" id=\"%s\" %s%s />",
				$output,$name,$name,
				(isset($options["check"]) && $options["check"]) ? "checked " : "",
				(isset($options["tooltip"])) ? $this->tooltip($options["tooltip"]) : ""
			);

		}

		public function img($path,$sizeX = 0,$sizeY = 0, $id = "",$options = array()) {
			/*
			 * If image is not prefixed by a /, force it.
			 * This doesn't permit external images
			 */
			if (!preg_match("#^\/(.*)#",$path)) {
				$path = sprintf("/%s",$path);
			}

			return sprintf("<img src=\"%s\" %s%s%s%s style=\"border: none;\"/>",
				$path,
				isset($options["tooltip"]) ? $this->tooltip($options["tooltip"]) : "",
				($sizeX != 0 ? "width=\"".$sizeX."\" " : ""),
				($sizeY != 0 ? "height=\"".$sizeY."\" " : ""),
				($id ? "id=\"".$id."\" " : "")
			);
		}

		public function imgWithZoom2($path,$title,$id,$bigpath="") {
			$output = sprintf("<a href=\"%s\" id=\"%s\" title=\"%s\">%s</a>",
				(strlen($bigpath) > 0 ? $bigpath : $path),
				$id, $title,
				FS::$iMgr->img($path,0,0,"jqzoom-img"));

			$this->js(sprintf("$('#%s').jqzoom({ zoomWidth: 400, zoomHeight: 320, alwaysOn: true, zoomType: 'drag'});",
				$id));
			return $output;
		}

		public function upload($name) {
			return sprintf("<input type=\"file\" name=\"%s\" />",$name);
		}

		public function canvas($name, $width=480, $height=480) {
			return sprintf("<canvas id=\"%s\" height=\"%s\" width=\"%s\">[Your browser doesn't support HTML5]</canvas>",
				$name,$height,$width);
		}

		public function tabPanElmt($shid,$link,$label,$cursh) {
			return sprintf("<li%s><a href=\"/?%s&at=2&sh=%s%s\">%s</a>",
				($shid == $cursh ? " class=\"ui-tabs-active ui-state-active\"" : ""),
				$link,$shid,
				(FS::$secMgr->checkAndSecuriseGetData("nohist") ? "&nohist=1" : ""),
				$label);

		}

		public function tabPan($elmts = array(),$cursh) {
			$output = "";

			$count = count($elmts);
			for ($i=0;$i<$count;$i++) {
				$output = sprintf("%s%s",$output,$this->tabPanElmt($elmts[$i][0],$elmts[$i][1],$elmts[$i][2],$cursh));
			}

			$output = sprintf("<div id=\"contenttabs\"><ul>%s</ul></div>",$output);
			FS::$iMgr->js("$('#contenttabs').tabs({cache: false,
				ajaxOptions: {
					error: function(xhr,status,index,anchor) {
						$(anchor.hash).html(\"".$this->getLocale("fail-tab")."\");
					},
				},
				beforeLoad: function(event,ui) {
					ui.panel.html('<span class=\"loader\"></span>');
					var url = ui.ajaxSettings.url;
					$.post(url, function(data) {
						var cb = JSON.parse(data);
						ui.panel.html(cb['htmldatas']);
						eval(cb['jscode']);
					});
					return false;
				}
			});");
			return ($count > 0 ? $output : "");
		}

		/*
		* lnkadd option contain get arguments in HTML form for AJAX calls
		*/
		public function opendiv($callid,$text1,$options=array()) {
			return sprintf("<a href=\"#\" onclick=\"formPopup('%s','%s','%s');\">%s</a>%s",
				(isset($options["moduleid"]) ? $options["moduleid"] : $this->cur_module->getModuleId()),
				$callid,
				(isset($options["lnkadd"]) ? $options["lnkadd"] : ""),
				$text1,
				(isset($options["line"]) && $options["line"]) ? "<br />" : "");
		}

		public function iconOpendiv($callid,$iconname,$options=array()) {
			$size = (isset($options["iconsize"]) && is_numeric($options["iconsize"])) ? $options["iconsize"] : 15;
			return $this->opendiv($callid,$this->img("styles/images/".$iconname.".png",$size,$size),$options);
		}

		/*
		* jQuery accordion generator
		*/
		public function accordion($accId,$elements=array()) {
			$output = "";

			foreach ($elements as $key => $values) {
				$output = sprintf("%s%s<div id=\"acc%sdiv\">%s</div>",
					$output,$this->h3($values[0],true,"acc".$key."h3"),$key,$values[1]);
			}

			$output = sprintf("<div id=\"%s\">%s</div>",$accId,$output);

			$this->js(sprintf("$('#%s').accordion({heightStyle: 'content'});",$accId));
			return $output;
		}

		public function setURL($url) {
			// Nohist is needed to remove history loops
			if (!FS::$secMgr->checkAndSecuriseGetData("nohist")) {
				$this->js(sprintf("addHistoryState(document.title, '/?mod=%s%s','&nohist=1&mod=%s%s');",
					$this->cur_module->getModuleId(),
					strlen($url) > 0 ? "&".$url : "",
					$this->cur_module->getModuleId(),
					strlen($url) > 0 ? "&".$url : ""));
			}
		}

		public function stylesheet($path) {
			$this->arr_css[count($this->arr_css)] = $path;
		}

		public function jsinc($path) {
			$this->arr_js[count($this->arr_js)] = $path;
		}

		public function redir($link,$js=false) {
			if ($js && FS::isAjaxCall()) {
				//$this->js("window.location.href=\"?".$link."\";");
				$this->js("loadInterface('&".$link."',false);");
			}
			else {
				header("Location: /?".$link);
			}
		}

		public function printError($msg,$raw=false) {
			if ($raw) {
				return sprintf("<div id=\"errorContent\">%s: %s</div>",$this->getLocale("Error"),$msg);
			}
			else {
				return sprintf("<div id=\"errorContent\">%s: %s</div>",$this->getLocale("Error"),$this->getLocale($msg));
			}
		}

		public function printNoRight($rightStr) {
			$this->cur_module->log(2,
				sprintf("User doesn't have rights to %s",$rightStr)
			);
			return $this->printError("err-no-rights");
		}

		public function printDebug($msg) {
			return sprintf("<div id=\"debugContent\">%s: %s</div>",$this->getLocale("Notification"),$msg);
		}

		public function printDebugBacktrace() {
			$output = "<span style=\"text-align:left;display:block;\">";

			$bt = debug_backtrace();
			$count = count($bt);
			// Don't show this call
			for ($i=1;$i<$count;$i++) {
				$op = "";
				if (isset($bt[$i]["type"])) {
					$op = $bt[$i]["type"];
				}

				$class = "";
				if (isset($bt[$i]["class"])) {
					$class = $bt[$i]["class"];
				}
				$output = sprintf("%s#%d %s%s%s() called at %s:%s<br />",$output,($i-1),$class,
					$op,$bt[$i]["function"],$bt[$i]["file"],$bt[$i]["line"]);
			}

			$output = sprintf("%s</span>",$output);
			return $output;
		}

		public function getModuleIdByPath($path) {
			if (isset($this->moduleIdBuf[$path])) {
				return $this->moduleIdBuf[$path];
			}

			$dir = opendir(dirname(__FILE__)."/../../modules");
			$moduleid = 0;
			$found = false;
			while(($elem = readdir($dir)) && $found == false) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) $moduleid++;
				if (is_dir($dirpath) && $elem == $path) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2)) && $found == false) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "module.php") {
							$this->moduleIdBuf[$path] = $moduleid;
							return $moduleid;
						}
					}
				}
			}
			return 0;
		}


		protected function ajaxEcho($str,$js="",$raw=false,$options=array()) {
			$this->echo_buffer = sprintf("%s%s",
				$this->echo_buffer,
				($raw ? $str : $this->getLocale($str))
			);

			if (isset($options["no-close"]) &&
				$options["no-close"] === true) {
				$js = sprintf("dontClosePopup();%s",$js);
			}
			if (strlen($js) > 0) {
				$this->js($js);
			}
		}

		public function ajaxEchoError($str, $js = "", $raw = false, $options = array()) {
			$js = sprintf("%s%s", $js,
				"setNotifIconToErr();"
			);
			$this->ajaxEcho(
				sprintf("<span class=\"notifErrSpan\">%s:</span> %s",
					$this->getLocale("Error"),
					$raw ? $str : $this->getLocale($str)),
				$js, true, $options
			);
		}

		public function ajaxEchoErrorNC($str, $js = "", $raw = false, $options = array()) {
			$this->ajaxEchoError($str,$js,$raw,array("no-close" => true));
		}

		public function echoNoRights($rightStr, $js = "", $options = array()) {
			$this->cur_module->log(2,
				sprintf("User doesn't have rights to %s",$rightStr)
			);
			$this->ajaxEchoError("err-no-rights",$js,false,$options);
		}

		public function ajaxEchoOK($str, $js = "", $raw = false, $options = array()) {
			$js = sprintf("%s%s", $js,
				"setNotifIconToOK();"
			);

			$this->ajaxEcho($str, $js, $raw, $options);
		}

		public function getAjaxEchoBuffer() {
			return $this->echo_buffer;
		}

		public function getLocale($locid) {
			return $this->getLocales()->s($locid);
		}

		public function getLocales() {
			if ($this->cur_module) {
				return $this->cur_module->getLoc();
			}
			else {
				return new FSLocales();
			}
		}

		public function getCurModule() { return $this->cur_module; }

		public function setCurrentModule($module) { $this->cur_module = $module; }
		public function setTitle($title) {
			$this->title = $title;
			if (FS::isAjaxCall()) {
				$this->js(sprintf("document.title='%s%s';",
					addslashes(Config::getWebsiteName()),
					addslashes(strlen($this->title) > 0 ? " - ".$this->title : "")
				));
			}
		}

		public function fileGetContent($path) {
			$opts = array(
				'http'=> array(
					'method'=>"GET",
					'header'=> sprintf("Accept-language: %s\r\n",
						FS::$sessMgr->getBrowserLang())
				)
			);
			$context = stream_context_create($opts);
			return file_get_contents($path, false, $context);
	}
		protected $cur_module;
		private $arr_css;
		private $arr_js;
		private $title;
		private $echo_buffer;
		private $js_buffer;
		private $js_buffer_idx;

		private $moduleIdBuf;
	};
?>
