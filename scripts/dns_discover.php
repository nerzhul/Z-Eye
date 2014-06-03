<?php
	/*
        * Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	$dns_stream_datas = "";
	$dhcpdatas2 = "";
	$DNSServers = "";
	$DNSfound = false;
	$DNSconnerr = false;
	$conn = NULL;

	function bufferizeDNSFiles($conn,$file,$chrootpath,$chroot=1) {
		$tmpbuf = "";
		$stream = ssh2_exec($conn,"cat ".($chroot ? $chrootpath."/" : "").$file);
		stream_set_blocking($stream, true);
		while ($buf = fread($stream, 4096)) {
			$inc_path = array();
			preg_match_all("#include \"(.*)\"#",$buf,$inc_path);
			if(count($inc_path[1]) > 0) {
				for($i=0;$i<count($inc_path[1]);$i++) {
					$tmpbuf .= bufferizeDNSFiles($conn,$inc_path[1][$i],$chrootpath);
				}
			}
			else
				$tmpbuf .= $buf;
		}
		return $tmpbuf;
	}

	FS::LoadFSModules();

	FS::$dbMgr->BeginTr();
	FS::$dbMgr->Delete("z_eye_dns_zone_cache");

	$query = FS::$dbMgr->Select("z_eye_dns_servers","addr,sshuser,sshpwd,namedpath,chrootpath","");
	while($data = FS::$dbMgr->Fetch($query)) {
		$conn = ssh2_connect($data["addr"],22);
		if(!$conn) {
			echo "Erreur de connexion au serveur ".$data["addr"]."\n";
			$DNSconnerr = true;
		}
		else {
			if(!ssh2_auth_password($conn, $data["sshuser"], $data["sshpwd"])) {
				echo "Authentication error for server '".$data["addr"]."' with login '".$data["sshuser"]."'\n";
				$DNSconnerr = true;
			}
			else {
				$dns_stream_datas = bufferizeDNSFiles($conn,$data["namedpath"],$data["chrootpath"],0);
				if($DNSfound == false) $DNSfound = true;
				else $DNSServers .= ", ";
				$DNSServers .= $data["addr"];
				$zones = preg_split("/zone /",$dns_stream_datas);

				for($i=0;$i<count($zones);$i++) {
					$zone = preg_split("#\n#",$zones[$i]);
					$zonename = "";
					$zonetype = 0;
					for($j=0;$j<count($zone);$j++) {
						if(preg_match("#\"(.*)\" (IN)*[ ]*{#",$zone[$j],$zname)) {
							$zonename = $zname[1];
							$zonename = preg_replace("#{#","",$zonename);
						}
						else if(preg_match("#type (.*);#",$zone[$j],$ztype)) {
							$zonetype = $ztype[1];
							$zonetype = preg_replace("#;#","",$zonetype);
							switch($zonetype) {
								case "master": $zonetype = 1; break;
								case "slave": $zonetype = 2; break;
								case "forward": $zonetype = 3; break;
								case "hint": $zonetype = 0; break;
								default: $zonetype = -1; break;
							}
						}
					}
					if($zonetype >= 0 && $zonename && !FS::$dbMgr->GetOneData("z_eye_dns_zone_cache","zonename","zonename = '".$zonename."' AND server = '".$data["addr"]."'"))
						FS::$dbMgr->Insert("z_eye_dns_zone_cache","zonename,zonetype,server","'".$zonename."','".$zonetype."','".$data["addr"]."'");
				}
			}
		}
	}
	
	FS::$dbMgr->CommitTr();
	
	if($DNSfound)
		echo "Données collectées sur le(s) serveur(s): ".$DNSServers."\n";
	else {
		if($DNSconnerr == false)
			echo "Aucun serveur DNS enregistré !\n";
		return;
	}
	
	echo "[".Config::getWebsiteName()."] DNS Discover done at ".date('d-m-Y G:i:s')."\n";
	FS::UnloadFSModules();
?>
