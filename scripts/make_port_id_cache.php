<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	function makePortIdCache() {
		$query = FS::$pgdbMgr->Select("device","ip,name");
		while($data = pg_fetch_array($query)) {
			$community = FS::$pgdbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$data["name"]."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			$out = "";
			exec("snmpwalk -v 2c -c ".$community." ".$data["ip"]." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			FS::$pgdbMgr->Delete("z_eye_port_id_cache","device = '".$data["name"]."'");
			for($i=0;$i<count($out);$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				$swid = 0;
				$swpid = 0;
				$out2 = "";
				exec("snmpwalk -v 2c -c ".$community." ".$data["ip"]." 1.3.6.1.4.1.9.5.1.4.1.1.11 | grep ".$pid,$out2);
				if(count($out2) > 0) {
					$piddata = explode(" ",$out2[0]);
					if(count($piddata) == 4 && FS::$secMgr->isNumeric($piddata[3])) {
						$piddata = explode(".",$piddata[0]);
						if(count($piddata) > 1 && FS::$secMgr->isNumeric($piddata[count($piddata)-1]) && FS::$secMgr->isNumeric($piddata[count($piddata)-2])) {
							$swid = $piddata[count($piddata)-2];
							$swpid = $piddata[count($piddata)-1];
						}
					}
				}
				FS::$pgdbMgr->Insert("z_eye_port_id_cache","device,portname,pid,switchid,switchportid","'".$data["name"]."','".$pname."','".$pid."','".$swid."','".$swpid."'");
			}
		}
	}
	
	echo "[".Config::getWebsiteName()."][PortId-Caching] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	makePortIdCache();
	
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][PortId-Caching] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";
?>
