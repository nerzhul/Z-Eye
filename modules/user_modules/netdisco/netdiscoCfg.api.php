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
        
        function readNetdiscoConf() {
			$file = file("/usr/local/etc/netdisco/netdisco.conf");

			$netdiscoCfg = array();
			$netdiscoCfg["dnssuffix"] = ".local";
			$netdiscoCfg["dir"] = "/usr/local/share/netdisco/";
			$netdiscoCfg["nodetimeout"] = 60;
			$netdiscoCfg["devicetimeout"] = 90;
			$netdiscoCfg["pghost"] = "127.0.0.1";
			$netdiscoCfg["dbname"] = "netdiscodb";
			$netdiscoCfg["dbuser"] = "netdiscouser";
			$netdiscoCfg["dbpwd"] = "password";
			$netdiscoCfg["snmptimeout"] = 1;
			$netdiscoCfg["snmptry"] = 3;
			$netdiscoCfg["snmpmibs"] = "/usr/local/share/netdisco-mibs/";

			if(!$file)
				return FS::$iMgr->printError($this->loc->s("err-unable-read")." /usr/local/etc/netdisco/netdisco.conf");

			foreach ($file as $lineNumber => $buf) {
				$buf = trim($buf);
				$buf = str_replace("\t", "", $buf);
				$buf = preg_replace("# #", "", $buf);
				$res = preg_split("#=#",$buf);

				if(count($res) == 2) {
					if(preg_match("#^domain$#",$res[0]))
						$netdiscoCfg["dnssuffix"] = $res[1];
					else if(preg_match("#^home$#",$res[0]))
						$netdiscoCfg["dir"] = $res[1];
					else if(preg_match("#^expire_nodes$#",$res[0]))
						$netdiscoCfg["nodetimeout"] = $res[1];
					else if(preg_match("#^expire_devices$#",$res[0]))
						$netdiscoCfg["devicetimeout"] = $res[1];
					else if(preg_match("#^db_Pg_user$#",$res[0]))
						$netdiscoCfg["dbuser"] = $res[1];
					else if(preg_match("#^db_Pg_pw$#",$res[0]))
						$netdiscoCfg["dbpwd"] = $res[1];
					else if(preg_match("#^snmptimeout$#",$res[0]))
						$netdiscoCfg["snmptimeout"] = $res[1]/1000000;
					else if(preg_match("#^snmpretries$#",$res[0]))
						$netdiscoCfg["snmptry"] = $res[1];
					else if(preg_match("#^mibhome$#",$res[0]))
						$netdiscoCfg["snmpmibs"] = $res[1];
					else if(preg_match("#^snmpver$#",$res[0]))
						$netdiscoCfg["snmpver"] = $res[1];
				} else if(count($res) == 4) {
					if(preg_match("#^db_Pg$#",$res[0])) {
						$netdiscoCfg["pghost"] = $res[3];
						$nsplit = preg_split("#;#",$res[2]);
						$netdiscoCfg["dbname"] = $nsplit[0];
					}
				}
			}
			fclose($file);
			$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","r");
			$netdiscoCfg["firstnode"] = fread($file,filesize("/usr/local/etc/netdisco/netdisco-topology.txt"));
			fclose($file);
			return $netdiscoCfg;
		}
		
		function checkNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$firstnode) {
			if(!FS::$secMgr->isNumeric($nodetimeout) || !FS::$secMgr->isNumeric($devicetimeout) || !FS::$secMgr->isNumeric($snmptimeout) ||
				!FS::$secMgr->isNumeric($snmptry) || !FS::$secMgr->isNumeric($snmpver))
				return false;
			if($dns == "" || $pghost == "" || $dbname == "" || $dbuser == "" || $dbpwd == "" || 
				$firstnode == "")
				return false;
			if($nodetimeout > 3600 || $nodetimeout < 10 || $devicetimeout > 3600 || $devicetimeout < 10 || $snmptimeout > 30 || $snmptimeout < 1 || $snmptry > 10 ||
				$snmptry < 1 || ($snmpver != 1 && $snmpver != 2))
				return false;
			return true;
		}
		
		function writeNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$firstnode) {
			$file = fopen("/usr/local/etc/netdisco/netdisco.conf","w+");
			if(!$file)
				return 1;
			fwrite($file,"# ---- General Settings ----\n");
			fwrite($file,"domain = ".$dns."\n");
			fwrite($file,"home = /usr/local/share/netdisco\n");
			fwrite($file,"topofile = /usr/local/etc/netdisco/netdisco-topology.txt\n");
			
			fwrite($file,"timeout = 90\nmacsuck_timeout = 90\nmacsuck_all_vlans = true\n");
			fwrite($file,"arpnip          = true\n");
			fwrite($file,"\n# -- Database Maintenance and Data Removal --\nexpire_devices = ".$devicetimeout."expire_nodes = ".$nodetimeout."\n");
			fwrite($file,"expire_nodes_archive = 60\n");
			fwrite($file,"\n# ---- Admin Panel Daemon Settings ----\ndaemon_bg       = true\ndaemon_pid      = /var/run/netdisco_daemon.pid\n");
			fwrite($file,"daemon_poll     = 2\n");
			fwrite($file,"\n# ---- Port Control Settings ---vlanctl             = true,portctl_timeout      = 60\n");
			fwrite($file,"\n# Data Archiving and Logging\ncompresslogs    = true\ncompress        = /bin/gzip -f\ndatadir = /var/log/netdisco\n");
			fwrite($file,"logextension    = txt");
			fwrite($file,"\n# ---- Database Settings ----\ndb_Pg = dbi:Pg:dbname=".$dbname.";host=".$pghost."\ndb_Pg_user = ".$dbuser."\ndb_Pg_pw = ".$dbpwd."\n");
			fwrite($file,"db_Pg_opts      = PrintError => 1, AutoCommit => 1\n");

			$roarr = array();
			$rwarr = array();
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($data["ro"] = 't') array_push($roarr,$data["name"]);
				if($data["rw"] = 't') array_push($rwarr,$data["name"]);
			}
			$snmpro = "";
			$snmprw = "";
			for($i=0;$i<count($roarr);$i++) {
				$snmpro .= $roarr[$i];
				if($i != count($roarr)-1) $snmpro .= ",";
			}
			for($i=0;$i<count($rwarr);$i++) {
				$snmprw .= $rwarr[$i];
				if($i != count($rwarr)-1) $snmprw .= ",";
			}
	
			if(!$snmpro) $snmpro = "public";
			if(!$snmprw) $snmprw = "private";
			fwrite($file,"\n# ---- SNMP Settings ----\ncommunity = ".$snmpro."\ncommunity_rw = ".$snmprw."\nsnmptimeout = ".($snmptimeout*1000000)."\n");
			fwrite($file,"snmpretries = ".$snmptry."\nsnmpver = ".$snmpver."\n");
			fwrite($file,"mibhome = /usr/local/share/netdisco-mibs/\n");
			fwrite($file,"mibdirs = ".'$mibhome/allied,  $mibhome/asante, $mibhome/cisco, \\'."\n");
			fwrite($file,'$mibhome/foundry, $mibhome/hp,     $mibhome/nortel, $mibhome/extreme, $mibhome/rfc,     $mibhome/net-snmp'."\n".'bulkwalk_off = true'."\n");
			fclose($file);

			$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","w+");
			if(!$file)
				return 1;
			fwrite($file,$firstnode."\n");
			fclose($file);
			return 0;
		}
?>