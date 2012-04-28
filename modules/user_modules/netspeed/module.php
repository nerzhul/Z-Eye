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
			$output = "<h2>Etat des liens de ".$device."</h2>";
			$output .= FS::$iMgr->addImage("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralWeatherMap() {
			$output = "<h2>Etat des liens inter équipements</h2>";
			$query = FS::$pgdbMgr->Select("device_port","ip,port,remote_id","remote_id != ''","ip");
			$neighbor_arr = array();
			while($data = pg_fetch_array($query)) {
				if(!isset($neighbor[$data["ip"]]))
					$neighbor[$data["ip"]] = array();
				$tmparr = $neighbor_arr[$data["ip"]];
				if(!in_array($data["remote_id"],$tmparr))
					$tmparr[count($tmparr)] = array($data["port"],$data["remote_ip"]);
			}
			
			
			
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
