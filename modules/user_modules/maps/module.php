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

	class iMaps extends genModule{
		function iMaps() { parent::genModule(); $this->loc = new lMaps(); }
		public function Load() {
			FS::$iMgr->setCurrentModule($this);

			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";

			if(!FS::isAJAXCall()) {
				$output .= "<h1>".$this->loc->s("title-maps")."</h1>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid,$this->loc->s("icinga-map"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,$this->loc->s("net-map"),$sh);
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("net-map-full"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
			} else {
				$device = FS::$secMgr->checkAndSecuriseGetData("d");
				if($device != NULL)
					$output .= $this->showDeviceWeatherMap($device);
				else if($sh == 1)
					$output .= $this->showGeneralFullWeatherMap();
				else if($sh == 2)
					$output .= $this->showGeneralLightWeatherMap();
				else if($sh == 3)
					$output .= $this->showIcingaMap();
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-tab"));
			}
			return $output;
		}
		
		private function showDeviceWeatherMap($device) {
			$output = "<h3>".$this->loc->s("link-state")." ".$device."</h3>";
			$output .= FS::$iMgr->img("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralLightWeatherMap() {
			return FS::$iMgr->imgWithZoom2("datas/weathermap/main-nowifi-tiny.svg",$this->loc->s("net-map-full"),"netmapF","datas/weathermap/main-nowifi.png");
		}
		
		private function showGeneralFullWeatherMap() {
			return FS::$iMgr->imgWithZoom2("datas/weathermap/main-tiny.svg",$this->loc->s("net-map-full"),"netmapF","datas/weathermap/main.png");
		}

		private function showIcingaMap() {
			return FS::$iMgr->imgWithZoom2("cgi-bin/icinga/statusmap.cgi?host=all&createimage&layout=5",$this->loc->s("icinga-map"),"netmapI");
		}
		
		public function handlePostDatas($act) {}
	};
?>
