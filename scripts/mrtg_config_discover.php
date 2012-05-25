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
	
	FS::LoadFSModules();
	
	echo "[".Config::getWebsiteName()."][MRTG-Discover] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	$query = FS::$pgdbMgr->Select("device","ip,name");
	while($data = pg_fetch_array($query)) {
		$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$data["name"]."'");
		if($community == NULL) $community = SNMPConfig::$SNMPReadCommunity;

		echo "[".Config::getWebsiteName()."][MRTG-Discover] Do: cfgmaker ".$community."@".$data["ip"]."\n";
		$out = "";
		exec("cfgmaker ".$community."@".$data["ip"],$out);
		$file = fopen(dirname(__FILE__)."/../datas/mrtg-config/mrtg-".$data["name"].".cfg","w+");
		if(!$file) {
			echo "[".Config::getWebsiteName()."][MRTG-Discover][FATAL] Cannot write file to ".dirname(__FILE__)."/../datas/mrtg-config/ !";
			exit(1);
		}
		$wkdirwr = 0;
		echo "[".Config::getWebsiteName()."][MRTG-Discover] Do: write ".dirname(__FILE__)."/../datas/mrtg-config/mrtg-".$data["name"].".cfg\n";
		for($i=0;$i<count($out);$i++) {
			if(preg_match("#WorkDir:#",$out[$i])) {
				if($wkdirwr == 0) {
					$towr = "WorkDir: ".dirname(__FILE__)."/../datas/rrd\n";
					$wkdirwr = 1;
				}
			}
			else
				$towr = $out[$i];
				
			fwrite($file,$towr."\n");
		}
		fclose($file);
	}
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][MRTG-Discover] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";
?>