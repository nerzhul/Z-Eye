<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/

	require_once(dirname(__FILE__)."/FS.main.php");
	require_once(dirname(__FILE__)."/../ckeditor/ckeditor.php");
	require_once(dirname(__FILE__)."/MySQLMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/HTTPLink".CLASS_EXT);
	require_once(dirname(__FILE__)."/modules/SVNRevision".CLASS_EXT);

	class FSInterfaceMgr {
		function FSInterfaceMgr($DBMgr) {
			$this->dbMgr = $DBMgr;	
		}
		
		public function InitComponents() {
			$this->arr_css = array();
			$this->arr_js = array();
		}
		
		
		// Complex methods
		public function CreateSelectFromDB($sname,$table,$name,$idx,$cond = "", $order = "", $ordersens = 0,$selectidx = NULL, $default = false) {
			$output = "<select name=\"".$sname."\">";
			$count = 0;
			if($default) {
				$output .= "<option value=\"none\"";
				if($selectidx == NULL)
					$output .= " selected=\"selected\"";
				$output .= ">------------</option>";
				
			}
				
			$query = FS::$dbMgr->Select($table,$name.','.$idx,$cond,$order,$ordersens);
			while($data = mysql_fetch_array($query)) {
				$count++;
				$output .= "<option value=\"".$data[$idx]."\"";
				if($selectidx == $data[$idx])
					$output .= " selected=\"selected\"";
				$output .= ">".$data[$name]."</option>";
			}
			if($count == 0 && !$default)
				$output .= "<option>------------</option>";
			$output .= "</select>";
			return $output;			
		}

		// header/footer/content
		
		public function showHeader() {
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
				
				$output .= "<script type=\"text/javascript\" src=\"lib/ckeditor/ckeditor.js\"></script>
				<script type=\"text/javascript\" src=\"lib/ckeditor/config.js?t=ABLC4TW\"></script>
				<script type=\"text/javascript\" src=\"lib/ckeditor/lang/fr.js?t=ABLC4TW\"></script>
				<script type=\"text/javascript\" src=\"lib/ckeditor/plugins/styles/styles/default.js?t=ABLC4TW\"></script>";
				
				$output .= "</head>
				<body class=\"body\">";
			return $output;
		}
		
		public function showFooter() {
			return "</body></html>";
		}
		
		public function showContent() {
			return "<div id=\"main\"></div>";
		}
		
		public function loadMenu($id) {
			$output = "";
			$query = $this->dbMgr->Select("fss_menus","name,ulevel,isconnected","id = '".$id."'");
			if($data = mysql_fetch_array($query)) {
				$conn = FS::$sessMgr->isConnected();
				if((!$conn && $data["isconnected"] == -1 || $conn && $data["isconnected"] == 1 || $data["isconnected"] == 0) &&
				(FS::$sessMgr->getUserLevel() >= $data["ulevel"]))
				{
					$output .= "<ul id=\"menu\"><h3>".$data["name"]."</h3>";
					$query2 = $this->dbMgr->Select("fss_menu_link","id_menu_item","id_menu = '".$id."'","`order`");
					while($data2 = mysql_fetch_array($query2)) {
						$query3 = $this->dbMgr->Select("fss_menu_items","title,link,isconnected,ulevel","id = '".$data2["id_menu_item"]."'");
						while($data3 = mysql_fetch_array($query3))
							if((!$conn && $data3["isconnected"] == -1 || $conn && $data3["isconnected"] == 1 || $data3["isconnected"] == 0) &&
								(FS::$sessMgr->getUserLevel() >= $data3["ulevel"])) {
								$link = new HTTPLink($data3["link"]);
								$output .= "<li><a class=\"menuText\" href=\"".$link->getIt()."\">".$data3["title"]."</a>";	
							}
					}
					$output .= "</ul>";
				}
			}
			else
				$output .= $this->printError("Menu ".$id." undefined");
			return $output;
		}
		
		private function getJSONLink($jsonstr) {
			$jsonstr = preg_replace("#\"#","&quot;",$jsonstr);
			return "javascript:getPage('".$jsonstr."');";			
		}
		
		public function addJSONLink($jsonstr,$text) {
			return "<a class=\"monoComponentt_a\" href=\"".FS::$iMgr->getJSONLink($jsonstr)."\">".$text."</a>";
		}
		
		public function addTextEditor($fieldname, $value = "") {
			$CKEditor = new CKEditor();
			$CKEditor->returnOutput = true;
 			return $CKEditor->editor($fieldname, $value);
		}

		public function addLabel($for,$value,$class = "") {
			return "<label class=\"".$class."\" for=\"".$for."\">".$value."</label>";
		}
		
		public function addInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" />";
		}
		
		public function addNumericInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:ReplaceNotNumeric('".$name."');\" />";
		}
		
		public function addIPInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkIP('".$name."');\" />";
		}
		
		public function addIPMaskInput($name, $def_value = "", $size = 20, $length = 40) {
			return "<input type=\"textbox\" name=\"".$name."\" id=\"".$name."\" value=\"".$def_value."\" size=\"".$size."\" maxlength=\"".$length."\" onkeyup=\"javascript:checkMask('".$name."');\" />";
		}
		
		public function addHidden($name, $value) {
			return "<input type=\"hidden\" id=\"".$name."\" name=\"".$name."\" value=\"".$value."\" />";	
		}
		
		public function addPasswdField($name, $def_value = "") {
			return "<input type=\"password\" name=\"".$name."\" value=\"".$def_value."\" />";
		}
		
		public function addSubmit($name, $value) {
			return "<input class=\"buttonStyle\" type=\"submit\" name=\"".$name."\" value=\"".$value."\" />";	
		}
		
		public function addJSSubmit($name, $value, $function) {
			return "<input class=\"buttonStyle\" type=\"submit\" name=\"".$name."\" value=\"".$value."\" onclick=\"".$function."\" />";	
		}
		
		public function addRadio($name, $value, $checked = false) {
			$output = "<input id=\"".$name."\" type=\"radio\" value=\"".$value."\" name=\"".$name."\"";
			if($checked) $output .= " checked=\"checked\"";
			$output .= "> ".$value;
			return $output;
		}
		
		public function addRadioList($name,$values, $checkid = NULL) {
			if(!is_array($values)) return "";
			$output = "";
			for($i=0;$i<count($values);$i++)
				$output .= $this->addRadio($name,$values[$i],$checkid == $values[$i] ? true : false);
			return $output;
		}
		
		public function addForm($link,$id="",$post=true) {
			$output = "<form action=\"".$link."\" ";
			if(strlen($id) > 0)
				$output .= "id=\"".$id."\" ";
			$output .= "method=\"".($post ? "POST" : "GET")."\" >";
			return $output;
		}
		
		public function addFormWithReturn($link,$jsfunct,$id="") {
			$output = "<form action=\"".$link->getIt()."\" ";			
			if(strlen($id) > 0)
				$output .= "id=\"".$id."\" ";
			$output .= "method=\"POST\" onsubmit=\"return ".$jsfunct.";\">";
			return $output;
		}
		
		public function addIndexedLine($idx,$name,$def_value = "", $pwd = false) {
			$output = "<tr><td>".$idx."</td><td><center>";
			if($pwd)
				$output .= $this->addPasswdField($name,$def_value);
			else
				$output .= $this->addInput($name,$def_value);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedNumericLine($idx,$name,$def_value = "",$size = 20, $length = 40) {
			$output = "<tr><td>".$idx."</td><td><center>";
			$output .= $this->addNumericInput($name,$def_value,$size,$length);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedIPLine($idx,$name,$def_value = "") {
			$output = "<tr><td>".$idx."</td><td><center>";
			$output .= $this->addIPInput($name,$def_value);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedIPMaskLine($idx,$name,$def_value = "") {
			$output = "<tr><td>".$idx."</td><td><center>";
			$output .= $this->addIPInput($name,$def_value);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addIndexedCheckLine($label, $name, $checked = false) {
			$output = "<tr><td>".$label."</td><td><center>";
			$output .= $this->addCheck($name, $checked);
			$output .= "</center></td></tr>";
			return $output;	
		}
		
		public function addTableSubmit($name,$value,$size = 2) {
			$output = "<tr><td colspan=\"".$size."\"><center>";
			$output .= $this->addSubmit($name,$value);
			$output .= "</center></td></tr>";
			return $output;
		}
		
		public function addList($name,$js = "") {
			$output = "<select name=\"".$name."\" id=\"".$name."\"";
			if(strlen($js) > 0)
				$output .= " onchange=\"javascript:".$js.";\" ";
			
			$output .= ">";
			return $output;
		}
		
		public function addElementToList($name,$value,$selected = false) {
			$output = "<option value=\"".$value."\"";
			if($selected)
				$output .= " selected=\"selected\"";
			$output .= ">".$name."</option>";
			return $output;
		}
		
		public function addCheck($name,$checked = false) {
			$output = "<input type=\"checkbox\" name=\"".$name."\" ";
			if($checked)
				$output .= "checked ";
			$output .= " />";
			return $output;
		}
		
		public function addImage($path,$sizeX = 0,$sizeY = 0, $id = "") {
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
		
		public function addImageWithZoom($path,$sizeX,$sizeY,$maxsizeX ,$maxsizeY,$id) {
			$output = 
            $output = "<div id=\"".$id."\" style=\"width: ".$sizeX."px; height: ".$sizeY."px; overflow: hidden;\"><div style=\"background: url(".$path."); no-repeat; width: ".$sizeX."px; height: ".$sizeY."px;\">";
			$output .= FS::$iMgr->addImage($path,$sizeX,$sizeY)."</div>";
			$output .= "<div style=\"width:".$maxsizeX."px; height:".$maxsizeY."px;\">";
        	$output .= FS::$iMgr->addImage($path);
        	$output .= "<div class=\"mapcontent\">";
			$output .= "</div></div></div><script type=\"text/javascript\">$('#".$id."').mapbox({mousewheel: true});</script>";
			return $output;
		}
		
		public function addImageWithLens($path,$sizeX = 0,$sizeY = 0, $id = "", $lsize=200) {
			$output = FS::$iMgr->addImage($path,$sizeX,$sizeY,$id);
            $output .= "<script type=\"text/javascript\">$('#".$id."').imageLens(";
			if(FS::$secMgr->isNumeric($lsize))
				$output .= "{ lensSize: ".$lsize." }";
			$output .=");</script>";
			return $output;
		}
		
		public function addUpload($name) {
			return "<input type=\"file\" name=\"".$name."\" />";	
		}
		
		public function addCanvas($name, $width=480, $height=480) {
			return "<canvas id=\"".$name."\" height=\"".$height."\" width=\"".$width."\">[Votre Navigateur ne supporte pas le HTML5]</canvas>";
		}
		
		// Simple methods
		public function addStylesheet($path) {
			$this->arr_css[count($this->arr_css)] = $path;
		}
		
		public function addJSFile($path) {
			$this->arr_js[count($this->arr_js)] = $path;
		}
		
		public function showSVNRev() {
			$svnrev = new SVNRevision();
			$rev = $svnrev->getRevision();
			return $rev;
		}
		
		public function printError($msg) {
			return "<div id=\"errorContent\">Erreur: ".$msg."</div>";
		}
		
		public function printDebug($msg) {
			return "<div id=\"debugContent\">Notification: ".$msg."</div>";
		}
		private $arr_css;
		private $arr_js;
		protected $dbMgr;
	};
?>
