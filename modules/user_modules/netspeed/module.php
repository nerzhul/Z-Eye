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
			$output .= FS::$iMgr->addImage("datas/weathermap/main-nowifi.png");
			return $output;	
		}
		
		private function showGeneralFullWeatherMap() {
			$output = "<h2>Carte complète du réseau</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/main.png");
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
