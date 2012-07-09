<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	$portbuffer = array();

	function getPortId($device,$portname,&$portbuffer) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;

			if(!isset($portbuffer[$dip])) $portbuffer[$dip] = array();
			if(!isset($portbuffer[$dip][$portname])) {
				$community = FS::$pgdbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
				if($community == NULL) $community = SNMPConfig::$SNMPReadCommunity;
				exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep ".$portname,$out);
				if(!is_array($out) || count($out) == 0 || strlen($out[0]) < 5)
					return -1;
				$out = explode(" ",$out[0]);
				$out = explode(".",$out[0]);
				if(!FS::$secMgr->isNumeric($out[1]))
					return -1;
				$portbuffer[$dip][$portname] = $out[1];
				return $out[1];
			}
			else
				return $portbuffer[$dip][$portname];
	}
		
	function generateGraph($filename, &$portbuffer, $options=array()) {
		$file = fopen(dirname(__FILE__)."/../datas/weathermap/".$filename.".dot","w+");
		if(!$file) {
			echo  "[".Config::getWebsiteName()."][NetGraph-Generator][FATAL] Can't write ".dirname(__FILE__)."/../datas/weathermap/".$filename.".dot !";
			exit(1);
		}
		// penwidth for epaisseur
		if(!in_array("NO-DIRECTION",$options))
			$graphbuffer = "digraph maingraph {\ngraph [bgcolor=white, nodesep=1];\n	node [label=\"\N\", color=white, fontcolor=black, fontname=lucon, shape=plaintext];\n edge [color=black];\n";
		else
			$graphbuffer = "graph maingraph {\ngraph [bgcolor=white, nodesep=1];\n        node [label=\"\N\", color=white, fontcolor=black, fontname=lucon, shape=plaintext];\n edge [color=black];\n";
		$nodelist = array();
		$query = FS::$pgdbMgr->Select("device","model, name");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options) && preg_match("#AIRAP#",$data["model"]))
				continue;
			if(!in_array($data["name"],$nodelist))
				$nodelist[count($nodelist)] = $data["name"];
		}
		
		/*$query = FS::$pgdbMgr->Select("device_port","remote_id","remote_id != ''","ip,remote_id");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options)) {
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","name = '".$data["remote_id"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
			}
			if(!in_array($data["remote_id"],$nodelist))
				$nodelist[count($nodelist)] = $data["remote_id"];
		}*/
		
		/*for($i=0;$i<count($nodelist);$i++) {
			 $graphbuffer .= preg_replace("#[.-]#","_",$nodelist[$i])." [label=\"".$nodelist[$i]."\", URL=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$nodelist[$i]."\", size=20];\n";
		}*/
		
		$outlink = array();
		$query = FS::$pgdbMgr->Select("device_port","ip,port,speed,remote_id","remote_id != ''","ip,remote_id");
		while($data = pg_fetch_array($query)) {
			if(in_array("NO-WIFI",$options)) {
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","name = '".$data["remote_id"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
				$dmodel = FS::$pgdbMgr->GetOneData("device","model","ip = '".$data["ip"]."'");
				if(preg_match("#AIRAP#",$dmodel)) continue;
			}
			if(!in_array($data["remote_id"],$nodelist)) {
				$nodelist[count($nodelist)] = $data["remote_id"];
				$graphbuffer .= preg_replace("#[.-]#","_",$data["remote_id"])." [label=\"".$data["remote_id"]."\", URL=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$data["remote_id"]."\"];\n";
			}
			$dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
			$outcharge = 0;
			$incharge = 0;
			$pid = getPortId($dname,$data["port"],$portbuffer);
			if($pid != -1) {
				$mrtgfilename = $data["ip"]."_".$pid.".log";
				$mrtgfile = file(dirname(__FILE__)."/../datas/rrd/".$mrtgfilename);
				for($i=1;$i<2;$i++) {
					$outputbw = 0;
					$res = preg_split("# #",$mrtgfile[$i]);
					if(count($res) == 5) {
						if($data["speed"] == 0) {
							$inputbw = 0;
							$outputbw = 0;
						} else {
							$inputbw = $res[1];
							$outputbw = $res[2];
							$maxbw = preg_split("# #",$data["speed"]);
							if(count($maxbw) == 2) {
								if($maxbw[1] == "Gbit" || $maxbw[1] == "Gbps")
									$maxbw = $maxbw[0] * 1000000000;
								else if($maxbw[1] == "Mbit" || $maxbw[1] == "Mbps")
									$maxbw = $maxbw[0] * 1000000;
							}
							else
								$maxbw = $maxbw[0];
							$outcharge = $outputbw / $maxbw;
							$incharge = $inputbw / $maxbw;
						}
						
						if(!isset($outlink[$dname])) $outlink[$dname] = array();
						if(!in_array("NO-DIRECTION",$options) || in_array("NO-DIRECTION",$options) && !isset($outlink[$data["remote_id"]][$dname]))
							$outlink[$dname][$data["remote_id"]] = array("lock" => 1, "chrg" => $outcharge);
						if(!in_array("NO-DIRECTION",$options)) {
							if(!isset($outlink[$data["remote_id"]])) $outlink[$data["remote_id"]] = array();
							if(!isset($outlink[$data["remote_id"]][$dname]) || $outlink[$data["remote_id"]][$dname]["lock"] == 0)
								$outlink[$data["remote_id"]][$dname] = array("lock" => 0, "chrg" => $incharge);
						}
					}
				}
			}
		}
		
		foreach($outlink as $dname => $outlinkkey) {
			foreach($outlinkkey as $remotename => $outlinkval) {
				$outcharge = $outlink[$dname][$remotename]["chrg"];
				$penwidth = "1.0";
				$pencolor = "black";
				
				if($outcharge > 0 && $outcharge < 10) {
					$penwidth = "1.0";
					$pencolor = "#8C00FF";
				}
				else if($outcharge < 25) {
					$penwidth = "1.5";
					$pencolor = "#2020FF";
				}
				else if($outcharge < 40) {
					$penwidth = "2.0";
					$pencolor = "#00C0FF";	
				}
				else if($outcharge < 55) {
					$penwidth = "3.0";
					$pencolor = "#00F000";
				}
				else if($outcharge < 70) {
					$penwidth = "4.0";
					$pencolor = "#F0F000";
				}
				else if($outcharge < 85) {
					$penwidth = "4.5";
					$pencolor = "#FFC000";
				}
				else {
					$pendwith = "5.0";
					$pencolor = "red";
				}
				if(in_array("NO-DIRECTION",$options))
					$graphbuffer .= preg_replace("#[.-]#","_",$remotename)." -- ".preg_replace("#[.-]#","_",$dname)." [color=\"".$pencolor."\", penwidth=".$penwidth."];\n";
				else
					$graphbuffer .= preg_replace("#[.-]#","_",$remotename)." -> ".preg_replace("#[.-]#","_",$dname)." [color=\"".$pencolor."\", penwidth=".$penwidth."];\n";
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
	
	generateGraph("main", $portbuffer);
	generateGraph("main-nodir", $portbuffer, array("NO-DIRECTION"));
	// Without WiFi APs
	generateGraph("main-nowifi", $portbuffer, array("NO-WIFI"));
	generateGraph("main-nodir-nowifi", $portbuffer, array("NO-DIRECTION","NO-WIFI"));

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][NetGraph-Generator] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
