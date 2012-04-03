<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");

	$dns_stream_datas = "";
	$dhcpdatas2 = "";
	$DNSServers = "";
	$DNSfound = false;
	$DNSconnerr = false;
	$conn = NULL;

	function bufferizeDNSFiles($conn,$file) {
		$tmpbuf = "";
		$stream = ssh2_exec($conn,"cat ".$file);
		stream_set_blocking($stream, true);
		while ($buf = fread($stream, 4096)) {
			$inc_path = array();
			preg_match_all("#include \"(.*)\"#",$buf,$inc_path);
			if(count($inc_path[1]) > 0) {
				for($i=0;$i<count($inc_path[1]);$i++)
					$tmpbuf .= bufferizeDNSFiles($conn,$inc_path[1][$i]);
			}
			else
				$tmpbuf .= $buf;
		}
		return $tmpbuf;
	}
	
	FS::LoadFSModules();
	
	$query = FS::$dbMgr->Select("fss_server_list","addr,login,pwd","dns = 1");
	while($data = mysql_fetch_array($query)) {
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
				$dns_stream_datas = bufferizeDNSFiles($conn,"/etc/bind/named.conf");
				if($DNSfound == false) $DNSfound = true;
				else $DNSServers .= ", ";
				$DNSServers .= $data["addr"];
			}
		}
	}
	
	if($DNSfound)
		echo "Données collectées sur le(s) serveur(s): ".$DNSServers."\n";
	else {
		if($DNSconnerr == false)
			echo "Aucun serveur DNS enregistré !\n";
		return;
	}
	
	FS::$dbMgr->Delete("fss_dns_zone_cache");
	FS::$dbMgr->Delete("fss_dns_zone_record_cache");
	
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
				$zonename[strlen($zonename)-1] = "";
				
			FS::$dbMgr->Insert("fss_dns_zone_cache","zonename, zonetype","'".$zonename."','".$zonetype."'");
			$zonebuffer = bufferizeDNSFiles($conn,$zonefile);
			$zonebuffer = preg_replace("#[\t]#"," ",trim($zonebuffer));
			$zonebuffer = preg_replace("#[ ]{2,}#"," ",trim($zonebuffer));
				
			$buflines = preg_split("#\n#",$zonebuffer);
			
			$currecord = "";
			$recsuffix = "";
			for($j=0;$j<count($buflines);$j++) {
				$record = preg_split("#[ ]#",$buflines[$j]);
				if($record[0] == ";" || $record[0] == "#")
					continue;
				if(count($record) == 3) {
					if(strlen($record[0]) > 0)
						$currecord = $record[0];
					FS::$dbMgr->Insert("fss_dns_zone_record_cache","zonename,record,rectype,recval","'".$zonename."','".(strlen($recsuffix) > 0 ? $recsuffix.".":"").$currecord."','".$record[1]."','".$record[2]."'");
				}
				else if(count($record) == 2) {
					if(preg_match('#\$ORIGIN#',$record[0]))
						$recsuffix = substr($record[1],0,strlen($record[1])-2);
				}
			}
		}
	}
		
	echo "[".Config::getWebsiteName()."] DNS Discover done at ".date('d-m-Y G:i:s');

?>
