<?php
	/*
        * Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	
	$snmpro = array();
	$snmprw = array();
	$snmpdbrecord = array();

	function loadNetdiscoCommunities(&$snmpro,&$snmprw) {

		if(Config::getOS() == "FreeBSD")
			$file = file("/usr/local/etc/netdisco/netdisco.conf");
		else if(Config::getOS() == "Debian")
			$file = file("/etc/netdisco/netdisco.conf");
		if(!$file) {
			echo "[".Config::getWebsiteName()."][SNMP-Caching][FATAL] Cannot find/read netdisco.conf !";
			exit(1);
		}

		foreach ($file as $lineNumber => $buf) {
			$buf = trim($buf);
			$buf = str_replace("\t", "", $buf);
			$buf = preg_replace("# #", "", $buf);
			$res = preg_split("#=#",$buf);

			if(count($res) == 2) {
				if(preg_match("#^community$#",$res[0])) {
					$tmpro = preg_replace("# #","",$res[1]);
					$snmpro = preg_split("#[,]#",$tmpro);
				}
				else if(preg_match("#^community_rw$#",$res[0])) {
					$tmprw = preg_replace("# #","",$res[1]);
					$snmprw = preg_split("#[,]#",$tmprw);
				}
			}
		}
	}

	function checkSNMP($device,$community) {
		if(snmpget($device,$community,"snmpSetSerialNo.0") == false)
			return 1;
		return 0;
	}

	function makeSNMPCache($snmpro,$snmprw) {
		$query = FS::$pgdbMgr->Select("device","ip,name");
		while($data = pg_fetch_array($query)) {
			$devro = "";
			$devrw = "";

			$foundro = FS::$pgdbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$data["name"]."'");
			$foundrw = FS::$pgdbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$data["name"]."'");
			if($foundro && checkSnmp($data["ip"],$foundro) == 0)
				$devro = $foundro;
			if($foundrw && checkSnmp($data["ip"],$foundrw) == 0)
				$devrw = $foundrw;

			for($i=0;$i<count($snmpro) && $devro == "";$i++) {
				if(checkSnmp($data["ip"],$snmpro[$i]) == 0)
					$devro = $snmpro[$i];
			}

			for($i=0;$i<count($snmprw) && $devrw == "";$i++) {
				if(checkSnmp($data["ip"],$snmprw[$i]) == 0)
					$devrw = $snmprw[$i];
			}

			if(strlen($devro) > 0 || strlen($devrw) > 0)
				$snmpdbrecord[$data["name"]] = array("ro" => $devro, "rw" => $devrw);
		}
		FS::$pgdbMgr->Delete("z_eye_snmp_cache");
		foreach($snmpdbrecord as $key => $value)
			FS::$pgdbMgr->Insert("z_eye_snmp_cache","device,snmpro,snmprw","'".$key."','".$value["ro"]."','".$value["rw"]."'");
	}

	echo "[".Config::getWebsiteName()."][SNMP-Caching] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);

	loadNetdiscoCommunities($snmpro,$snmprw);
	makeSNMPCache($snmpro,$snmprw);

	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][SNMP-Caching] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
