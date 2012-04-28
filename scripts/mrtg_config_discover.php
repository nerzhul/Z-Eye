<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	$query = FS::$pgdbMgr->Select("device","ip,name");
	while($data = pg_fetch_array($query)) {
		$out = "";
		exec("cfgmaker ".SNMPConfig::$SNMPReadCommunity."@".$data["ip"],$out);
		$out = preg_replace("#Workdir: (.*)\n#","Workdir: ".dirname(__FILE__)."/../datas/rrd\n",$out);
		$file = fopen(dirname(__FILE__)."/../datas/mrtg-config/mrtg-".$data["name"].".cfg","w+");
		if(!$file) {
			echo "[FATAL] Cannot write file to ".dirname(__FILE__)."/../datas/mrtg-config/ !";
			exit(1);
		}
		fwrite($file,$out);
		fclose($file);
	}
	
	
	echo "[".Config::getWebsiteName()."] MRTG Discover done at ".date('d-m-Y G:i:s')."\n";

?>