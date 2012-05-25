<?php
require_once(dirname(__FILE__)."/../../config/snmp.conf.php");
class SNMPMgr {
        function SNMPMgr() {}
		
		public function get($addr,$path) {
			return snmpget($addr,SNMPConfig::$SNMPReadCommunity,$path);			
		}
		
		public function set($addr,$path,$valuetype,$value) {
			snmpset($addr,SNMPConfig::$SNMPWriteCommunity,$path,$valuetype,$value);
		}
		
		public function setInt($addr,$path,$value) {
			$this->set($addr,$path,"i",$value);	
		}
		
		public function walk($addr,$path) {
			return snmpwalk($addr,SNMPConfig::$SNMPReadCommunity,$path);
		}
	};
?>