<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	function generateGraph($filename, $options=array()) {
		$file = fopen(dirname(__FILE__)."/../datas/weathermap/".$filename.".dot","w+");
		if(!$file) {
			echo  "[".Config::getWebsiteName()."][NetGraph-Generator][FATAL] Can't write ".dirname(__FILE__)."/../datas/weathermap/".$filename.".dot !";
			exit(1);
		}
		
		$graphbuffer = "digraph maingraph {\ngraph [bgcolor=white, nodesep=1];\n	node [label=\"\N\", color=white, fontcolor=black, fontname=lucon, shape=plaintext];\n edge [color=black];\n";
		
		$nodelist = array();
		$query = FS::$pgdbMgr->Select("device","model, name");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options) && preg_match("#AIRAP#",$data["model"]))
				continue;
			if(!in_array($data["name"],$nodelist))
				$nodelist[count($nodelist)] = $data["name"];
		}
		
		$query = FS::$pgdbMgr->Select("device_port","remote_id","remote_id != ''");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options)) {
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","name = '".$data["remote_id"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
			}
			if(!in_array($data["remote_id"],$nodelist))
				$nodelist[count($nodelist)] = $data["remote_id"];
		}
		
		for($i=0;$i<count($nodelist);$i++) {
			 $graphbuffer .= preg_replace("#[.-]#","_",$nodelist[$i])." [label=\"".$nodelist[$i]."\", URL=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$nodelist[$i]."\"];\n";
		}
		
		$peer_arr = array();
		$query = FS::$pgdbMgr->Select("device_port","ip,remote_id","remote_id != ''");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options)) {
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","name = '".$data["remote_id"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","ip = '".$data["ip"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
			}
			if(!in_array($data["remote_id"],$nodelist))
				$nodelist[count($nodelist)] = $data["remote_id"];
			$dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
			if(!isset($peer_arr[$data["ip"]])) $peer_arr[$data["ip"]] = array();
			if(!in_array($data["remote_id"],$peer_arr[$data["ip"]])) {
				$peer_arr[$data["ip"]][count($peer_arr[$data["ip"]])] = $data["remote_id"];
				$graphbuffer .= preg_replace("#[.-]#","_",$dname)." -> ".preg_replace("#[.-]#","_",$data["remote_id"])."\n";
			}
		}
		
		$graphbuffer .= "}\n";
		
		fwrite($file,$graphbuffer);
		fclose($file);
		
		exec("circo -Tsvg ".dirname(__FILE__)."/../datas/weathermap/".$filename.".dot -o ".dirname(__FILE__)."/../datas/weathermap/".$filename.".svg");
		exec("circo -Tpng ".dirname(__FILE__)."/../datas/weathermap/".$filename.".dot -o ".dirname(__FILE__)."/../datas/weathermap/".$filename.".png");
	}
	
	echo "[".Config::getWebsiteName()."][NetGraph-Generator] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	generateGraph("main");
	
	// Without WiFi APs
	generateGraph("main-nowifi",array("NO-WIFI"));

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][NetGraph-Generator] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>