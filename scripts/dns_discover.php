<?php
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	
	$dhcpdatas = "";
	$dhcpdatas2 = "";
	$DNSServers = "";
	$DNSfound = false;
	$DNSconnerr = false;
	$query = FS::$dbMgr->Select("fss_server_list","addr,login,pwd","dhcp = 1");
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
				
				$stream = ssh2_exec($conn,"grep -Rsh '' /etc/bind/");
				stream_set_blocking($stream, true);
					
				while ($buf = fread($stream, 4096)) {
					$dhcpdatas .= $buf;
				}
				fclose($stream);
	
				//$dhcpdatas = preg_replace("/\n/","<br />",$dhcpdatas);
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
			echo "Aucun serveur DHCP enregistré !\n";
		return;
	}
	
		
	echo "[Z-Mon] DNS Discover done at ".date('d-m-Y G:i:s');

?>