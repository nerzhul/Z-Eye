<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetSpeed extends genModule{
		function iNetSpeed() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Analyse des Débits</h3>";
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if($device != NULL)
				$output .= $this->showDeviceWeatherMap($device);
			else if($sh == 1)
				$output .= $this->showGeneralFullWeatherMap();
			else
				$output .= $this->showGeneralLightWeatherMap();
			$output .= "</div>";
			return $output;
		}
		
		private function showDeviceWeatherMap($device) {
			$output = "<h2>Etat des liens de ".$device."</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralLightWeatherMap() {
			$output = "<h2>Carte du réseau</h2>";
			$output .= "<span id=\"netmap\" class=\"zoom\" style=\"display: inline-block; position: relative; overflow: hidden;\">";
			$output .= "<p>Carte des équipement réseau filaires</p>";
			$output .= FS::$iMgr->addImage("datas/weathermap/main-nowifi.svg",1000,700);
			$output .= "</span><script type=\"text/javascript\">";
			$output .= "$('#netmap').zoom();";
			$output .= "</script>";
			return $output;	
		}
		
		private function showGeneralFullWeatherMap() {
			$output = "<h2>Carte complète du réseau</h2>";
			$output .= "<span id=\"netmap\" class=\"zoom\" style=\"display: inline-block; position: relative; overflow: hidden;\">";
			$output .= "<p>Carte des équipement réseau filaires</p>";
			$output .= FS::$iMgr->addImage("datas/weathermap/main.svg",1000,700);
			$output .= "</span><script type=\"text/javascript\">";
			$output .= "$('#netmap').zoom();";
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