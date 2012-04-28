<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetSpeed extends genModule{
		function iNetSpeed() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Analyse des Débits</h3>";
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			if($device != NULL)
				$output .= $this->showDeviceWeatherMap($device);
			else
				$output .= $this->showGeneralWeatherMap();
			$output .= "</div>";
			return $output;
		}
		
		private function showDeviceWeatherMap($device) {
			$dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$device."'");
			if(!$dname)
				return FS::$iMgr->printError("Cet équipement n'existe pas !");
				
			$output = "<h2>Etat des liens de ".$dname."</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/".$dname.".png");
			return $output;	
		}
		private function showGeneralWeatherMap() {
			$output = "<h2>Etat des liens inter équipements</h2>";
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
