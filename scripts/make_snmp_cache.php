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
	require_once(dirname(__FILE__)."/../modules/user_modules/switches/snmpdiscovery.api.php");
	
	FS::LoadFSModules();
	
	$snmpro = array();
	$snmprw = array();
	$snmpdbrecord = array();

	function makeSNMPCache($snmpro,$snmprw) {
		$query = FS::$dbMgr->Select("device","ip,name");
		while($data = pg_fetch_array($query)) {
			$devro = "";
			$devrw = "";

			$foundro = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$data["name"]."'");
			$foundrw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$data["name"]."'");
			if($foundro && checkSnmp($data["ip"],$foundro) == 0)
				$devro = $foundro;
			if($foundrw && checkSnmp($data["ip"],$foundrw) == 0)
				$devrw = $foundrw;

			if(strlen($devro) == 0) {
				for($i=0;$i<count($snmpro);$i++) {
					if(checkSnmp($data["ip"],$snmpro[$i]) == 0)
						$devro = $snmpro[$i];
				}
			}

			if(strlen($devrw) == 0) {
				for($i=0;$i<count($snmprw);$i++) {
					if(checkSnmp($data["ip"],$snmprw[$i]) == 0)
						$devrw = $snmprw[$i];
				}
			}

			if(strlen($devro) > 0 || strlen($devrw) > 0)
				$snmpdbrecord[$data["name"]] = array("ro" => $devro, "rw" => $devrw);
		}
		FS::$dbMgr->BeginTr();
		FS::$dbMgr->Delete("z_eye_snmp_cache");
		foreach($snmpdbrecord as $key => $value)
			FS::$dbMgr->Insert("z_eye_snmp_cache","device,snmpro,snmprw","'".$key."','".$value["ro"]."','".$value["rw"]."'");
		FS::$dbMgr->CommitTr();
	}

	echo "[".Config::getWebsiteName()."][SNMP-Caching] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);

	loadNetdiscoCommunities($snmpro,$snmprw);
	makeSNMPCache($snmpro,$snmprw);

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][SNMP-Caching] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
