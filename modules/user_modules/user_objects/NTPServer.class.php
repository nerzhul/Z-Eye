<?php
	define('DNS_RECORD_TABLE',"fss_ntp_servers");
	
	class NTPServer {
		private $serveraddr;
		private $prefered;
		private $disabled;
		
		function NTPServer($addr) {
			$this->serveraddr = $addr;
			$this->prefered = true;
			$this->disabled = false;
		}
		
		public function Load() {
			$query = FS::$dbMgr->Select(NTP_RECORD_TABLE,"disabled, prefered","server_addr = '".$this->serveraddr."'");
			if($data = mysql_fetch_array($query)) {
				$this->prefered = ($data["prefered"] == 1 ? true : false);
				$this->disabled = ($data["disabled"] == 1 ? true : false);
				return true;
			}
			else
				return false;
		}
		
		public function Save() {
			if(FS::$dbMgr->GetOneData(NTP_RECORD_TABLE,"server_addr","server_addr = '".$this->serveraddr."'"))
				FS::$dbMgr->Update(NTP_RECORD_TABLE,"disabled = '".($this->disabled == true ? 1 : 0)."', prefered = '".($this->prefered == true ? 1 : 0)."'","server_addr = '".$this->serveraddr."'");
			else
				FS::$dbMgr->Insert(NTP_RECORD_TABLE,"server_addr, disabled, prefered","'".$this->serveraddr."','".($this->disabled == true ? 1 : 0)."','".($this->prefered == true ? 1 : 0)."'");
		}
		
		public function Delete() {
			FS::$dbMgr->Delete(NTP_RECORD_TABLE,"server_addr = '".$this->serveraddr."'");
		}
		
		/* Setters */
		public function setServerAddr($addr) { $this->serveraddr = $addr; }
		public function setPrefered($pref) { $this->prefered = $pref; }
		public function setDisabled($dis) { $this->disabled = $dis; }
		
		/* Getters */
		public function getServerAddr() { return $this->serveraddr; }
		public function getPrefered() { return $this->prefered; }
		public function getDisabled() { return $this->disabled; }
	}

?>