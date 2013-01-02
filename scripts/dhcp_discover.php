<?php
	/*
        * Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	require_once(dirname(__FILE__)."/../lib/FSS/modules/Network.FS.class.php");

	function bufferizeDHCPFiles($conn,$file) {
		$tmpbuf = "";
		// show file but remove comments
		$stream = ssh2_exec($conn,"cat ".$file."");
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
	
	/*
	 * Reading current leases
	 */
	
	function readLeases($buffer,$server,&$hosts_list) {
		// dynamic leases
		// split on lease keyword
		$leases = preg_split("/lease /",$buffer);
		
		for($i=0;$i<count($leases);$i++) {
			$lease = preg_split("#[\n]#",$leases[$i]);
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
						if(!isset($hosts_list[$lease_ip]["state"]) || $st == "active" 
						|| ($st == "expired" && $hosts_list[$lease_ip]["state"] != "active")) {
							if(isset($st) && $must_be_set[0] == true)
								$hosts_list[$lease_ip]["state"] = $st;
							if(isset($hw) && $must_be_set[1] == true)
								$hosts_list[$lease_ip]["hw"] = $hw;
							if(isset($end) && $must_be_set[2] == true)
								$hosts_list[$lease_ip]["end"] = $end[2]." ".$end[3];
							if(isset($start) && $must_be_set[3] == true)
								$hosts_list[$lease_ip]["start"] = $start[2]." ".$start[3];
							if(isset($hname) && $must_be_set[4] == true)
								$hosts_list[$lease_ip]["hostname"] = $hname;
							$hosts_list[$lease_ip]["ip"] = $lease_ip;
							$hosts_list[$lease_ip]["server"] = $server;
						}
					}
				}
			}
		}
	}
	
	/*
	 * Reading static leases
	 */
	
	function readReserv($buffer,$server,&$subnet_list,&$hosts_list) {
		$tmphosts_list = array();
		// static leases
		// split each line
		$resline = preg_split("#[\n]#",$buffer);
		for($j=0;$j<count($resline);$j++) {
			// pseudo trim
			$resline[$j] = preg_replace("#^([ ])+#","",$resline[$j]);
			$resline[$j] = preg_replace("#^([\t])+#","",$resline[$j]);

			if(preg_match("#range (.+) (.+);#",$resline[$j],$range)) {
				for($i=ip2long($range[1]);$i<=ip2long($range[2]);$i++) {
					if(!isset($hosts_list[long2ip($i)]))
		                                $hosts_list[long2ip($i)] = array();
					if(!isset($hosts_list[long2ip($i)]["state"]))
						$hosts_list[long2ip($i)]["state"] = "distributed";
					else if(isset($hosts_list[long2ip($i)]["end"]) && $hosts_list[long2ip($i)]["end"] < date("Y/m/d"))
						$hosts_list[long2ip($i)]["state"] = "distributed";
					$hosts_list[long2ip($i)]["server"] = $server;
				}
			}

			if(preg_match("#(.*){#",$resline[$j],$host)) {
				if(preg_match("#subnet(.*)#",$resline[$j],$subnet)) {
					$subnet = preg_split("# #",$subnet[0]);
					$net = $subnet[1];
					$mask = $subnet[3];
					array_push($subnet_list,array($net,$mask));
				}
				else if(preg_match("#host(.*)#",$resline[$j],$reserv_host)){
					$reserv_host = preg_split("# #",$reserv_host[0]);
					$reserv_host = preg_replace("#\{#","",$reserv_host);
					$reserv_host = $reserv_host[1];
				}
				else
					$reserv_host = "";
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

			if(isset($reserv_host) && $reserv_host != "") {
				if(!isset($tmphosts_list[$reserv_host]))
					$tmphosts_list[$reserv_host] = array();

				$tmphosts_list[$reserv_host]["state"] = "reserved";
				if(isset($hw))
					$tmphosts_list[$reserv_host]["hw"] = $hw;
				$tmphosts_list[$reserv_host]["end"] = " ";
				$tmphosts_list[$reserv_host]["start"] = " ";
				if(isset($reserv_ip))
					$tmphosts_list[$reserv_host]["ip"] = $reserv_ip;
			}
		}
		foreach($tmphosts_list as $key => $value) {
			if(!isset($value["ip"]))
				continue;
			if(!isset($hosts_list[$value["ip"]]))
				$hosts_list[$value["ip"]] = array();
			$hosts_list[$value["ip"]]["ip"] = $value["ip"];

			$hosts_list[$value["ip"]]["state"] = "reserved";
			$hosts_list[$value["ip"]]["hw"] = $value["hw"];
			$hosts_list[$value["ip"]]["end"] = " ";
			$hosts_list[$value["ip"]]["start"] = " ";
			$hosts_list[$value["ip"]]["hostname"] = $key;
			$hosts_list[$value["ip"]]["server"] = $server;
		}
	}
	
	/*
	 * Registering IPs and link it to radius server
	 */
	
	function registerIPs($hosts_list,&$subnet_list,$server) {
		global $execdate;
		// Flush ip table for server
		FS::$pgdbMgr->Delete("z_eye_dhcp_ip_cache","server = '".$server."'");
		foreach($hosts_list as $host => $value) {

			if(isset($value["state"])) $rstate = $value["state"];
			else $rstate = 0;

			switch($rstate) {
				case "free":
					$rstate = 1;
					break;
				case "active":
				case "expired":
				case "abandoned":
					$rstate = 2;
					break;
				case "reserved":
					$rstate = 3;
					break;
				case "distributed":
					$rstate = 4;
					break;
				default:
					$rstate = 0;
					break;
			}

			if($rstate) {
				if(isset($value["hw"])) $iwh = $value["hw"];
				else $iwh = "";

				if(isset($value["hostname"])) $ihost = $value["hostname"];
				else $ihost = "";

				if(isset($value["end"])) $iend = $value["end"];
				else $iend = "";

				$netfound = "";
				if($host) {
					for($i=0;$i<count($subnet_list)&&$netfound==false;$i++) {
						$netclass = new FSNetwork();
						$netclass->setNetAddr($subnet_list[$i][0]);
						$netclass->setNetMask($subnet_list[$i][1]);
						if($netclass->isUsableIP($host))
							$netfound = $subnet_list[$i][0];
					}

					FS::$pgdbMgr->Insert("z_eye_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,netid,server","'".$host."','".$iwh."','".$ihost."','".$iend."','".$rstate."','".$netfound."','".$value["server"]."'");
					if($rstate == 2 || $rstate == 3 || $rstate == 4)
						FS::$pgdbMgr->Insert("z_eye_dhcp_ip_history","ip,mac,distributed,netid,server,collecteddate","'".$host."','".$iwh."','".$rstate."','".$netfound."','".$value["server"]."','".$execdate."'::timestamp");
					if($rstate == 3) {
						$macaddr = strtolower(preg_replace("#[:]#","",$iwh));
						$query = FS::$pgdbMgr->Select("z_eye_radius_dhcp_import","dbname,addr,port,groupname","dhcpsubnet ='".$netfound."'");
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

	FS::LoadFSModules();

	echo "[".Config::getWebsiteName()."][DHCP-Sync] started at ".date('d-m-Y G:i:s')."\n";
        $start_time = microtime(true);

	$execdate = date("Y-m-d G:i:00");

	$subnet_list = array();

	$dhcpdatas2 = "";
	$DHCPfound = false;
	$DHCPconnerr = false;
	$query = FS::$pgdbMgr->Select("z_eye_dhcp_servers","addr,sshuser,sshpwd,dhcpdpath,leasespath");
	while($data = pg_fetch_array($query)) {
		$dhcpdatas = "";
		if($data["dhcpdpath"] == NULL || $data["dhcpdpath"] == "" || !FS::$secMgr->isPath($data["dhcpdpath"])) {
			echo "Chemin dhcpd.conf invalide pour le serveur ".$data["addr"].": ".$data["dhcpdpath"]."\n";
			$DHCPconnerr = true;
			continue;
		}

		if($data["leasespath"] == NULL || $data["leasespath"] == "" || !FS::$secMgr->isPath($data["leasespath"])) {
				echo "Chemin dhcpd.conf invalide pour le serveur ".$data["addr"].": ".$data["leasespath"]."\n";
				$DHCPconnerr = true;
				continue;
		}
		$conn = ssh2_connect($data["addr"],22);
		if(!$conn) {
			echo "Erreur de connexion au serveur ".$data["addr"]."\n";
			$DHCPconnerr = true;
		}
		else {
			if(!ssh2_auth_password($conn, $data["sshuser"], $data["sshpwd"])) {
				echo "Authentication error for server '".$data["addr"]."' with login '".$data["sshuser"]."'\n";
				$DHCPconnerr = true;
			}
			else {
				echo "Collecte des données sur le serveur ".$data["addr"]."\n";
				$stream = ssh2_exec($conn,"cat ".$data["leasespath"]);
				stream_set_blocking($stream, true);

				while ($buf = fread($stream, 4096)) {
					$dhcpdatas .= $buf;
				}
				fclose($stream);

				$dhcpdatas2 = bufferizeDHCPFiles($conn,$data["dhcpdpath"]);
				
				$hosts_list = array();

				readLeases($dhcpdatas,$data["addr"],$hosts_list);
				readReserv($dhcpdatas2,$data["addr"],$subnet_list,$hosts_list);
				registerIPs($hosts_list,$subnet_list,$data["addr"]);
				
				if($DHCPfound == false) $DHCPfound = true;
			}
		}
	}

	if(!$DHCPfound && $DHCPconnerr == false)
		echo "Aucun serveur DHCP enregistré !\n";

	// Flush subnet table
	FS::$pgdbMgr->Delete("z_eye_dhcp_subnet_cache");

	// Register subnets
	for($i=0;$i<count($subnet_list);$i++)
		FS::$pgdbMgr->Insert("z_eye_dhcp_subnet_cache","netid,netmask","'".$subnet_list[$i][0]."','".$subnet_list[$i][1]."'");

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][DHCP-Sync] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
