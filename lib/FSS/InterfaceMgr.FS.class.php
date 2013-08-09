<?php
        /*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
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
	require_once(dirname(__FILE__)."/objects/InterfaceModule".CLASS_EXT);

	abstract class FSInterfaceMgr {
		function __construct() {
			$this->InitComponents();
		}

		public function InitComponents() {
			$this->arr_css = array();
			$this->arr_js = array();
			$this->title = "";

			// Create 2 JS buffers
			$this->js_buffer = array();
			for ($i=0;$i<2;$i++) {
				$this->js_buffer[] = "";
			}
			$this->js_buffer_idx = 0;
		}

		// header/footer/content

		public function header() {
			$output = "<!DOCTYPE html>
				<html lang=\"".Config::getSysLang()."\">
				<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
				<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />
				<head>
				<title>".Config::getWebsiteName().(strlen($this->title) > 0 ? " - ".$this->title : "")."</title>";

				$count = count($this->arr_css);
				for ($i=0;$i<$count;$i++) {
					$output .= "<link rel=\"stylesheet\" href=\"".$this->arr_css[$i]."\" type=\"text/css\" />";
				}

				if (Config::hasFavicon()) {
					$output .= "<link rel=\"shortcut icon\" href=\"/styles/images/favicon.png\" />";
				}

				$count = count($this->arr_js);
				for ($i=0;$i<$count;$i++) {
					$output .= "<script type=\"text/javascript\" src=\"".$this->arr_js[$i]."\"></script>";
				}

				$output .= "</head><body>";
			return $output;
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
						if (is_file($dirpath."/".$elem2) && $elem2 == "main.php") {
							$path = $elem;
							require(dirname(__FILE__)."/../../modules/".$path."/main.php");
							$menuname = $module->getMenu();
							if ($menuname && $module->getRulesClass()->canAccessToModule()) {
								if (!isset($menus[$menuname])) {
									$menus[$menuname] = array();
								}
								if ($module->getRulesClass()->canAccessToModule()) {
									$menus[$menuname][] = 
										"<div class=\"menuItem\" onclick=\"loadInterface('&mod=".$moduleid."');\">".
										$module->getModuleClass()->getMenuTitle()."</div>";
								}
							}
						}
					}
				}
			}

			foreach ($menus as $menuname => $elemts) {
				$output .= "<div id=\"menuStack\"><div id=\"menuTitle\">".$menuname."</div><div class=\"menupopup\">";

				for ($i=0;$i<count($elemts);$i++) {
					$output .= $elemts[$i];
				}

				$output .= "</div></div>";
			}
			return $output;
		}

		public function loadModule($id,$act=1) {
			$output = "";
			$dir = opendir(dirname(__FILE__)."/../../modules/");
			$found = false;
			$moduleid = 0;
			while(($elem = readdir($dir)) && $found == false) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) $moduleid++;
				if (is_dir($dirpath) && $moduleid == $id) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2)) && $found == false) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "main.php")
							$found = true;
							$path = $elem;
					}
				}
			}
			if ($found == true) {
				require(dirname(__FILE__)."/../../modules/".$path."/main.php");
				if ($module->getRulesClass()->canAccessToModule()) {
					$this->setCurrentModule($module->getModuleClass());
					$module->getModuleClass()->setModuleId($id);
					switch($act) {
						case 1: default: $output .= $module->getModuleClass()->Load(); break;
						case 2: $output .= $module->getModuleClass()->getIfaceElmt(); break;
					}
						
				}
				else
					$output .= $this->printError($this->getLocale("err-no-rights"));
			}
			else
				$output .= $this->printError($this->getLocale("err-unk-module"));

			return $output;
		}

		public function getRealNameById($id) {
                        $user = new User();
                        $user->LoadFromDB($id);
                        return $user->getSubName()." ".$user->getName();
                }

		public function backLink() {
			return "<div id=\"backarrow\"><a href=\"javascript:history.back()\">".FS::$iMgr->img("styles/back.png",32,32)."</a></div>";
		}

		public function h1($str,$raw=false,$id="") {
			return "<h1".($id ? " id=\"".$id."\"" : "").">".($raw ? $str : $this->getLocale($str))."</h1>";
		}

		public function h2($str,$raw=false,$id="") {
			return "<h2".($id ? " id=\"".$id."\"" : "").">".($raw ? $str : $this->getLocale($str))."</h2>";
		}

		public function h3($str,$raw=false,$id="") {
			return "<h3".($id ? " id=\"".$id."\"" : "").">".($raw ? $str : $this->getLocale($str))."</h3>";
		}

		public function h4($str,$raw=false,$id="") {
			return "<h4".($id ? " id=\"".$id."\"" : "").">".($raw ? $str : $this->getLocale($str))."</h4>";
		}

		public function hr() { return "<div id=\"hr\"></div>"; }

		public function js($js) {
			if (!isset($this->js_buffer[$this->js_buffer_idx])) $this->js_buffer[$this->js_buffer_idx] = "";
			$this->js_buffer[$this->js_buffer_idx] .= $js;
		}

		public function setJSBuffer($idx) {
			$this->js_buffer_idx = $idx;
		}

		public function renderJS() {
			if (strlen($this->js_buffer[$this->js_buffer_idx]) > 0)
                        	return "<script type=\"text/javascript\">".$this->js_buffer[$this->js_buffer_idx]."</script>";
			return "";
		}

		public function label($for,$value,$class = "") {
			return "<label class=\"".$class."\" for=\"".$for."\">".$value."</label>";
		}

		public function tooltip($text) {
			$output = "onmouseover=\"showTooltip('".addslashes($this->getLocale($text))."');\" ";
			$output .= "onmouseout=\"hideTooltip();\" ";
			return $output;
		}

		public function tip($text,$raw=false) {
			return "<div id=\"tip\">".($raw ? $text : $this->getLocale($text))."</div>";
		}

		public function textarea($name, $def_value = "", $options=array()) {
			$output = "";
			if (isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<textarea name=\"".$name."\" id=\"".$name."\" style=\"width:".(isset($options["width"]) ? $options["width"] : 400).
				"px;height:".(isset($options["height"]) ? $options["height"] : 300)."px\" ";
			if (isset($options["tooltip"])) $output .= $this->tooltip($options["tooltip"]);
			if (isset($options["length"])) $output .= " maxlength=\"".$options["length"]."\"";
			$output .= ">".$def_value."</textarea>";
			return $output;
		}

		public function tabledTextArea($label,$name,$options = array()) {
			$output = "<tr><td>".$label."</td><td class=\"ctrel\">";
			$output .= $this->textarea($name,(isset($options["value"]) ? $options["value"] : ""),$options);
			$output .= "</td></tr>";
			return $output;
		}

		public function input($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
			if ($label) $output .= "<label for=\"".$name."\">".$label." </label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" ";
			if ($tooltip)
				$output .= $this->tooltip($tooltip);
			$output .= "/>";
			return $output;
		}

		public function numInput($name, $def_value = "", $options = array()) {
			$output = "";
		        if (isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".(isset($options["size"]) ? $options["size"] : 20)."\" maxlength=\"".(isset($options["length"]) ? $options["length"] : 40)."\" onkeyup=\"javascript:ReplaceNotNumeric(this);\" ";
			if (isset($options["tooltip"])) $output .= $this->tooltip($options["tooltip"]);
			$output .= " />";
			return $output;
		}

		public function IPInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
                        if ($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkIP(this);\" ";
			if ($tooltip) $output .= $this->tooltip($tooltip);
			$output .= " />";
			return $output;
		}

		public function MacInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL) {
			$output = "";
                        if ($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkMAC(this);\" />";
			return $output;
		}

		public function IPMaskInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkMask(this);\" />";
		}

		public function colorInput($name, $def_value) {
			$output = "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"6\" maxlength=\"6\" onfocus=\"showColorPicker(this,'".$def_value."');\"/>";
			return $output;
		}

		public function autoComplete($name, $options = array()) {
			$output = $this->input($name,"",(isset($options["size"]) ? $options["size"] : 20),
				(isset($options["length"]) ? $options["length"] : 40), (isset($options["label"]) ? $options["label"] : NULL),
				(isset($options["tooltip"]) ? $options["tooltip"] : NULL));
			$this->js("$('#".$name."').autocomplete({source: 'index.php?mod=".$this->getModuleIdByPath("search")."&at=2',
				minLength: 3});");
			return $output;
		}

		public function slider($slidername, $name, $min, $max, $options = array()) {
			if (isset($options["hidden"]))
				$output = FS::$iMgr->hidden($name,(isset($options["value"]) ? $options["value"] : 0));
			else
				$output = FS::$iMgr->input($name,(isset($options["value"]) ? $options["value"] : 0));
			$js = "$(function() {
                        	$('#".$slidername."').slider({
					range: 'min',
					min: ".$min.",
					max:".$max.",";

			if (isset($options["value"])) $js .= "value: ".$options["value"].",";

			$js .= "slide: function(event,ui) { $('#".$name."').val(".(isset($options["valoverride"]) ? $options["valoverride"] : "ui.value").");";
			if (isset($options["hidden"])) $js.= "$('#".$name."label').html(ui.value);";
			$js.= "}
				});
                        });";
			$output .= $this->js($js).
				"<div id=\"".$slidername."\" ".(isset($options["width"]) ? "style=\"width: ".$options["width"]."\" " : "")."></div>";
			if (isset($options["hidden"])) $output .= "<br /><span id=\"".$name."label\">".
				(isset($options["value"]) ? $options["value"] : 0)."</span> ".$options["hidden"]."<br />";
			return $output;
		}

		public function hidden($name, $value) {
			return "<input type=\"hidden\" id=\"".$name."\" name=\"".$name."\" value=\"".$value."\" />";
		}

		public function password($name, $def_value = "", $label=NULL) {
			$output = "";
                        if ($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"password\" name=\"".$name."\" value=\"".$def_value."\" />";
			return $output;
		}

		public function calendar($name, $def_value, $label=NULL) {
			$output = "";
                        if ($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
                        $output .= "<input type=\"textbox\" value=\"".$def_value."\" name=\"".$name."\" id=\"".$name."\" size=\"20\" />";
			$js = "$('#".$name."').datepicker($.datepicker.regional['fr']);
				$('#".$name."').datepicker('option', 'dateFormat', 'dd-mm-yy');";
			if ($def_value)
				$js .= "$('#".$name."').datepicker('setDate','".$def_value."');";
			$output .= $this->js($js);
			return $output;
		}

		public function hourlist($hname,$mname,$hselect=0,$mselect=0) {
			$output = $this->select($hname);
			for ($i=0;$i<24;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
				$output .= $this->selElmt($txt,$i,$hselect == $i);
			}
			$output .= "</select> h ".$this->select($mname);
			for ($i=0;$i<60;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
                                $output .= $this->selElmt($txt,$i,$mselect == $i);
                        }
			$output .= "</select>";

			return $output;
		}

		public function submit($name, $value, $options = array()) {
			$output = "<input class=\"buttonStyle\" type=\"submit\" name=\"".$name."\" id=\"".$name."\" value=\"".$value."\" ";
			if (isset($options["tooltip"])) $output .= $this->tooltip($options["tooltip"]);
			$output .= " />";
			return $output;
		}

		public function JSSubmit($name, $value, $function) {
			return "<input class=\"buttonStyle\" type=\"submit\" name=\"".$name."\" id=\"".$name."\" value=\"".$value."\" onclick=\"".$function."\" />";
		}

		public function button($name, $value, $js) {
			return "<input class=\"buttonStyle\" type=\"button\" name=\"".$name."\" value=\"".$value."\" onclick=\"".$js."\" />";
		}

		public function radio($name, $value, $checked = false, $label=NULL) {
			$output = "";
			$output .= "<input id=\"".$name."\" type=\"radio\" value=\"".$value."\" name=\"".$name."\"";
			if ($checked) $output .= " checked=\"checked\"";
			$output .= "> ".($label ? $label : "");
			return $output;
		}
		
		public function radioList($name,$values, $labels, $checkid = NULL) {
			if (!is_array($values)) return "";
			$output = "";

			$count = count($values);
			for ($i=0;$i<$count;$i++) {
				$output .= $this->radio($name,$values[$i],$checkid == $values[$i] ? true : false, $labels[$i])."<br />";
			}

			return $output;
		}

		public function form($link,$options=array()) {
			$output = "<form action=\"".$link."\" ";
			if (isset($options["id"]) && strlen($options["id"]) > 0)
				$output .= "id=\"".$options["id"]."\" ";
			$output .= "method=\"".((isset($options["get"]) && $options["get"]) ? "GET" : "POST")."\"";
			if (isset($options["js"]))
				$output .= " onsubmit=\"return ".$options["js"].";\" ";
			$output .= ">";
			return $output;
		}

		public function cbkForm($link, $textid = "Modification",$raw = false) {
			if($raw == false) {
				$link = "index.php?mod=".$this->cur_module->getModuleId()."&act=".$link;
			}
			$output = "<form action=\"".$link.
				"\" method=\"POST\" onsubmit=\"return callbackForm('".$link."',this,".
				"{'snotif':'".addslashes($this->getLocale($textid))."'});\" >";
			return $output;
		}

		public function idxLine($label,$name,$def_value = "", $options = array()) {
			$output = "<tr ";
			if (isset($options["tooltip"])) {
				$output .= $this->tooltip($options["tooltip"]);
				unset($options["tooltip"]);
			}
			$output .= "><td>".$label."</td><td class=\"ctrel\">";
			if (isset($options["type"])) {
				switch($options["type"]) {
					case "idxedit": 
						if (isset($options["edit"]) && $options["edit"])
							$output .= $def_value.FS::$iMgr->hidden($name,$def_value).FS::$iMgr->hidden("edit",1);
						else
							$output .= $this->input($name,$def_value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40),
                                        			(isset($options["label"]) ? $options["label"] : NULL));
						break;
					case "idxipedit":
						if (isset($options["edit"]) && $options["edit"])
							$output .= $def_value.FS::$iMgr->hidden($name,$def_value).FS::$iMgr->hidden("edit",1);
						else
							$output .= $this->IPInput($name,$def_value);
						break;
					case "chk": $options["check"] = $def_value; $output .= $this->check($name, $options); break;
					case "ip": $output .= $this->IPInput($name,$def_value); break;
					case "ipmask": $output .= $this->IPMaskInput($name,$def_value); break;
					case "num": $output .= $this->numInput($name,(isset($options["value"]) ? $options["value"] : ""),$options); break;
					case "pwd": $output .= $this->password($name,$def_value); break;
					case "color": $output .= $this->colorInput($name,$def_value); break;
					case "area": $output .= $this->textarea($name, (isset($options["value"]) ? $options["value"] : ""), $options); break;
					default: break;
				}
			}
			else
				$output .= $this->input($name,$def_value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40),
					(isset($options["label"]) ? $options["label"] : NULL));
			$output .= "</td></tr>";
			return $output;
		}

		public function idxIdLine($label,$name,$value = "",$options = array()) {
			if ($value)
				return "<tr><td>".$this->getLocale($label)."</td><td>".$value."</td></tr>".FS::$iMgr->hidden($name,$value).FS::$iMgr->hidden("edit",1);
			else
				return $this->idxLine($this->getLocale($label),$name,"",$options);
		}

		public function ruleLine($label,$rulename,$rulelist,$idx = "") {
			return "<tr><td>".$idx."</td><td>".$this->check($rulename,array("check" => in_array($rulename,$rulelist),"label" => $label))."</td></tr>";
		}

		public function ruleLines($idx,$rulelist,$rules = array()) {
			$output = "";

			$count = count($rules);
			for ($i=0;$i<$count;$i++) {
				$output .= $this->ruleLine($rules[$i][0],$rules[$i][1],$rulelist,$i == 0 ? $idx : "");
			}

			return $output;
		}

		public function tableSubmit($label,$options = array()) {
			$output = "<tr><th colspan=\"".(isset($options["size"]) ? $options["size"] : 2)."\" class=\"ctrel\">";
			if (isset($options["js"])) {
				$output .= $this->JSSubmit((isset($options["name"]) ? $options["name"] : ""),$this->getLocale($label),$options["js"]);
			}
			else {
				$output .= $this->submit((isset($options["name"]) ? $options["name"] : ""),$this->getLocale($label));
			}
			$output .= "</th></tr></table></form>";
			return $output;
		}

		// Helper for tabled Add/Submit forms
		public function aeTableSubmit($add = true, $options = array()) {
			if ($add)
				return $this->tableSubmit("Add",$options);
			else
				return $this->tableSubmit("Save",$options);
		}

		public function jsSortTable($id) {
			$this->js("$('#".$id."').tablesorter();");
		}

		public function formatHTMLId($str) {
			return preg_replace("#[ .]#","-",$str);
		}

		public function progress($name,$value,$max=100,$label=NULL) {
			$output = "";
			if ($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<progress id=\"".$name."\" value=\"".$value."\" max=\"".$max."\"></progress><span id=\"".$name."val\"></span>";
			$output .= $this->js("eltBar = document.getElementById(\"".$name."\");
				eltPct = document.getElementById(\"".$name."val\");
				eltPct.innerHTML = ' ' + Math.floor(eltBar.position * 100) + \"%\";");
			return $output;
		}

		public function select($name, $options = array()) {
			$output = "";

			if (isset($options["label"])) {
				$output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			}

			$selId = preg_replace("#\[|\]#","",$name);

			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$output .= "<select name=\"".$name.($multi ? "[]" : "")."\" id=\"".$selId."\"";

			if (isset($options["js"]) > 0) {
				$output .= " onchange=\"javascript:".$options["js"].";\" ";
			}

			if ($multi) {
				$output .= " multiple=\"multiple\" ";
			}

			if (isset($options["size"]) && FS::$secMgr->isNumeric($options["size"])) {
				$output .= " size=\"".$options["size"]."\" ";
			}

			if (isset($options["style"])) {
				$output .= " style=\"".$style."\" ";
			}

			if (isset($options["tooltip"])) {
				$output .= $this->tooltip($options["tooltip"]);
			}
			$output .= ">";
			return $output;
		}

		public function selElmt($name,$value,$selected = false) {
			$output = "<option value=\"".$value."\"";
			if ($selected)
				$output .= " selected=\"selected\"";
			$output .= ">".$name."</option>";
			return $output;
		}

		public function selElmtFromDB($table,$labelfield,$valuefield,$selected = array(),$sqlopts = array()) {
			$output = "";
			$query = FS::$dbMgr->Select($table,$labelfield.",".$valuefield,"",$sqlopts);
                        while($data = FS::$dbMgr->Fetch($query)) {
                                $output .= FS::$iMgr->selElmt($data[$labelfield],$data[$valuefield],in_array($data[$valuefield],$selected));
                        }
			return $output;
		}

		public function check($name,$options = array()) {
			$output = "";
			if (isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<input type=\"checkbox\" name=\"".$name."\" id=\"".$name."\" ";
			if (isset($options["check"]) && $options["check"])
				$output .= "checked ";
			if (isset($options["tooltip"])) $output .= $this->tooltip($options["tooltip"]);
			$output .= " />";
			return $output;
		}

		public function img($path,$sizeX = 0,$sizeY = 0, $id = "") {
			$output = "<img src=\"".$path."\" ";
			if ($sizeX != 0)
				$output .= "width=\"".$sizeX."\" ";
			if ($sizeY != 0)
				$output .= "height=\"".$sizeY."\" ";
			if (strlen($id) > 0)
				$output .= "id=\"".$id."\" ";
			$output .= "style=\"border: none;\"/>";	
			return $output;
		}
		
		public function imgWithZoom2($path,$title,$id,$bigpath="") {
			$output = "<a href=\"".(strlen($bigpath) > 0 ? $bigpath : $path)."\" id=\"".$id."\" title=\"".$title."\">"; 
			$output .= FS::$iMgr->img($path,0,0,"jqzoom-img");
			$output .= "</a>".$this->js("$('#".$id."').jqzoom({ zoomWidth: 400, zoomHeight: 320, alwaysOn: true, zoomType: 'drag'});");
			return $output;
		}	

		public function upload($name) {
			return "<input type=\"file\" name=\"".$name."\" />";
		}
		
		public function canvas($name, $width=480, $height=480) {
			return "<canvas id=\"".$name."\" height=\"".$height."\" width=\"".$width."\">[Votre Navigateur ne supporte pas le HTML5]</canvas>";
		}

		public function tabPanElmt($shid,$link,$label,$cursh) {
			$output = "<li".($shid == $cursh ? " class=\"ui-tabs-active ui-state-active\"" : "")."><a href=\"index.php?".$link."&at=2&sh=".$shid."\">".$label."</a>";
			return $output;
		}

		public function tabPan($elmts = array(),$cursh) {
			$output = "<div id=\"contenttabs\"><ul>";

			$count = count($elmts);
			for ($i=0;$i<$count;$i++) {
				$output .= $this->tabPanElmt($elmts[$i][0],$elmts[$i][1],$elmts[$i][2],$cursh);	
			}

			$output .= "</ul></div>";
			FS::$iMgr->js("$('#contenttabs').tabs({cache: false,
				ajaxOptions: { error: function(xhr,status,index,anchor) {
                        		$(anchor.hash).html(\"".$this->getLocale("fail-tab")."\");
				}},
				beforeLoad: function(event,ui) { $(ui.panel).html('<span class=\"loader\"></span>');}
			});");
			return ($count > 0 ? $output : "");
		}

		/*
		* lnkadd option contain get arguments in HTML form for AJAX calls
		*/
		public function opendiv($callid,$text1,$options=array()) {
			$output = "<a href=\"#\" onclick=\"formPopup('".$this->cur_module->getModuleId()."','".$callid."','".
				(isset($options["lnkadd"]) ? $options["lnkadd"] : "")."'";
			$output .= ");\">".$text1."</a>";
			if (isset($options["line"]) && $options["line"])
				$output .= "<br />";

			return $output;
		}

		/*
		* jQuery accordion generator
		*/
		public function accordion($accId,$elements=array()) {
			$output = "<div id=\"".$accId."\">";

			$count = count($elements);
			foreach ($elements as $key => $values) {
				$output .= $this->h3($values[0],true,"acc".$key."h3")."<div id=\"acc".$key."div\">".$values[1]."</div>";
			}

			$output .= "</div>";

			$this->js("$('#".$accId."').accordion({heightStyle: 'content'});");
			return $output;
		}

		// Simple methods
		public function stylesheet($path) {
			$this->arr_css[count($this->arr_css)] = $path;
		}

		public function jsinc($path) {
			$this->arr_js[count($this->arr_js)] = $path;
		}

		public function redir($link,$js=false) {
			if ($js && FS::isAjaxCall())
				$this->js("window.location.href=\"index.php?".$link."\";");
			else
				header("Location: index.php?".$link);
		}

		public function printError($msg) {
			return "<div id=\"errorContent\">Erreur: ".$msg."</div>";
		}

		public function printDebug($msg) {
			return "<div id=\"debugContent\">Notification: ".$msg."</div>";
		}

		public function getModuleIdByPath($path) {
			$dir = opendir(dirname(__FILE__)."/../../modules");
			$moduleid = 0;
			$found = false;
			while(($elem = readdir($dir)) && $found == false) {
				$dirpath = dirname(__FILE__)."/../../modules/".$elem;
				if (is_dir($dirpath)) $moduleid++;
				if (is_dir($dirpath) && $elem == $path) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2)) && $found == false) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "main.php")
							return $moduleid;
					}
				}
			}
			return 0;
		}


		public function ajaxEcho($str,$js="",$raw=false) {
			echo ($raw ? $str : $this->getLocale($str)).(strlen($js) > 0 ? $this->js($js) : "");
		}

		public function ajaxEchoNC($str,$js="",$raw=false) {
			echo ($raw ? $str : $this->getLocale($str)).(strlen($js) > 0 ? $this->js("dontClosePopup(); ".$js) : 
				$this->js("dontClosePopup();"));
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
		public function setTitle($title) { $this->title = $title; }

		protected $cur_module;
		private $arr_css;
		private $arr_js;
		private $title;
		private $js_buffer;
		private $js_buffer_idx;
	};
?>
