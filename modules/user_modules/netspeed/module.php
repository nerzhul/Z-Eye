<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetSpeed extends genModule{
		function iNetSpeed() { parent::genModule(); }
		public function Load() {
			$output = "";
			if(!FS::isAJAXCall()) {
				$output .= "<div id=\"monoComponent\"><h3>Analyse des Débits</h3>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2\">Carte principale</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=1\">Carte détaillée</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
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
			$output = "<h2>Etat des liens de ".$device."</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralLightWeatherMap() {
			$output = "<h2>Carte du réseau</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/main-nowifi.svg",1000,700,"netmapL");
			$output .= "<script type=\"text/javascript\">$('#netmapL').mapbox({mousewheel: true});</script>";
			return $output;	
		}
		
		private function showGeneralFullWeatherMap() {
			$output = "<h2>Carte complète du réseau</h2>";
			//$output .= FS::$iMgr->addImageWithLens("datas/weathermap/main.svg",1000,700,"netmapF",400);
			$output .= FS::$iMgr->addImage("datas/weathermap/main.svg",1000,700,"netmapF");
			$output .= "<script type=\"text/javascript\">$('#netmapF').mapbox({mousewheel: true});</script>";
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
