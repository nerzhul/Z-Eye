<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iIPManager extends genModule{
		function iIPManager() { parent::genModule(); }
		public function Load() {
			$output = "";
			$output .= "<div id=\"module_connect\"><h3>Supervision IP</h3>";
			$output .= $this->showStats();
			$output .= "</div>";
			return $output;
		}

		private function showStats() {
			$output = "";
			
			$dhcpdatas = "";
			$dhcpdatas2 = "";
			$DHCPservers = "";
			$DHCPfound = false;
			$DHCPconnerr = false;
			$query = FS::$dbMgr->Select("fss_server_list","addr,login,pwd","dhcp = 1");
			while($data = mysql_fetch_array($query)) {
				
				$conn = ssh2_connect($data["addr"],22);
				if(!$conn) {
					$output .= FS::$iMgr->printError("Erreur de connexion au serveur ".$data["addr"]);
					$DHCPconnerr = true;
				}
				else {
					if(!ssh2_auth_password($conn, $data["login"], $data["pwd"])) {
						$output .= FS::$iMgr->printError("Authentication error for server '".$data["addr"]."' with login '".$data["login"]."'");
						$DHCPconnerr = true;
					}
					else {
						
						$stream = ssh2_exec($conn,"cat /var/lib/dhcp/dhcpd.leases");
						stream_set_blocking($stream, true);
							
						while ($buf = fread($stream, 4096)) {
							$dhcpdatas .= $buf;
						}
						fclose($stream);
			
						$stream = ssh2_exec($conn,"cat /etc/dhcp/conf.d/*.conf");
						stream_set_blocking($stream, true);
						while ($buf = fread($stream, 4096)) {
							$dhcpdatas2 .= $buf;
						}
						fclose($stream);
			
						$dhcpdatas = preg_replace("/\n/","<br />",$dhcpdatas);
						if($DHCPfound == false) $DHCPfound = true;
						else $DHCPservers .= ", ";
						$DHCPservers .= $data["addr"];
					}
				}
			}
			
			if($DHCPfound)
				$output .= "Données collectées sur le(s) serveur(s): ".$DHCPservers."<br /><br />";
			else {
				if($DHCPconnerr == false)
					$output .= FS::$iMgr->printError("Aucun serveur DHCP enregistré !");
				return $output;
			}

			$leases = preg_split("/lease /",$dhcpdatas);
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
			$dhcpdatas2 = preg_replace("/\n/","<br />",$dhcpdatas2);
			$reserv = preg_split("/host /",$dhcpdatas2);
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

			if(count($sort_result) > 0) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
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
				
				$last_ip = "";
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
		
										$output .= "<tr style=\"$style\"><td><a class=\"monoComponent_li_a\" href=\"index.php?mod=33&node=".$class_a.".".$class_b.".".$class_c.".".$ipKey."\">";
										$output .= $class_a.".".$class_b.".".$class_c.".".$ipKey."</a>";
										$output .= "</td><td>".$rstate."</td><td>";
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
							}
						}
					}
				}
			}
			else
				FS::$iMgr->printError("Aucun réseau IP n'a été trouvé dans le(s) serveur(s) DHCP !");

			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) header("Location: index.php?mod".$this->mid."");
					else header("Location: index.php?mod=".$this->mid."&f=".$filtr);
					return;
			}
		}
	};
?>