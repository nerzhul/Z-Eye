<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	echo "[".Config::getWebsiteName()."][NetGraph-Generator] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	$file = fopen(dirname(__FILE__)."/../datas/weathermap/main.dot","w+");
	if(!$file) {
		echo  "[".Config::getWebsiteName()."][NetGraph-Generator][FATAL] Can't write ".dirname(__FILE__)."/../datas/weathermap/main.dot !";
		exit(1);
	}
	
	$graphbuffer = "graph maingraph {\ngraph [size=\"30,30\", ratio=fill, bgcolor=white, color=black, fontcolor=white, fontname=lucon, fontsize=14, nodesep=2, overlap=scale, rankrep=\"0.3\", splines=false];\n	node {label=\"\\N\", fillcolor=dimgrey, fixedsize=false, fontcolor=white, fontname=lucon, fontsize=12, shape=box, style=filled];\n edge [color=black};\ngraph [ratio=compress];\n";
	
	$nodelist = array();
	$query = FS::$pgdbMgr->Select("device","name");
	while($data = pg_fetch_array($query)) {
		if(!in_array($data["name"]))
			$nodelist[count($nodelist)] = $data["name"];
	}
	
	$linklist = array();
	$query = FS::$pgdbMgr->Select("device_port","remote_id","remote_id != ''");
	$neighbor_arr = array();
	while($data = pg_fetch_array($query)) {
		if(!in_array($data["remote_id"]))
			$nodelist[count($nodelist)] = $data["remote_id"];
	}
	
	for($i=0;$i<count($nodelist);$i++) {
		 $graphbuffer .= preg_replace("#[.-]#","_",$nodelist[$i])." [label=\"".$nodelist[$i]."\", URL=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$nodelist[$i]."\"];\n";
	}
	
	$query = FS::$pgdbMgr->Select("device_port","ip,remote_id","remote_id != ''");
	while($data = pg_fetch_array($query)) {
		if(!in_array($data["remote_id"]))
			$nodelist[count($nodelist)] = $data["remote_id"];
		$dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
		$graphbuffer .= preg_replace("#[.-]#","_",$dname)." -> ".preg_replace("#[.-]#","_",$data["remote_id"])."\n";
	}
	// $graphbuffer .= "node".$ncount." [label=\"".$data["name"]."\", URL=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$data["name"]."\"];\n";
	
	
	$graphbuffer = "}\n";
	
	echo $graphbuffer;
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][NetGraph-Generator] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>