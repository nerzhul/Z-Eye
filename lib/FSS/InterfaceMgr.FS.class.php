<?php
        /*
        * Copyright (c) 2012, Loïc BLOT, CNRS
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
	require_once(dirname(__FILE__)."/MySQLMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/HTTPLink".CLASS_EXT);

	class FSInterfaceMgr {
		function FSInterfaceMgr($DBMgr) {
			$this->dbMgr = $DBMgr;	
		}

		public function InitComponents() {
			$this->arr_css = array();
			$this->arr_js = array();
		}

		// header/footer/content
		
		public function header() {
			$output = "<!DOCTYPE html>
				<html lang=\"".Config::getSysLang()."\">
				<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
				<head>
				<title>".Config::getWebsiteName()."</title>";
				for($i=0;$i<count($this->arr_css);$i++)
					$output .= "<link rel=\"stylesheet\" href=\"".$this->arr_css[$i]."\" type=\"text/css\" />";
				if(Config::hasFavicon())
					$output .= "<link rel=\"shortcut icon\" href=\"/favicon.ico\" />";
				
				for($i=0;$i<count($this->arr_js);$i++)
					$output .= "<script type=\"text/javascript\" src=\"".$this->arr_js[$i]."\"></script>";
				
				$output .= "</head>
				<body class=\"body\">";
			return $output;
		}

		public function footer() {
			return "</body></html>";
		}

		public function content() {
			return "<div id=\"main\"></div>";
		}

		protected function loadMenus($mlist) {
                        $output = "";
			for($i=0;$i<count($mlist);$i++) {
				$haselemtoshow = 0;
				$tmpoutput = "";
				// Load menu
				$query = FS::$pgdbMgr->Select("z_eye_menus","name,isconnected","id = '".$mlist[$i]."'");
                                if($data = pg_fetch_array($query)) {
					$tmpoutput .= "<div id=\"menuStack\"><div id=\"menuTitle\">".$data["name"]."</div><div class=\"menupopup\">";
					// load menu elements
					$query2 = FS::$pgdbMgr->Select("z_eye_menu_link","id_menu_item","id_menu = '".$mlist[$i]."'","\"order\"");
                                        while($data2 = pg_fetch_array($query2)) {
						$query3 = FS::$pgdbMgr->Select("z_eye_menu_items","title,link,isconnected","id = '".$data2["id_menu_item"]."'");
                                                while($data3 = pg_fetch_array($query3)) {
							$link = new HTTPLink($data3["link"]);
                                                        $link->Load();
                                                        $dirpath = dirname(__FILE__)."/../../modules/user_modules/".$link->getArgs();
							if(FS::$sessMgr->getUid() == 1 || (is_dir($dirpath))) {
								require($dirpath."/main.php");
								if($module->getRulesClass()->canAccessToModule()) {
	                                                                $tmpoutput .= "<div class=\"menuItem\"><a href=\"".$link->getIt()."\">".$data3["title"]."</a></div>";
									$haselemtoshow = 1;
								}
							}
						}
					}
					$tmpoutput .= "</div></div>";
				}
				if($haselemtoshow) $output .= $tmpoutput;

			}
                        return $output;
                }

		public function getRealNameById($id) {
                        $user = new User();
                        $user->LoadFromDB($id);
                        return $user->getSubName()." ".$user->getName();
                }

		private function getJSONLink($jsonstr) {
			$jsonstr = preg_replace("#\"#","&quot;",$jsonstr);
			return "javascript:getPage('".$jsonstr."');";
		}

		public function addJSONLink($jsonstr,$text) {
			return "<a class=\"monoComponentt_a\" href=\"".FS::$iMgr->getJSONLink($jsonstr)."\">".$text."</a>";
		}

		public function backLink() {
			return "<div id=\"backarrow\"><a href=\"javascript:history.back()\">".FS::$iMgr->img("styles/back.png",32,32)."</a></div>";
		}

		public function label($for,$value,$class = "") {
			return "<label class=\"".$class."\" for=\"".$for."\">".$value."</label>";
		}

		public function textarea($name, $def_value = "", $options=array()) {
			$output = "";
			if(isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<textarea name=\"".$name."\" id=\"".$name."\" style=\"width:".(isset($options["width"]) ? $options["width"] : 400).
				"px;height:".(isset($options["height"]) ? $options["height"] : 300)."px\">".$def_value."</textarea>";
			if(isset($options["tooltip"])) {
				$output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".
					$options["tooltip"]."\"});</script>";
			}
			return $output;
		}

		public function tabledTextArea($label,$name,$options = array()) {
			$output = "<tr><td>".$label."</td><td><center>";
			$output .= $this->textarea($name,(isset($options["value"]) ? $options["value"] : ""),$options);
			$output .= "</center></td></tr>";
			return $output;
		}

		public function input($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
			if($label) $output .= "<label for=\"".$name."\">".$label." </label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" />";
			if($tooltip) $output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".$tooltip."\"});</script>";
			return $output;
		}

		public function numInput($name, $def_value = "", $options = array()) {
			$output = "";
		        if(isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".(isset($options["size"]) ? $options["size"] : 20)."\" maxlength=\"".(isset($options["length"]) ? $options["length"] : 40)."\" onkeyup=\"javascript:ReplaceNotNumeric('".$name."');\" />";
			if(isset($options["tooltip"])) $output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".$options["tooltip"]."\"});</script>";
			return $output;
		}

		public function IPInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = "";
                        if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkIP('".$name."');\" />";
			if($tooltip) $output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".$tooltip."\"});</script>";
			return $output;
		}

		public function MacInput($name, $def_value = "", $size = 20, $length = 40, $label=NULL) {
			$output = "";
                        if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkMAC('".$name."');\" />";
			return $output;
		}

		public function IPMaskInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkMask('".$name."');\" />";
		}

		public function hidden($name, $value) {
			return "<input type=\"hidden\" id=\"".$name."\" name=\"".$name."\" value=\"".$value."\" />";
		}

		public function password($name, $def_value = "", $label=NULL) {
			$output = "";
                        if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<input type=\"password\" name=\"".$name."\" value=\"".$def_value."\" />";
			return $output;
		}

		public function calendar($name, $def_value, $label=NULL) {
			$output = "";
                        if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
                        $output .= "<input type=\"textbox\" value=\"".$def_value."\" name=\"".$name."\" id=\"".$name."\" size=\"20\" />";
			$output .= "<script type=\"text/javascript\">$('#".$name."').datepicker($.datepicker.regional['fr']);";
			$output .= "$('#".$name."').datepicker('option', 'dateFormat', 'dd-mm-yy');";
			if($def_value)
				$output .= "$('#".$name."').datepicker('setDate','".$def_value."');";
			$output .= "</script>";
			return $output;
		}

		public function hourlist($hname,$mname,$hselect=0,$mselect=0) {
			$output = $this->addList($hname);
			for($i=0;$i<24;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
				$output .= $this->addElementToList($txt,$i,$hselect == $i);
			}
			$output .= "</select> h ".$this->addList($mname);
			for($i=0;$i<60;$i++) {
				$txt = ($i < 10 ? "0".$i : $i);
                                $output .= $this->addElementToList($txt,$i,$mselect == $i);
                        }
			$output .= "</select>";

			return $output;
		}

		public function submit($name, $value, $options = array()) {
			$output = "<input class=\"buttonStyle\" type=\"submit\" name=\"".$name."\" id=\"".$name."\" value=\"".$value."\" />";
			if(isset($options["tooltip"])) {
				$output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".
					$options["tooltip"]."\"});</script>";
			}
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
			if($checked) $output .= " checked=\"checked\"";
			$output .= "> ".($label ? $label : "");
			return $output;
		}
		
		public function radioList($name,$values, $labels, $checkid = NULL) {
			if(!is_array($values)) return "";
			$output = "";
			for($i=0;$i<count($values);$i++)
				$output .= $this->radio($name,$values[$i],$checkid == $values[$i] ? true : false, $labels[$i])."<br />";
			return $output;
		}
		
		public function form($link,$options=array()) {
			$output = "<form action=\"".$link."\" ";
			if(isset($options["id"]) && strlen($options["id"]) > 0)
				$output .= "id=\"".$options["id"]."\" ";
			$output .= "method=\"".((isset($options["get"]) && $options["get"]) ? "GET" : "POST")."\"";
			if(isset($options["js"]))
				$output .= " onsubmit=\"return ".$options["js"].";\ ";
			$output .= ">";
			return $output;
		}
		
		public function addIndexedLine($label,$name,$def_value = "", $options = array()) {
			$output = "<tr><td>".$label."</td><td><center>";
			if(isset($options["pwd"]) && $options["pwd"])
				$output .= $this->password($name,$def_value);
			else
				$output .= $this->input($name,$def_value,(isset($options["size"]) ? $options["size"] : 20),(isset($options["length"]) ? $options["length"] : 40),
				(isset($options["label"]) ? $options["label"] : NULL),(isset($options["tooltip"]) ? $options["tooltip"] : NULL));
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedNumericLine($label,$name,$options = array()) {
			$output = "<tr><td>".$label."</td><td><center>";
			$output .= $this->numInput($name,(isset($options["value"]) ? $options["value"] : ""),$options);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedIPLine($idx,$name,$def_value = "") {
			$output = "<tr><td>".$idx."</td><td><center>";
			$output .= $this->IPInput($name,$def_value);
			$output .= "</center></td></tr>";
			return $output;
		}

		public function addIndexedIPMaskLine($idx,$name,$def_value = "") {
			$output = "<tr><td>".$idx."</td><td><center>";
			$output .= $this->IPMaskInput($name,$def_value);
			$output .= "</center></td></tr>";
			return $output;
		}

		public function addIndexedCheckLine($label, $name, $checked = false) {
			$output = "<tr><td>".$label."</td><td><center>";
			$output .= $this->check($name, array("check" => $checked));
			$output .= "</center></td></tr>";
			return $output;	
		}

		public function tableSubmit($label,$options = array()) {
			$output = "<tr><th colspan=\"".(isset($options["size"]) ? $options["size"] : 2)."\"><center>";
			if(isset($options["js"]))
				$output .= $this->JSSubmit((isset($options["name"]) ? $options["name"] : ""),$label,$options["js"]);
			else
				$output .= $this->submit((isset($options["name"]) ? $options["name"] : ""),$label);
			$output .= "</center></th></tr>";
			return $output;
		}

		public function progress($name,$value,$max=100,$label=NULL) {
			$output = "";
			if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<progress id=\"".$name."\" value=\"".$value."\" max=\"".$max."\"></progress><span id=\"".$name."val\"></span>";
			$output .= "<script type=\"text/javascript\">
				eltBar = document.getElementById(\"".$name."\");
				eltPct = document.getElementById(\"".$name."val\");
				eltPct.innerHTML = ' ' + Math.round(eltBar.position * 100) + \"%\";
				</script>";
			return $output;
		}
		
		public function addList($name,$js = "",$label=NULL, $multival=false, $options=array()) {
			$output = "";
			if($label) $output .= "<label for=\"".$name."\">".$label."</label> ";
			$output .= "<select name=\"".$name."\" id=\"".$name."\"";
			if(strlen($js) > 0)
				$output .= " onchange=\"javascript:".$js.";\" ";
			if($multival)
				$output .= " multiple=\"multiple\" ";
			if(isset($options["size"]) && FS::$secMgr->isNumeric($options["size"]))
				$output .= " size=\"".$options["size"]."\" ";
			$output .= ">";
			if(isset($options["tooltip"]))
				$output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".$options["tooltip"]."\"});</script>";
			return $output;
		}
		
		public function addElementToList($name,$value,$selected = false) {
			$output = "<option value=\"".$value."\"";
			if($selected)
				$output .= " selected=\"selected\"";
			$output .= ">".$name."</option>";
			return $output;
		}
		
		public function check($name,$options = array()) {
			$output = "";
			if(isset($options["label"])) $output .= "<label for=\"".$name."\">".$options["label"]."</label> ";
			$output .= "<input type=\"checkbox\" name=\"".$name."\" id=\"".$name."\" ";
			if(isset($options["check"]) && $options["check"])
				$output .= "checked ";
			$output .= " />";
			if(isset($options["tooltip"]))
				$output .= "<script type=\"text/javascript\">$('#".$name."').wTooltip({className: 'tooltip', fadeIn: '200', fadeOut: '100', content: \"".$options["tooltip"]."\"});</script>";
			return $output;
		}
		
		public function img($path,$sizeX = 0,$sizeY = 0, $id = "") {
			$output = "<img src=\"".$path."\" ";
			if(FS::$secMgr->isNumeric($sizeX) && $sizeX > 0)
				$output .= "width=\"".$sizeX."\" ";
			if(FS::$secMgr->isNumeric($sizeY) && $sizeY > 0)
				$output .= "height=\"".$sizeY."\" ";
			if(strlen($id) > 0)
				$output .= "id=\"".$id."\" ";
			$output .= "style=\"border: none;\"/>";	
			return $output;
		}
		
		public function imgWithZoom($path,$sizeX,$sizeY,$maxsizeX ,$maxsizeY,$id) {
			$output = 
            $output = "<div id=\"".$id."\" style=\"width: ".$sizeX."px; height: ".$sizeY."px; overflow: hidden;\"><div style=\"background: url(".$path."); no-repeat; width: ".$sizeX."px; height: ".$sizeY."px;\">";
			$output .= FS::$iMgr->img($path,$sizeX,$sizeY)."</div>";
			$output .= "<div style=\"width:".$maxsizeX."px; height:".$maxsizeY."px;\">";
        	$output .= FS::$iMgr->img($path);
        	$output .= "<div class=\"mapcontent\">";
			$output .= "</div></div></div><script type=\"text/javascript\">$('#".$id."').mapbox({mousewheel: true});</script>";
			return $output;
		}

		public function imgWithLens($path,$sizeX = 0,$sizeY = 0, $id = "", $lsize=200) {
			$output = FS::$iMgr->img($path,$sizeX,$sizeY,$id);
		        $output .= "<script type=\"text/javascript\">$('#".$id."').imageLens(";
			if(FS::$secMgr->isNumeric($lsize))
				$output .= "{ lensSize: ".$lsize." }";
			$output .=");</script>";
			return $output;
		}
		
		public function upload($name) {
			return "<input type=\"file\" name=\"".$name."\" />";
		}
		
		public function canvas($name, $width=480, $height=480) {
			return "<canvas id=\"".$name."\" height=\"".$height."\" width=\"".$width."\">[Votre Navigateur ne supporte pas le HTML5]</canvas>";
		}

		public function tabPanElmt($shid,$link,$label,$cursh) {
			$output = "<li".($shid == $cursh ? " class=\"ui-tabs-selected\"" : "")."><a href=\"".$link."&at=2&sh=".$shid."\">".$label."</a>";
			return $output;
		}
		
		public function opendiv($content,$text1,$text2="Fermer",$divname=NULL, $liname=NULL, $aname=NULL) {
			if($divname == NULL) $divname = uniqid();
			if($liname == NULL) $liname = uniqid();
			if($aname == NULL) $aname = uniqid();
                        $output = "<ul style=\"list-style-type:none;padding:0;\"><li id=\"".$liname."\"><a id=\"".$aname."\" href=\"#\">".$text1."</a>
                       		<a id=\"".$aname."2\" style=\"display:none;\" href=\"#\">".$text2."</a></li></ul>";
                        $output .= "<div id=\"".$divname."\" style=\"display:none;\">".$content."</div>";
			$output .= "<script type=\"text/javascript\">
				$(\"#".$aname."\").click(function(){ $(\"div#".$divname."\").slideDown(\"slow\");});
				$(\"#".$aname."2\").click(function(){ $(\"div#".$divname."\").slideUp(\"slow\");});
				$(\"#".$liname."\").click(function(){ $(\"#".$liname." a\").toggle();});
				</script>";
			return $output;
		}
		// Simple methods
		public function stylesheet($path) {
			$this->arr_css[count($this->arr_css)] = $path;
		}
		
		public function jsinc($path) {
			$this->arr_js[count($this->arr_js)] = $path;
		}
		
		public function printError($msg) {
			return "<div id=\"errorContent\">Erreur: ".$msg."</div>";
		}
		
		public function printDebug($msg) {
			return "<div id=\"debugContent\">Notification: ".$msg."</div>";
		}
		private $arr_css;
		private $arr_js;
	};
?>
