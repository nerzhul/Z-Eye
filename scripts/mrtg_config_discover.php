<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	echo "[".Config::getWebsiteName()."][MRTG-Discover] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	$query = FS::$pgdbMgr->Select("device","ip,name");
	while($data = pg_fetch_array($query)) {
		$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$data["name"]."'");
		if($community == NULL) $community = SNMPConfig::$SNMPReadCommunity;

		echo "[".Config::getWebsiteName()."][MRTG-Discover] Do: cfgmaker ".$community."@".$data["ip"]."\n";
		$out = "";
		exec("cfgmaker ".$community."@".$data["ip"],$out);
		$file = fopen(dirname(__FILE__)."/../datas/mrtg-config/mrtg-".$data["name"].".cfg","w+");
		if(!$file) {
			echo "[".Config::getWebsiteName()."][MRTG-Discover][FATAL] Cannot write file to ".dirname(__FILE__)."/../datas/mrtg-config/ !";
			exit(1);
		}
		$wkdirwr = 0;
		echo "[".Config::getWebsiteName()."][MRTG-Discover] Do: write ".dirname(__FILE__)."/../datas/mrtg-config/mrtg-".$data["name"].".cfg\n";
		for($i=0;$i<count($out);$i++) {
			if(preg_match("#WorkDir:#",$out[$i])) {
				if($wkdirwr == 0) {
					$towr = "WorkDir: ".dirname(__FILE__)."/../datas/rrd\n";
					$wkdirwr = 1;
				}
			}
			else
				$towr = $out[$i];
				
			fwrite($file,$towr."\n");
		}
		fclose($file);
	}
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][MRTG-Discover] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";
?>