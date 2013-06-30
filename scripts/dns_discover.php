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
	FS::$dbMgr->Delete("z_eye_dns_zone_record_cache");

	$query = FS::$dbMgr->Select("z_eye_server_list","addr,login,pwd,namedpath,chrootnamed","dns = 1");
	while($data = FS::$dbMgr->Fetch($query)) {
		$conn = ssh2_connect($data["addr"],22);
		if(!$conn) {
			echo "Erreur de connexion au serveur ".$data["addr"]."\n";
			$DNSconnerr = true;
		}
		else {
			if(!ssh2_auth_password($conn, $data["login"], $data["pwd"])) {
				echo "Authentication error for server '".$data["addr"]."' with login '".$data["login"]."'\n";
				$DNSconnerr = true;
			}
			else {
				$dns_stream_datas = bufferizeDNSFiles($conn,$data["namedpath"],$data["chrootnamed"],0);
				if($DNSfound == false) $DNSfound = true;
				else $DNSServers .= ", ";
				$DNSServers .= $data["addr"];
				$zones = preg_split("/zone /",$dns_stream_datas);
				for($i=0;$i<count($zones);$i++) {
					$zone = preg_split("#\n#",$zones[$i]);
					$zonename = "";
					$zonetype = 0;
					$zonefile = "";
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
								case "hint": default: $zonetype = 0; break;
							}
						}
						else if(preg_match("#file \"(.*)\";#",$zone[$j],$zfile)) {
							$zonefile = $zfile[1];
							$zonefile = preg_replace("#;#","",$zonefile);
						}
					}
					if(strlen($zonename) > 0 && $zonetype > 0 && strlen($zonefile) > 0) {
						if($zonename[strlen($zonename)-1] == ".")
							$zonename = substr($zonename,0,strlen($zonename)-1);
						if(!FS::$dbMgr->GetOneData("z_eye_dns_zone_cache","zonename","zonename = '".$zonename."'"))
							FS::$dbMgr->Insert("z_eye_dns_zone_cache","zonename, zonetype","'".$zonename."','".$zonetype."'");
						$zonebuffer = bufferizeDNSFiles($conn,$zonefile,$data["chrootnamed"]);
						$zonebuffer = preg_replace("#[\t]#"," ",trim($zonebuffer));
						$zonebuffer = preg_replace("#[ ]{2,}#"," ",trim($zonebuffer));
							
						$buflines = preg_split("#\n#",$zonebuffer);
						
						$currecord = "";
						$recsuffix = "";
						for($j=0;$j<count($buflines);$j++) {
							$record = preg_split("#[ ]#",$buflines[$j]);
							if(count($record) < 2 || $record[0] == ";" || $record[0] == "#")
								continue;
							if(count($record) == 3) {
								if(strlen($record[0]) > 0)
									$currecord = $record[0];
								FS::$dbMgr->Insert("z_eye_dns_zone_record_cache","zonename,record,rectype,recval,server","'".$zonename."','".(strlen($recsuffix) > 0 ? $recsuffix.".":"").$currecord."','".$record[1]."','".$record[2]."','".$data["addr"]."'");
							}
							else if(count($record) == 2) {
								/*if(preg_match('#\$ORIGIN#',$record[0]) && $record[1] != $zonename)
									$recsuffix = substr($record[1],0,strlen($record[1])-1);*/
							} 
							else if($record[1] == "SRV") {
								$tmprec = "";
								for($k=2;$k<count($record);$k++) {
									$tmprec .= $record[$k];
									if($k != count($record) -1)
										$tmprec .= " ";
								}
								FS::$dbMgr->Insert("z_eye_dns_zone_record_cache","zonename,record,rectype,recval,server","'".$zonename."','".$currecord."','".$record[1]."','".$tmprec."','".$data["addr"]."'");
							}
						}
					}
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
