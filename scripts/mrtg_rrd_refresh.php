<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	$dir = opendir(dirname(__FILE__)."/../datas/mrtg-config/");
	while(($elem = readdir($dir))) {
		$fpath = dirname(__FILE__)."/../datas/mrtg-config/".$elem;
		if(is_file($fpath))
			exec("env LANG=C /usr/bin/mrtg ".$fpath);
	}
	echo "[".Config::getWebsiteName()."] MRTG RRD Refresh done at ".date('d-m-Y G:i:s')."\n";

?>