<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	$snmpro = array();
	$snmprw = array();
	$snmpdbrecord = array();
	function getPortId($ip,$portname) {
			$out = "";
			exec("snmpwalk -v 2c -c ".SNMPConfig::$SNMPReadCommunity." ".$ip." ifDescr | grep ".$portname,$out);
			if(!is_array($out) || count($out) == 0 || strlen($out[0]) < 5)
				return -1;
			$out = explode(" ",$out[0]);
			$out = explode(".",$out[0]);
			if(!FS::$secMgr->isNumeric($out[1]))
				return -1;
			return $out[1];
	}
	
	function loadNetdiscoCommunities(&$snmpro,&$snmprw) {
		
		if(Config::getOS() == "FreeBSD")
			$file = file("/usr/local/etc/netdisco/netdisco.conf");
		else if(Config::getOS() == "Debian")
			$file = file("/etc/netdisco/netdisco.conf");
		if(!$file) {
			echo "[".Config::getWebsiteName()."][SNMP-Caching][FATAL] Cannot find/read netdisco.conf !";
			exit(1);
		}
		
		foreach ($file as $lineNumber => $buf) {
			$buf = trim($buf);
			$buf = str_replace("\t", "", $buf);
			$buf = preg_replace("# #", "", $buf);
			$res = preg_split("#=#",$buf);

			if(count($res) == 2) {
				if(preg_match("#^community$#",$res[0])) {
					$tmpro = preg_replace("# #","",$res[1]);
					$snmpro = preg_split("#[,]#",$tmpro);
				}
				else if(preg_match("#^community_rw$#",$res[0])) {
					$tmprw = preg_replace("# #","",$res[1]);
					$snmprw = preg_split("#[,]#",$tmprw);
				}
			}
		}
	}
	
	function checkSNMP($device,$community) {
		if(snmpget($device,$community,"snmpSetSerialNo.0") == false)
			return 1;
		return 0;
	}
	
	function makeSNMPCache($snmpro,$snmprw) {
		$query = FS::$pgdbMgr->Select("device","ip,name");
		while($data = pg_fetch_array($query)) {
			$devro = "";
			$devrw = "";
			
			$foundro = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$data["name"]."'");
			$foundrw = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$data["name"]."'");
			if($foundro && checkSnmp($data["ip"],$foundro) == 0)
				$devro = $foundro;
			if($foundrw && checkSnmp($data["ip"],$foundrw) == 0)
				$devrw = $foundrw;
				
			for($i=0;$i<count($snmpro) && $devro == "";$i++) {
				if(checkSnmp($data["ip"],$snmpro[$i]) == 0)
					$devro = $snmpro[$i];
			}
			
			for($i=0;$i<count($snmprw) && $devrw == "";$i++) {
				if(checkSnmp($data["ip"],$snmprw[$i]) == 0)
					$devrw = $snmprw[$i];
			}
			
			if(strlen($devro) > 0 || strlen($devrw) > 0)
				$snmpdbrecord[$data["name"]] = array("ro" => $devro, "rw" => $devrw);
		}
		
		FS::$dbMgr->Delete("fss_snmp_cache");
		foreach($snmpdbrecord as $key => $value)
			FS::$dbMgr->Insert("fss_snmp_cache","device,snmpro,snmprw","'".$key."','".$value["ro"]."','".$value["rw"]."'");
	}
	
	echo "[".Config::getWebsiteName()."][SNMP-Caching] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	loadNetdiscoCommunities($snmpro,$snmprw);
	makeSNMPCache($snmpro,$snmprw);

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][SNMP-Caching] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>