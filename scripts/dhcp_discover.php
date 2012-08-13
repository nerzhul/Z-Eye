<?php
	/*
        * Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
        * Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
        *
        * This program is free software; you can redistribute it and/or modify
        * it under the terms of the GNU General Public License as published by
        * the Free Software Foundation; either version 2 of the License, or
        * (at your option) any later version.
        *
        * This program is distributed in the hope that it will be useful,
        * but WITHOUT ANY WARRANTY; without even the implied warranty of
        * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
        * GNU General Public License for more details.
        *
        * You should have received a copy of the GNU General Public License
        * along with this program; if not, write to the Free Software
        * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
        */
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	function bufferizeDHCPFiles($conn,$file) {
		$tmpbuf = "";
		$stream = ssh2_exec($conn,"cat ".$file);
		stream_set_blocking($stream, true);
		while ($buf = fread($stream, 4096)) {
			$inc_path = array();
			preg_match_all("#include \"(.*)\"#",$buf,$inc_path);
			if(count($inc_path[1]) > 0) {
				for($i=0;$i<count($inc_path[1]);$i++)
					$tmpbuf .= bufferizeDHCPFiles($conn,$inc_path[1][$i]);
			}
			else
				$tmpbuf .= $buf;
		}
		return $tmpbuf;
	}
	
	FS::LoadFSModules();

	echo "[".Config::getWebsiteName()."][DHCP-Sync] started at ".date('d-m-Y G:i:s')."\n";
        $start_time = microtime(true);

	$dhcpdatas = "";
	$dhcpdatas2 = "";
	$DHCPservers = "";
	$DHCPfound = false;
	$DHCPconnerr = false;
	$query = FS::$pgdbMgr->Select("z_eye_server_list","addr,login,pwd,dhcpdpath,dhcpleasepath","dhcp = 1");
	while($data = pg_fetch_array($query)) {
		if($data["dhcpdpath"] == NULL || $data["dhcpdpath"] == "" || !FS::$secMgr->isPath($data["dhcpdpath"])) {
			echo "Chemin dhcpd.conf invalide pour le serveur ".$data["addr"].": ".$data["dhcpdpath"]."\n";
			$DHCPconnerr = true;
			continue;
		}

		if($data["dhcpleasepath"] == NULL || $data["dhcpleasepath"] == "" || !FS::$secMgr->isPath($data["dhcpleasepath"])) {
                        echo "Chemin dhcpd.conf invalide pour le serveur ".$data["addr"].": ".$data["dhcpleasepath"]."\n";
                        $DHCPconnerr = true;
                        continue;
                }
		$conn = ssh2_connect($data["addr"],22);
		if(!$conn) {
			echo "Erreur de connexion au serveur ".$data["addr"]."\n";
			$DHCPconnerr = true;
		}
		else {
			if(!ssh2_auth_password($conn, $data["login"], $data["pwd"])) {
				echo "Authentication error for server '".$data["addr"]."' with login '".$data["login"]."'\n";
				$DHCPconnerr = true;
			}
			else {
				
				$stream = ssh2_exec($conn,"cat ".$data["dhcpleasepath"]);
				stream_set_blocking($stream, true);
					
				while ($buf = fread($stream, 4096)) {
					$dhcpdatas .= $buf;
				}
				fclose($stream);
	
				$dhcpdatas2 = bufferizeDHCPFiles($conn,$data["dhcpdpath"]);
	
				$dhcpdatas = preg_replace("/\n/","<br />",$dhcpdatas);
				if($DHCPfound == false) $DHCPfound = true;
				else $DHCPservers .= ", ";
				$DHCPservers .= $data["addr"];
			}
		}
	}
	
	if($DHCPfound)
		echo "Données collectées sur le(s) serveur(s): ".$DHCPservers."\n";
	else {
		if($DHCPconnerr == false)
			echo "Aucun serveur DHCP enregistré !\n";
		return;
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
		if(isset($value["ip"]))
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

	// Flush subnet table
	FS::$pgdbMgr->Delete("z_eye_dhcp_subnet_cache");
	
	if(count($sort_result) > 0) {
		foreach ($sort_result as $class_a => $ca_keys) {
			foreach ($ca_keys as $class_b => $cb_keys) {
				foreach ($cb_keys as $class_c => $cc_keys) {
					if(isset($cc_keys["net"])) {
						$netw = $cc_keys["net"]."/".$cc_keys["mask"];
						// Ecrire en base
						FS::$pgdbMgr->Insert("z_eye_dhcp_subnet_cache","netid,netmask","'".$cc_keys["net"]."','".$cc_keys["mask"]."'");
					}
				}
			}
		}
		
		// Flush ip table
		FS::$pgdbMgr->Delete("z_eye_dhcp_ip_cache");
		
		foreach ($sort_result as $class_a => $ca_keys) {
			foreach ($ca_keys as $class_b => $cb_keys) {
				foreach ($cb_keys as $class_c => $cc_keys) {
					if(isset($cc_keys["mask"])) {
						$usableaddr = ~(ip2long($cc_keys["mask"]));
						$usableaddr += 4294967295 - 4;
						$usedaddr = (count($cc_keys)-2);
						$used = 0;
						$reserv = 0;
						foreach($cc_keys as $ipKey => $ipData) {
							if($ipKey == "mask" || $ipKey == "net")
								continue;
							if(isset($ipData["state"])) $rstate = $ipData["state"];
							else $rstate = 0;
							switch($rstate) {
							case "free":
								$rstate = 1;
								break;
							case "active":
							case "expired":
							case "abandoned":
								$rstate = 2;
								$used++;
								break;
							case "reserved":
								$rstate = 3;
								$reserv++;
								break;
							default:
								$rstate = 0;
								break;
							}

							if($rstate) {
								if(isset($ipData["hw"])) $iwh = $ipData["hw"];
								else $iwh = "";
								
								if(isset($ipData["hostname"])) $ihost = $ipData["hostname"];
								else $ihost = "";
								if(isset($ipData["end"])) $iend = $ipData["end"];
								else $iend = "";

								FS::$pgdbMgr->Insert("z_eye_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,netid","'".$class_a.".".$class_b.".".$class_c.".".$ipKey."','".$iwh."','".$ihost."','".$iend."','".$rstate."','".$cc_keys["net"]."'");
								if($rstate == 3) {
									$macaddr = strtolower(preg_replace("#[:]#","",$iwh));
									$query = FS::$pgdbMgr->Select("z_eye_radius_dhcp_import","dbname,addr,port,groupname","dhcpsubnet ='".$cc_keys["net"]."'");
									if($data = pg_fetch_array($query)) {
										$radhost = $data["addr"];
										$radport = $data["port"];
										$raddb = $data["dbname"];
										$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        					$radSQLMgr = new FSMySQLMgr();
					                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
					                                        $radSQLMgr->Connect();
										if(!$radSQLMgr->GetOneData("radusergroup","username","username = '".$macaddr."' AND groupname = '".$data["groupname"]."'"))
											$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$macaddr."','".$data["groupname"]."','0'");
										if(!$radSQLMgr->GetOneData("radcheck","username","username = '".$macaddr."' AND attribute = 'Auth-Type' AND op = ':=' AND value = 'Accept'"))
											$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$macaddr."','Auth-Type',':=','Accept'");
									}
								}
							}
						}
					}
				}
			}
		}
	}
	else
		echo "Aucun réseau IP n'a été trouvé dans le(s) serveur(s) DHCP !\n";

	$end_time = microtime(true);
        $script_time = $end_time - $start_time;
        echo "[".Config::getWebsiteName()."][DHCP-Sync] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
