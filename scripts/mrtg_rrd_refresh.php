<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	$dir = opendir(dirname(__FILE__)."/../datas/mrtg-config/");
	while(($elem = readdir($dir))) {
		$fpath = dirname(__FILE__)."/../datas/mrtg-config/".$elem;
		if(is_file($fpath)) {
			echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] Do: env LANG=C /usr/bin/mrtg ".$fpath."\n";
			exec("env LANG=C /usr/bin/mrtg ".$fpath);
		}
	}
	
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>