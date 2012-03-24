<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../central/rrd.php");
	class iStats extends genModule{
		function iStats($iMgr) { parent::genModule($iMgr); }

		public function Load() {
			$stype = FS::$secMgr->checkAndSecuriseGetData("s");
			if($stype == NULL) $stype = 1;

			$output = "<div id=\"monoComponent\">";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
			$output .= FS::$iMgr->addList("stype");
			$output .= FS::$iMgr->addElementToList("Statistiques d'attaque",1,$stype == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("Statistiques DHCP",2, $stype == 2 ? true : false);
			$output .= FS::$iMgr->addElementToList("Cartographie Icinga (Nagios)",3, $stype == 3 ? true : false);
			$output .= FS::$iMgr->addElementToList("Débits up/down Switches en cours",4, $stype == 4 ? true : false);
			$output .= FS::$iMgr->addElementToList("Statistiques de débit",5, $stype == 5 ? true : false);
			$output .= "</select>";
			$output .= FS::$iMgr->addSubmit("Aller","Aller")."</form><br />";
			switch($stype) {
				case 1: $output .= $this->loadAttackStats(); break;
				case 2:	$output .= $this->loadDHCPStats(); break;
				case 3: $output .= $this->loadNagiosMap(); break;
				case 4: $output .= $this->loadWeathermaps(); break;
				case 5: $output .= $this->loadDebitStats(); break;
				default:$output .= $this->loadAttackStats(); break;
			}
			$output .= "</div>";
			return $output;
		}

		private function loadDebitStats() {
			$output = "<h3>Statistiques de débit (en Ko)</h3><h4>Upload</h4>";
			$output .= "<canvas id=\"debst\" height=\"450\" width=\"1175\"></canvas><h4>Download</h4>";
			$output .= "<canvas id=\"debdw\" height=\"450\" width=\"1175\"></canvas>";

			// @todo : date conditions
			$result = array();
			mysql_select_db("snort");

			$year = date("Y");
                        $month = date("m");
                        $day = date("d");

                        $sql_date = $year."-".$month."-".$day." 00:00:00";
			$sql_date2 = $year."-".$month."-".$day." 23:59:59";

			$query = FS::$dbMgr->Select("debit_stats","debitdate,linklabel,up,down","debitdate > '".$sql_date."' AND debitdate < '".$sql_date2."'","debitdate");
			while($data = mysql_fetch_array($query)) {
				if(!isset($result[$data["linklabel"]])) $result[$data["linklabel"]] = array();
				$result[$data["linklabel"]][$data["debitdate"]]["up"] = $data["up"];
				$result[$data["linklabel"]][$data["debitdate"]]["down"] = $data["down"];
			}
			$output .= "<script>window.onload = function (){var data = ";
			$output .= "[";
			$arr_up = array();
			$arr_down = array();
			foreach($result as $key => $value) {
				if(!isset($arr_up[$key])) $arr_up[$key] = "";
				if(!isset($arr_down[$key])) $arr_down[$key] = "";
				foreach($result[$key] as $key2 => $value2) {
					$arr_up[$key] .= ($value2["up"]*8/1024).",";
					$arr_down[$key] .= ($value2["down"]*8/1024).",";
				}
				$arr_up[$key] = substr($arr_up[$key],0,strlen($arr_up[$key])-1);
				$arr_down[$key] = substr($arr_down[$key],0,strlen($arr_down[$key])-1);
			}
			$fudx = 0;
			$ech = 1;
			$temp1 = 0;
			$cursor = 0;
                        foreach($arr_up as $key => $value) {
				if($cursor == $ech) {
					if($fudx == 0) {
						$fudx = 1;
						$output .= "[";
					}
					else
						$output .= ",[";

					//$output .= $temp1;
					$output .= $arr_up[$key];
					$output .= "]";
					$cursor = $temp = 0;
				}
				else {
					$temp1 += $arr_up[$key];
					$cursor++;
				}
                        }

			$output .= "];";
                        $output .= "var line = new RGraph.Line(\"debst\", data);";
                        $output .= "line.Set('chart.yaxispos', 'right');
                        line.Set('chart.hmargin', 15);
                        line.Set('chart.tickmarks', 'endcircle');
                        line.Set('chart.linewidth', 2);
                        line.Set('chart.shadow', true);
                        line.Set('chart.gutter.top', 5);
                        line.Set('chart.gutter.right', 100);
                        line.Set('chart.key', [";

			$fidx = 0;
                        foreach($result as $key => $value) {
                                if($fidx == 0) {
                                        $fidx = 1;
                                        $output .= "'".$key."'";
                                }
                                else
                                        $output .= ",'".$key."'";
                        }

			$output .= "]);
                        line.Set('chart.gutter.bottom', 45); ";

                        $output .= "line.Set('chart.labels', [";
/*			$fidx = 0;
                        foreach($result as $key => $value) {
                                if($fidx == 0) {
                                        $fidx = 1;
                                        $output .= "'".$key."'";
                                }
                                else
                                        $output .= ",'".$key."'";
                        }*/

			$output .= "]);";
                        $output .= "line.Draw();";

			// down

			$output .= "var data2 = ";
                        $output .= "[";
                        $fudx = 0;
                        $ech = 1;
                        $temp1 = 0;
                        $cursor = 0;

			foreach($arr_down as $key => $value) {
                                if($cursor == $ech) {
                                        if($fudx == 0) {
                                                $fudx = 1;
                                                $output .= "[";
                                        }
                                        else
                                                $output .= ",[";
                                
                                        //$output .= $temp1;
                                        $output .= $arr_down[$key];
                                        $output .= "]";
                                        $cursor = $temp = 0;
                                }
                                else {
                                        $temp1 += $arr_down[$key];
                                        $cursor++;
                                }
                        }

                        $output .= "];";
                        $output .= "var line2 = new RGraph.Line(\"debdw\", data2);";
                        $output .= "line2.Set('chart.yaxispos', 'right');
                        line2.Set('chart.hmargin', 15);
                        line2.Set('chart.tickmarks', 'endcircle');
                        line2.Set('chart.linewidth', 2);
                        line2.Set('chart.shadow', true);
                        line2.Set('chart.gutter.top', 5);
                        line2.Set('chart.gutter.right', 100);
                        line2.Set('chart.key', [";

			$fidx = 0;
                        foreach($result as $key => $value) {
                                if($fidx == 0) {
                                        $fidx = 1;
                                        $output .= "'".$key."'";
                                }
                                else
                                        $output .= ",'".$key."'";
                        }

                        $output .= "]);
                        line2.Set('chart.gutter.bottom', 45); ";

                        $output .= "line2.Set('chart.labels', [";
			$output .= "]);";
                        $output .= "line2.Draw();}</script>";

                        mysql_select_db("fssmanager");
			return $output;
		}

		private function loadWeathermaps() {
			$output = "<center><h4>Récapitulatif des bandes passantes physiques</h4>";

			global $rrdtool;
			$rrdtool="/usr/bin/rrdtool";
			$rrdstep=300;
			$filename=dirname(__FILE__)."/../../central/weathermap.conf";

			if (! extension_loaded('gd')) {
				$output .= "PHP GD module is needed for Weathermap4RRD.";
				return;
			}

			global	$link,$nodea,$nodeb,$displayvalue;
			global	$bandwidth,$maxbytesin,$maxbytesout;
			global	$target,$targetin,$targetout,$forcemrtg,$inpos,$outpos,$unit,$coef;
			if (! file_exists($filename)) {
				$output .= "file $filename not found. You should check that $filename file exists.";
				return;
			} else {
				$lines=file($filename);
				$autoscale=0;
				foreach ($lines as $line_num => $line) {

					if (preg_match("/^\s*\bNODE\b\s+(\S+)/i",$line,$out)) {
						$node=$out[1];
					}

					if (preg_match("/^\s*\bIP\b\s+(\d+.\d+.\d+.\d+)/i",$line,$out)) {
						$ip[$node]=$out[1];
					}

					if (preg_match("/^\s*\bLINK\b\s+(\S+)/i",$line,$out)) {
						$link=$out[1];
					}

					if (preg_match("/^\s*\bTARGETIN\b\s+(\S+)/i",$line,$out)) {
						$targetin[$link]=$out[1];
						$target[$link]=$out[1];
					}

					if (preg_match("/^\s*\bTARGETOUT\b\s+(\S+)/i",$line,$out)) {
						$targetout[$link]=$out[1];
					}

					if (preg_match("/^\s*\bTARGET\b\s+(\S+)/i",$line,$out)) {
						$target[$link]=$out[1];
						$targetin[$link]=$target[$link];
						$targetout[$link]=$target[$link];
					}

					if (preg_match("/^\s*\bFORCEMRTG\b\s+(\d+)/i",$line,$out)) {
						$forcemrtg[$link]=$out[1];
					}

					if (preg_match("/^\s*\bBANDWIDTH\b\s+(\d+)/i",$line,$out)) {
						$bandwidth[$link]=$out[1]; # Read in Kbits
						$maxbytesin[$link]=$bandwidth[$link]*1000/8;
						$maxbytesout[$link]=$maxbytesin[$link];
					}

					if (preg_match("/^\s*\bBANDWIDTH\b\s+(\d+)\s+(\d+)/i",$line,$out)) {
						$bandwidthin[$link]=$out[1]; # Read in Kbits
						$bandwidthout[$link]=$out[2]; # Read in Kbits
						$maxbytesin[$link]=$bandwidthin[$link]*1000/8;
						$maxbytesout[$link]=$bandwidthout[$link]*1000/8;
					}

					if (preg_match("/^\s*\bDISPLAYVALUE\b\s+(\d+)/i",$line,$out)) {
						$displayvalue[$link]=$out[1];
					}

					if (preg_match("/^\s*\bUNIT\b\s+(\S+)/i",$line,$out)) {
						$unit[$link]=$out[1];
						if ( $unit[$link] == "Mbits" ) {
							$coef[$link]=1000*1000/8;
						}
						if ( $unit[$link] == "Kbits" ) {
							$coef[$link]=1000/8;
						}
						if ( $unit[$link] == "bits" ) {
							$coef[$link]=1/8;
						}
						if ( $unit[$link] == "Mbytes" ) {
							$coef[$link]=1024*1024;
						}
						if ( $unit[$link] == "Kbytes" ) {
							$coef[$link]=1024;
						}
						if ( $unit[$link] == "bytes" ) {
							$coef[$link]=1;
						}
					}

					if (preg_match("/^\s*\bINPOS\b\s+(\d+)/i",$line,$out)) {
						$inpos[$link]=$out[1];
					}

					if (preg_match("/^\s*\bOUTPOS\b\s+(\d+)/i",$line,$out)) {
						$outpos[$link]=$out[1]; 
					}

					if (preg_match("/^\s*\bNODES\b\s+(\S+)\s+(\S+)/i",$line,$out)) {
						$nodea[$link]=$out[1];
						$nodeb[$link]=$out[2];
						$coef[$link]=1; // By default set coef to bytes value
					}
				}
			}

			if (empty($target)) {
				$output .= "No link has been defined. Unable to generate graph.";
			}
			$output .= "<table><tr style=\"font-size: 13px;\"><th>Lien</th><th>Débit Entrant</th><th>Débit Sortant</th></tr>";
			foreach ($target as $link => $i) {
				if (empty($date) ) {
					$start=rrdtool_last($targetin[$link]);
					$unixtime=$start;
					if (rrdtool_getversion() >= 1.2) { 
						$end=$start;
						$start=$start-$rrdstep; 
					} else
						$end=$start;
				} else {
					$start=$date;
					$end=$date;
				}
				$result=rrdtool_function_fetch($targetin[$link],$start,$end);
				$input[$link]=$result["values"][$inpos[$link]-1][0]*$coef[$link];

				if (empty($date) ) {
					$start=rrdtool_last($targetout[$link]);
					$unixtime=$start;
					if (rrdtool_getversion() >= 1.2) {
						$end=$start;
						$start=$start-$rrdstep;
					} else
						$end=$start;
				} else {
					$start=$date;
					$end=$date;
				}
				$result=rrdtool_function_fetch($targetout[$link],$start,$end);
				$outputbp[$link]=$result["values"][$outpos[$link]-1][0]*$coef[$link];
				if ( (int)(($outputbp[$link]/$maxbytesout[$link]+0.005)*100) > 100 ) {
					$outrate=100;
				} else {
					$outrate=(int)(($outputbp[$link]/$maxbytesout[$link]+0.005)*100);
				}

				if ( (int)(($input[$link]/$maxbytesin[$link]+0.005)*100) > 100 ) {
					$inrate=100;
				} else {
					$inrate=(int)(($input[$link]/$maxbytesin[$link]+0.005)*100);
				}

				if($outputbp[$link] != 0 && $outrate == 0)  $outrate=1; 
				if($input[$link] != 0 && $inrate == 0) $inrate=1;

				if ($outputbp[$link] >=125000) {
                		    $coefdisplay=8/(1024*1024);
				    $unitdisplay="Mbits";
                		} else {
		                    $coefdisplay=8/1024;
                		    $unitdisplay="Kbits";
		                }
                		$todisplay=round($outputbp[$link]*$coefdisplay,1). "$unitdisplay";
				$outputrate=($outputbp[$link]*8/(1024*1024*1024))." ";
				if ($input[$link] >=125000) {
		                     $coefdisplay=8/(1024*1024);
                		     $unitdisplay="Mbits";
		                } else {
                		     $coefdisplay=8/1024;
		                     $unitdisplay="Kbits";
                		}
		                $todisplay2=round($input[$link]*$coefdisplay,1). "$unitdisplay";
				$inputrate=($input[$link]*8/(1024*1024*1024))." ";
				$link=preg_replace("#\-#"," - ",$link);
				$output .= "<tr style=\"font-size: 13px;\" align=\"center\"><td>$link</td>";

				if($inputrate < 0.000001) // < 1Ko
					$output .= "<td style=\"background-color: red;\">$todisplay2</td>";
				else if($inputrate > 0.0025)
					$output .= "<td style=\"background-color: cyan;\">$todisplay2</td>"; 
				else if($inputrate > 0.0055)
					$output .= "<td style=\"background-color: yellow;\">$todisplay2</td>"; 
				else if($inputrate > 0.0070)
					$output .= "<td style=\"background-color: orange;\">$todisplay2</td>"; 
				else if($inputrate > 0.0085)
					$output .= "<td style=\"background-color: red;\">$todisplay2</td>";
				else
					$output .= "<td>$todisplay2</td>";

				if($outputrate < 0.000001) // < 1Ko
					$output .= "<td style=\"background-color: red;\">$todisplay</td>"; 
				else if($outputrate > 0.0025)
		                        $output .= "<td style=\"background-color: cyan;\">$todisplay</td>"; 
                		else if($outputrate > 0.0055)
		                        $output .= "<td style=\"background-color: yellow;\">$todisplay</td>"; 
                		else if($outputrate > 0.0070)
		                        $output .= "<td style=\"background-color: orange;\">$todisplay</td>"; 
                		else if($outputrate > 0.0085)
		                        $output .= "<td style=\"background-color: red;\">$todisplay</td>"; 
                		else
		                        $output .= "<td>$todisplay</td>";
				$output .= "</tr>";
			}
			$output .= "</table>";

			$output .= "<h4>Carte par pile de switches</h4>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/weathermap/weathermap.png",1032,804);
			$output .= "<h4>Carte par réseaux</h4>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/weathermap/weathermapvlan.png",1032,804);
			$output .= "</center>";
			return $output;
		}

		private function loadNagiosMap() {
			$output = "<center>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/cgi-bin/icinga/statusmap.cgi?host=all&createimage&canvas_x=0&canvas_y=0&canvas_width=770&canvas_height=794&max_width=0&max_height=0&layout=5&layermode=exclude",770,794);
			$output .= "</center>";
			return $output;
		}

		private function loadAttackStats() {
			$output = "<h4>Statistiques d'attaque</h4><form action=\"index.php?mod=".$this->mid."&act=1\" method=\"post\">";
			$ech = FS::$secMgr->checkAndSecuriseGetData("ech");
                        if($ech == NULL) $ech = 7;
			$ec = FS::$secMgr->checkAndSecuriseGetData("ec");
			if($ec == NULL) $ec = 365;
			if(!FS::$secMgr->isNumeric($ec)) $ec = 365;
                        $output .= "Pas: ".FS::$iMgr->addNumericInput("ech",$ech,2,2)." jours<br />";
			$output .= "Echelle: ".FS::$iMgr->addNumericInput("ec",$ec,3,3)." jours<br />";
                        $output .= FS::$iMgr->addSubmit("Mise à jour","Mise à jour")."<br />";
                        $output .= "</form><canvas id=\"atkst\" height=\"450\" width=\"1175\"></canvas>";

			$year = date("Y");
	        	$month = date("m");
	        	$day = date("d");

        		$sql_date = $year."-".$month."-".$day." 00:00:00";
			$sql = "select * from attack_stats where atkdate > (SELECT DATE_SUB('".$sql_date."', INTERVAL ".$ec." DAY))";
			mysql_select_db("snort");
			$query = mysql_query($sql);
		        $labels = $scans = $tse = $ssh = "[";
		        $cursor = 0;
		        $subline = false;
		        $temp1 = $temp2 = $temp3 = $temp4 = "";
		        while($data = mysql_fetch_array($query)) {
                		if($cursor == $ech) {
		                        if($subline)
                		                $labels .= "'\\r\\n".$temp1."',";
		                        else
                                		$labels .= "'".$temp1."',";
                		        $scans .= $temp2.",";
		                        $tse .= $temp3.",";
                		        $ssh .= $temp4.",";
		                        $cursor = $temp1 = $temp2 = $temp3 = $temp4 = 0;
		                        $subline = ($subline ? false : true);
		                } else {
		                        $cursor++;
		                        $temp1 = substr($data["atkdate"],8,2)."/".substr($data["atkdate"],5,2);
		                        $temp2 += $data["scans"];
		                        $temp3 += $data["tse"];
		                        $temp4 += $data["ssh"];
		                }
		        }

		        $labels .= "]";
		        $scans .= "]";
		        $tse .= "]";
			$ssh .= "]";
		        $output .= "<script>window.onload = function (){var data = ";

		        $output .= "[".$scans.",".$tse.",".$ssh."];";

		        $output .= "var line = new RGraph.Line(\"atkst\", data);";
		        $output .= "line.Set('chart.yaxispos', 'right');
		        line.Set('chart.hmargin', 15);
		        line.Set('chart.tickmarks', 'endcircle');
		        line.Set('chart.linewidth', 2);
		        line.Set('chart.shadow', true);
		        line.Set('chart.gutter.top', 5);
		        line.Set('chart.gutter.right', 100);
		        line.Set('chart.key', ['Scans', 'Attaques TSE', 'Attaques SSH']);
		        line.Set('chart.gutter.bottom', 45); ";
		        $output .= "line.Set('chart.labels', ".$labels.");";
		        $output .= "line.Draw();}</script>";
			mysql_select_db("fssmanager");
			return $output;
		}

		private function loadDHCPStats() {
			$output = "<h2>Statistiques DHCP</h2>";

			$conn = ssh2_connect("cerbere.iota.u-psud.fr",22);
			if(!$conn) {
				$output .= "Erreur de connexion au serveur";
				return $output;
			}
			if(!ssh2_auth_password($conn, 'miniroot', 'SiCeP&CER')) {
				$output .= "Authentication error for server cerbere";
				return $output;
			}
			$stream = ssh2_exec($conn,"cat /var/lib/dhcp/dhcpd.leases");
			stream_set_blocking($stream, true);
		        $data = "";
        		while ($buf = fread($stream, 4096)) {
	        	    $data .= $buf;
	        	}
			fclose($stream);

			$stream = ssh2_exec($conn,"cat /etc/dhcp/conf.d/*.conf");
        		stream_set_blocking($stream, true);
		        $data2 = "";
        		while ($buf = fread($stream, 4096)) {
		            $data2 .= $buf;
        		}
	        	fclose($stream);

			$data = preg_replace("/\n/","<br />",$data);

			$conn = ssh2_connect("morgoth.iota.u-psud.fr",22);
		        if(!$conn) {
        	        	$output .= "Erreur de connexion au serveur";
        		        return $output;
		        }
        		if(!ssh2_auth_password($conn, 'miniroot', 'SiCeP&MOR')) {
	        	        $output .= "Authentication error for server morgoth";
                		return $output;
	        	}
		        $stream = ssh2_exec($conn,"cat /var/lib/dhcp/dhcpd.leases");
        		stream_set_blocking($stream, true);
		        while ($buf = fread($stream, 4096)) {
        		    $data .= $buf;
	        	}
	        	fclose($stream);
        		$data = preg_replace("#\n#","<br />",$data);

			$leases = preg_split("/lease /",$data);
			$result = array();
			for($i=0;$i<count($leases);$i++) {
				$lease = preg_split("#<br />#",$leases[$i]);
				for($j=0;$j<count($lease);$j++) {
					if(preg_match("#next binding state (.*);#",$lease[$j]))
						continue;

					$must_be_set = array(false,false,false,false,false);
					if(preg_match("#(.*) {#",$lease[$j],$state)) {
                                		$lease_ip = $state[0];
						$lease_ip = preg_replace("# #","",$lease_ip);
						$lease_ip = preg_replace("#{#","",$lease_ip);
        		                }
					else if(preg_match("#binding state (.*);#",$lease[$j],$state)) {
						$lease_state = $state[0];
						$st = preg_split("# #",$lease_state);
                		                $st = preg_replace("#;#","",$st[2]);
						$must_be_set[0]=true;
					}
					else if(preg_match("#hardware ethernet (.*);#",$lease[$j],$state)) {
						$lease_hwaddr = $state[0];
						$hw = preg_split("# #",$lease_hwaddr);
	                	                $hw = preg_replace("#;#","",$hw[2]);
						$must_be_set[1]=true;
					}
					else if(preg_match("#ends [0-9] (.*);#",$lease[$j],$state)) {
        	        	                $lease_end = $state[0];
						$end = preg_split("# #",$lease_end);
                                		$end[3] = preg_replace("#;#","",$end[3]);
						$must_be_set[2]=true;
					}
					else if(preg_match("#starts [0-9] (.*);#",$lease[$j],$state)) {
	                	                $lease_start = $state[0];
						$start = preg_split("# #",$lease_start);
                        	        	$start[3] = preg_replace("#;#","",$start[3]);
						$must_be_set[3]=true;
					}
					else if(preg_match("#client-hostname (.*);#",$lease[$j],$state)) {
                        	        	$lease_hostname = $state[0];
						$hn = preg_split("#\"#",$lease_hostname);
                	                	$hname = $hn[1];
						$must_be_set[4]=true;
					}

					if(isset($lease_ip)) {
						if($lease_ip != "failover" && (isset($st) && $st != "backup" || !isset($st))) {
							if(!isset($result[$lease_ip]["state"]) || $st == "active" 
							|| ($st == "expired" && $result[$lease_ip]["state"] != "active")) {
								if(isset($st) && $must_be_set[0] == true)
									$result[$lease_ip]["state"] = $st;
								if(isset($hw) && $must_be_set[1] == true)
									$result[$lease_ip]["hw"] = $hw;
								if(isset($end) && $must_be_set[2] == true)
									$result[$lease_ip]["end"] = $end[2]." ".$end[3];
								if(isset($start) && $must_be_set[3] == true)
									$result[$lease_ip]["start"] = $start[2]." ".$start[3];
								if(isset($hname) && $must_be_set[4] == true)
									$result[$lease_ip]["hostname"] = $hname;
							}
						}
					}
	//			else
//					$output .= $lease[$j]."<br />";
				}
			}

			$sort_result = array();

			// static leases
			$data2 = preg_replace("/\n/","<br />",$data2);
			$reserv = preg_split("/host /",$data2);
	        	for($i=0;$i<count($reserv);$i++) {
                		$resline = preg_split("#<br />#",$reserv[$i]);
	        	        for($j=0;$j<count($resline);$j++) {
		                        if(preg_match("#(.*){#",$resline[$j],$host)) {
						if(preg_match("#subnet(.*)#",$resline[$j],$subnet)) {
                		                	$subnet = preg_split("# #",$subnet[0]);
							$net = $subnet[1];
							$mask = $subnet[3];
							$ip_split = preg_split("#\.#",$net);
							$sort_result[$ip_split[0]][$ip_split[1]][$ip_split[2]]["net"] = $net;
							$sort_result[$ip_split[0]][$ip_split[1]][$ip_split[2]]["mask"] = $mask;
                		        	}
						else {
		                	                $reserv_host = $host[0];
							$reserv_host = preg_split("# #",$reserv_host);
        		        	        	$reserv_host = preg_replace("#\{#","",$reserv_host);
		                		        $reserv_host = $reserv_host[0];
						}
					}
	                	        else if(preg_match("#hardware ethernet (.*);#",$resline[$j],$hweth)) {
                        		        $reserv_hw = $hweth[0];
						$hw = preg_split("# #",$reserv_hw);
			                        $hw = preg_replace("#;#","",$hw[2]);
					}
                	        	else if(preg_match("#fixed-address (.*);#",$resline[$j],$ipaddr)) {
                		                $reserv_ip = $ipaddr[0];
						$reserv_ip = preg_split("# #",$reserv_ip);
	                        		$reserv_ip = preg_replace("#;#","",$reserv_ip[1]);
					}
					$st = "reserved";

					if(isset($reserv_host) && $reserv_host != "subnet") {
						if(!isset($result_temp[$reserv_host]))
							$result_temp[$reserv_host] = array();

						$result_temp[$reserv_host]["state"] = $st;
	                		        $result_temp[$reserv_host]["hw"] = $hw;
        	        	        	$result_temp[$reserv_host]["end"] = " ";
			                        $result_temp[$reserv_host]["start"] = " ";
						if(isset($reserv_ip))
	        	        	        	$result_temp[$reserv_host]["ip"] = $reserv_ip;
						$result_temp[$reserv_host]["hostname"] = $reserv_host;
					}
	                	}
        		}

			foreach($result_temp as $key => $value) {
				$result[$value["ip"]] = $value;
			}

			foreach ($result as $key => $ipData) {
				if(preg_match("#\.#",$key)) {
					$ip_split = preg_split("#\.#",$key);
					$sort_result[$ip_split[0]][$ip_split[1]][$ip_split[2]][$ip_split[3]] = $ipData;
					ksort($sort_result[$ip_split[0]]);
			        	ksort($sort_result[$ip_split[0]][$ip_split[1]]);
	        			ksort($sort_result[$ip_split[0]][$ip_split[1]][$ip_split[2]]);
				}
			}

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=3");
			$output .= FS::$iMgr->addList("f");
			foreach ($sort_result as $class_a => $ca_keys) {
                                foreach ($ca_keys as $class_b => $cb_keys) {
                                        foreach ($cb_keys as $class_c => $cc_keys) {
						if(isset($cc_keys["net"])) {
							$netw = $cc_keys["net"]."/".$cc_keys["mask"];
							$output .= FS::$iMgr->addElementTolist($netw,$netw,($filter == $cc_keys["net"]."/".$cc_keys["mask"] ? true : false));
						}
					}
				}
			}
			$output .= "</select>";
			$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
			$output .= "</form>";

			foreach ($sort_result as $class_a => $ca_keys) {
				foreach ($ca_keys as $class_b => $cb_keys) {
					if($filter == NULL) $output .= "<h3>Classe B: $class_a.$class_b.0.0/16</h3>";
					foreach ($cb_keys as $class_c => $cc_keys) {
						if(isset($cc_keys["mask"]) && ($filter == NULL || $filter == $cc_keys["net"]."/".$cc_keys["mask"])) {
						$usableaddr = ~(ip2long($cc_keys["mask"]));
						$usableaddr += 4294967295 - 4;
						$output .= "<h4>Classe C: ".$cc_keys["net"]."/".$cc_keys["mask"]."</h4>";
						$output .= "<center><canvas id=\"".$cc_keys["net"]."\" height=\"300\" width=\"450\">HTML5 non support&eacute;</canvas></center>";
						$usedaddr = (count($cc_keys)-2);
						$output .= "<center><table><tr><th>Adresse IP</th><th>Statut</th><th>MAC address</th><th>Nom d'hote</th><th>Fin du bail</th></tr>";
						$used = 0;
						$reserv = 0;
						foreach($cc_keys as $ipKey => $ipData) {
							if($ipKey == "mask" || $ipKey == "net")
								continue;
							if(isset($ipData["state"])) $rstate = $ipData["state"];
                                                               else $rstate = "";
								switch($rstate) {
								case "free":
									$rstate = "Libre";
									$style = "background-color: #BFFFBF;";
									break;
								case "active":
								case "expired":
								case "abandoned":
									$rstate = "Utilis&eacute;";
									$style = "background-color: #FF6A6A;";
									$used++;
									break;
								case "reserved":
									$rstate = "R&eacute;serv&eacute;";
									$style = "background-color: #FFFF80;";
									$reserv++;
									break;
								default:
									$rstate = "";
									$style = "";
									break;
								}

							if($rstate) {
								if(isset($ipData["hw"])) $iwh = $ipData["hw"];
								else $iwh = "";
								if(isset($ipData["hostname"])) $ihost = $ipData["hostname"];
                                                                else $ihost = "";
								if(isset($ipData["end"])) $iend = $ipData["end"];
                                                                else $iend = "";

								$output .= "<tr style=\"$style\"><td><a class=\"monoComponent_li_a\" href=\"index.php?mod=33&node=".$class_a.".".$class_b.".".$class_c.".".$ipKey."\">".$class_a.".".$class_b.".".$class_c.".".$ipKey."</a></td><td>".$rstate."</td><td>";
								$output .= "<a class=\"monoComponent_li_a\" href=\"index.php?mod=33&node=".$iwh."\">".$iwh."</a></td><td>";
								$output .= $ihost."</td><td>";
								$output .= $iend."</td></tr>";
							}
						}

						$output .= "</table></center><br /><hr>";
						$free = $usableaddr - $used - $reserv;
						$output .= "<script>
						{
							var pie3 = new RGraph.Pie('".$cc_keys["net"]."', [".$used.",".$reserv.",".$free."]);
							pie3.Set('chart.key', ['Used (".substr(($used/$usableaddr*100),0,5)."%)', 'Reserved (".substr(($reserv/$usableaddr*100),0,5)."%)', 'Free (".substr(($free/$usableaddr*100),0,5)."%)']);
							pie3.Set('chart.key.interactive', true);
							pie3.Set('chart.colors', ['red', 'yellow', 'green']);
							pie3.Set('chart.shadow', true);
							pie3.Set('chart.shadow.offsetx', 0);
							pie3.Set('chart.shadow.offsety', 0);
							pie3.Set('chart.shadow.blur', 25);
							pie3.Set('chart.strokestyle', 'white');
							pie3.Set('chart.linewidth', 3);
							pie3.Draw();
					       }</script>";
					}}
				}
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$ech = FS::$secMgr->checkAndSecurisePostData("ech");
					if($ech == NULL) $ech = 7;
					$ec = FS::$secMgr->checkAndSecurisePostData("ec");
								if($ec == NULL) $ec = 365;
								if(!FS::$secMgr->isNumeric($ec)) $ec = 365;
					header("Location: index.php?mod=".$this->mid."&s=1&ech=".$ech."&ec=".$ec);
					return;
				case 2:
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
														if($stype == NULL) $stype = 1;
					header("Location: index.php?mod=".$this->mid."&s=".$stype);
														return;
				case 3: 
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) header("Location: index.php?mod".$this->mid."&s=2");
					else header("Location: index.php?mod=".$this->mid."&s=2&f=".$filtr);
					return;
				default: break;
			}
		}
	};
?>
