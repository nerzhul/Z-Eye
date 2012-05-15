<?php
		public function getPortId($device,$portname) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
				
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep ".$portname,$out);
			if(strlen($out[0]) < 5)
				return -1;
			$out = explode(" ",$out[0]);
			$out = explode(".",$out[0]);
			if(!FS::$secMgr->isNumeric($out[1]))
				return -1;
			return $out[1];
		}
		
		// Saving running-config => startup-config
		public function writeMemory($device) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"1");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"4");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"3");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"1");
			
			FS::$snmpMgr->get($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);

			return 0;
		}
?>
