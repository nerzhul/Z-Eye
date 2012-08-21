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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetdisco extends genModule{
		function iNetdisco() { parent::genModule(); }
		public function Load() {
			$output = "<h4>Management du service de découverte Netdisco</h4>";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err == 1)
				$output .= FS::$iMgr->printError("Les données que vous avez entré ne sont pas valides !");
			else if ($err == -1)
				$output .= FS::$iMgr->printDebug("Modification prise en compte.");
			$output .= $this->showMainConf();
			return $output;
		}

		private function showMainConf() {
			$output = "";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">Configuration globale</th></tr>";
			if(Config::getOS() == "FreeBSD")
				$file = file("/usr/local/etc/netdisco/netdisco.conf");
			else if(Config::getOS() == "Debian")
				$file = file("/etc/netdisco/netdisco.conf");
				
			$dnssuffix = ".local";
			if(Config::getOS() == "FreeBSD")
				$netdiscodir = "/usr/local/share/netdisco/";
			else if(Config::getOS() == "Debian")
				$netdiscodir = "/usr/lib/netdisco/";
			$nodetimeout = 60;
			$devicetimeout = 90;
			$pghost = "127.0.0.1";
			$dbname = "netdiscodb";
			$dbuser = "netdiscouser";
			$dbpwd = "password";
			$snmpro = "public";
			$snmprw = "private";
			$snmptimeout = 1;
			$snmptry = 3;
			if(Config::getOS() == "FreeBSD")
				$snmpmibs = "/usr/local/share/netdisco-mibs/";
			else if(Config::getOS() == "Debian")
				$snmpmibs = "/usr/share/netdisco/mibs/";
			$snmpver = 2;

			if(!$file) {
				if(Config::getOS() == "FreeBSD")
					$output .= FS::$iMgr->printError("Impossible de lire le fichier /usr/local/etc/netdisco/netdisco.conf");
				else if(Config::getOS() == "Debian")
					$output .= FS::$iMgr->printError("Impossible de lire le fichier /etc/netdisco/netdisco.conf");
			} else {
				foreach ($file as $lineNumber => $buf) {
					$buf = trim($buf);
					$buf = str_replace("\t", "", $buf);
					$buf = preg_replace("# #", "", $buf);
					$res = preg_split("#=#",$buf);

					if(count($res) == 2) {
						if(preg_match("#^domain$#",$res[0]))
							$dnssuffix = $res[1];
						else if(preg_match("#^home$#",$res[0]))
							$netdiscodir = $res[1];
						else if(preg_match("#^expire_nodes$#",$res[0]))
							$nodetimeout = $res[1];
						else if(preg_match("#^expire_devices$#",$res[0]))
							$devicetimeout = $res[1];
						else if(preg_match("#^db_Pg_user$#",$res[0]))
							$dbuser = $res[1];
						else if(preg_match("#^db_Pg_pw$#",$res[0]))
							$dbpwd = $res[1];
						else if(preg_match("#^community$#",$res[0]))
							$snmpro = $res[1];
						else if(preg_match("#^community_rw$#",$res[0]))
							$snmprw = $res[1];
						else if(preg_match("#^snmptimeout$#",$res[0]))
							$snmptimeout = $res[1]/1000000;
						else if(preg_match("#^snmpretries$#",$res[0]))
							$snmptry = $res[1];
						else if(preg_match("#^mibhome$#",$res[0]))
							$snmpmibs = $res[1];
						else if(preg_match("#^snmpver$#",$res[0]))
							$snmpver = $res[1];
					} else if(count($res) == 4) {
						if(preg_match("#^db_Pg$#",$res[0])) {
							$pghost = $res[3];
							$nsplit = preg_split("#;#",$res[2]);
							$dbname = $nsplit[0];
						}
					}
				}
				fclose($file);
			}
			if(Config::getOS() == "FreeBSD")
				$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","r");
			else if(Config::getOS() == "Debian")
				$file = fopen("/etc/netdisco/netdisco-topology.txt","r");
			if(Config::getOS() == "FreeBSD")
				$firstnode = fread($file,filesize("/usr/local/etc/netdisco/netdisco-topology.txt"));
			else if(Config::getOS() == "Debian")
				$firstnode = fread($file,filesize("/etc/netdisco/netdisco-topology.txt"));
			fclose($file);
			// @TODO: load configuration file
			$output .= "<tr><td>Suffixe DNS</td><td>".FS::$iMgr->addInput("suffix",$dnssuffix)."</td></tr>";
			$output .= "<tr><td>Noeud principal</td><td>".FS::$iMgr->addInput("fnode",$firstnode)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Configuration des timers</th></tr>";
			$output .= "<tr><td>Expiration des noeuds</td><td>".FS::$iMgr->addInput("nodetimeout",$nodetimeout,4,4)."</td></tr>";
			$output .= "<tr><td>Expiration des périphériques</td><td>".FS::$iMgr->addInput("devicetimeout",$devicetimeout,4,4)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Base de données</th></tr>";
			$output .= "<tr><td>Hôte PostGRESQL</td><td>".FS::$iMgr->addInput("pghost",$pghost)."</td></tr>";
			$output .= "<tr><td>Nom de la base de données</td><td>".FS::$iMgr->addInput("dbname",$dbname)."</td></tr>";
			$output .= "<tr><td>Utilisateur PostGRESQL</td><td>".FS::$iMgr->addInput("dbuser",$dbuser)."</td></tr>";
			$output .= "<tr><td>Mot de passe</td><td>".FS::$iMgr->addPasswdField("dbpwd",$dbpwd)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Configuration SNMP</th></tr>";
			$output .= "<tr><td>Communautés en lecture</td><td>".FS::$iMgr->addInput("snmpro",$snmpro)."</td></tr>";
			$output .= "<tr><td>Communautés en écriture</td><td>".FS::$iMgr->addInput("snmprw",$snmprw)."</td></tr>";
			$output .= "<tr><td>Timeout des requêtes</td><td>".FS::$iMgr->addInput("snmptimeout",$snmptimeout,2,2)."</td></tr>";
			$output .= "<tr><td>Tentatives maximales</td><td>".FS::$iMgr->addInput("snmptry",$snmptry,2,2)."</td></tr>";
			$output .= "<tr><td>Version SNMP</td><td>";
			$output .= FS::$iMgr->addList("snmpver");
			$output .= FS::$iMgr->addElementToList("1","1",$snmpver == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("2c","2",$snmpver == 2 ? true : false);
			$output .= "</select></td></tr>";
			$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
			$output .= "</table></form>";
			return $output;
		}

		public function checkNetdiscoConf($dns,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$firstnode) {
			if(!FS::$secMgr->isNumeric($nodetimeout) || !FS::$secMgr->isNumeric($devicetimeout) || !FS::$secMgr->isNumeric($snmptimeout) ||
				!FS::$secMgr->isNumeric($snmptry) || !FS::$secMgr->isNumeric($snmpver))
				return false;

			if($dns == "" || $dir == "" || $pghost == "" || $dbname == "" || $dbuser == "" || $dbpwd == "" || $snmpro == "" || $snmprw == "" || $snmpmibs == "" || $firstnode == "")
				return false;

			if($nodetimeout > 3600 || $nodetimeout < 10 || $devicetimeout > 3600 || $devicetimeout < 10 || $snmptimeout > 30 || $snmptimeout < 1 || $snmptry > 10 ||
				$snmptry < 1 || ($snmpver != 1 && $snmpver != 2))
				return false;

			return true;
		}
		public function writeNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$firstnode) {
			if(Config::getOS() == "FreeBSD")
				$file = fopen("/usr/local/etc/netdisco/netdisco.conf","w+");
			else if(Config::getOS() == "Debian")
				$file = fopen("/etc/netdisco/netdisco.conf","w+");
			fwrite($file,"# ---- General Settings ----\n");
			fwrite($file,"domain = ".$dns."\n");
			if(Config::getOS() == "FreeBSD") {
				fwrite($file,"home = /usr/local/share/netdisco\n");
				fwrite($file,"topofile = /usr/local/etc/netdisco/netdisco-topology.txt\n");
			}
			else if(Config::getOS() == "Debian") {
				fwrite($file,"home = /usr/lib/netdisco\n");
				fwrite($file,"topofile = /etc/netdisco/netdisco-topology.txt\n");
			}
			
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
			fwrite($file,"\n# ---- SNMP Settings ----\ncommunity = ".$snmpro."\ncommunity_rw = ".$snmprw."\nsnmptimeout = ".($snmptimeout*1000000)."\n");
			fwrite($file,"snmpretries = ".$snmptry."\nsnmpver = ".$snmpver."\n");
			if(Config::getOS() == "FreeBSD")
				fwrite($file,"mibhome = /usr/local/share/netdisco-mibs/\n");
			else if(Config::getOS() == "Debian")
				fwrite($file,"mibhome = /usr/share/netdisco/mibs/\n");
			fwrite($file,"mibdirs = ".'$mibhome/allied,  $mibhome/asante, $mibhome/cisco, \\'."\n");
			fwrite($file,'$mibhome/foundry, $mibhome/hp,     $mibhome/nortel, $mibhome/extreme, $mibhome/rfc,     $mibhome/net-snmp'."\n".'bulkwalk_off = true'."\n");
			fclose($file);

			if(Config::getOS() == "FreeBSD")
				$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","w+");
			else if(Config::getOS() == "Debian")
				$file = fopen("/etc/netdisco/netdisco-topology.txt","w+");
			fwrite($file,$firstnode."\n");
			fclose($file);
			return 0;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$suffix = FS::$secMgr->checkAndSecurisePostData("suffix");
					$nodetimeout = FS::$secMgr->checkAndSecurisePostData("nodetimeout");
					$devicetimeout = FS::$secMgr->checkAndSecurisePostData("devicetimeout");
					$pghost = FS::$secMgr->checkAndSecurisePostData("pghost");
					$dbname = FS::$secMgr->checkAndSecurisePostData("dbname");
					$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
					$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
					$snmpro = FS::$secMgr->checkAndSecurisePostData("snmpro");
					$snmprw = FS::$secMgr->checkAndSecurisePostData("snmprw");
					$snmptimeout = FS::$secMgr->checkAndSecurisePostData("snmptimeout");
					$snmptry = FS::$secMgr->checkAndSecurisePostData("snmptry");
					$snmpver = FS::$secMgr->checkAndSecurisePostData("snmpver");
					$fnode = FS::$secMgr->checkAndSecurisePostData("fnode");
					if($this->checkNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$fnode) == true) {
						$this->writeNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$fnode);
						header("Location: index.php?mod=".$this->mid."&err=-1");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&err=1");	
					return;
				default: break;
			}
		}
	};
?>
