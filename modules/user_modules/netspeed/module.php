<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");

	class iNetSpeed extends genModule{
		function iNetSpeed() { parent::genModule(); $this->loc = new lNetSpeed(); }
		public function Load() {
			FS::$iMgr->setCurrentModule($this);
			$output = "";
			if(!FS::isAJAXCall()) {
				$output .= "<h1>".$this->loc->s("title-bw")."</h1>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2\">".$this->loc->s("main-map")."</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=1\">".$this->loc->s("precise-map")."</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
			} else {
				$device = FS::$secMgr->checkAndSecuriseGetData("d");
				$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
				if($device != NULL)
					$output .= $this->showDeviceWeatherMap($device);
				else if($sh == 1)
					$output .= $this->showGeneralFullWeatherMap();
				else
					$output .= $this->showGeneralLightWeatherMap();
			}
			return $output;
		}
		
		private function showDeviceWeatherMap($device) {
			$output = "<h3>".$this->loc->s("link-state")." ".$device."</h3>";
			$output .= FS::$iMgr->img("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralLightWeatherMap() {
			$output = "<h3>".$this->loc->s("net-map")."</h3>";
			$imgsize = getimagesize("datas/weathermap/main-nowifi.png");
			$sizes = preg_split("#\"#",$imgsize[3]);
			$output .= FS::$iMgr->imgWithZoom("datas/weathermap/main-nowifi.svg","1000","700",$sizes[1],$sizes[3],"netmapL");
			return $output;	
		}
		
		private function showGeneralFullWeatherMap() {
			$output = "<h2>".$this->loc->s("net-map-full")."</h2><div id=\"netmapdF\">";
			$imgsize = getimagesize("datas/weathermap/main.png");
			$sizes = preg_split("#\"#",$imgsize[3]);
			$output .= FS::$iMgr->imgWithZoom("datas/weathermap/main.svg","1000","700",$sizes[1],$sizes[3],"netmapF");
			return $output;	
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					break;
				default: break;
			}
		}
	};
?>
