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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	
	class iNetdisco extends genModule{
		function iNetdisco() { parent::genModule(); $this->loc = new lNetdisco(); }
		
		public function Load() {
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err == 1)
				$output .= FS::$iMgr->printError($this->loc->s("err-invalid-data"));
			else if($err == 2)
				$output .= FS::$iMgr->printError($this->loc->s("err-write-fail"));
			else if ($err == -1)
				$output .= FS::$iMgr->printDebug($this->loc->s("mod-ok"));
			$output .= $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = "";
                        $sh = FS::$secMgr->checkAndSecuriseGetData("sh");
                        
                        if(!FS::isAjaxCall()) {
				$output .= "<h1>".$this->loc->s("title-netdisco")."</h1>";
                                $output .= "<div id=\"contenttabs\"><ul>";
                                $output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("General"),$sh);
                                $output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,$this->loc->s("SNMP-communities"),$sh);
                                $output .= "</ul></div>";
                                $output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
                                $output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
                                return $output;
                        }

                        if(!$sh) $sh = 1;

                        switch($sh) {
                                case 1: $output .= $this->showMainConfTab(); break;
                                case 2: $output .= $this->showSNMPTab(); break;
			}

			return $output;	
		}

		private function showSNMPTab() {
			$output = "";
			$found = false;


			$tmpoutput = "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Read-Only")."</th><th>".$this->loc->s("Read-Write")."</th></tr>";
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$tmpoutput .= "<tr><td>".$data["name"]."</td><td>".($data["ro"] == 't' ? "X" : "")."</td><td>".($data["rw"] == 't' ? "X": "")."</td><td></td></tr>";
			}
			if($found) $output .= $tmpoutput."</table>";	
		}

		private function showMainConfTab() {
			$output = "";
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$file = file("/usr/local/etc/netdisco/netdisco.conf");

			$dnssuffix = ".local";
			$netdiscodir = "/usr/local/share/netdisco/";
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
			$snmpmibs = "/usr/local/share/netdisco-mibs/";
			$snmpver = 2;

			$count = FS::$dbMgr->Count("z_eye_snmp_communities");
			if($count < 1) {
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community"));
				return $output;
			} 

			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">".$this->loc->s("global-conf")."</th></tr>";
			if(!$file) {
				$output .= FS::$iMgr->printError($this->loc->s("err-unable-read")." /usr/local/etc/netdisco/netdisco.conf");
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
					fclose($file);
				}
			}
			$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","r");
			$firstnode = fread($file,filesize("/usr/local/etc/netdisco/netdisco-topology.txt"));
			fclose($file);
			$output .= "<tr><td>".$this->loc->s("dns-suffix")."</td><td>".FS::$iMgr->input("suffix",$dnssuffix)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("main-node")."</td><td>".FS::$iMgr->input("fnode",$firstnode)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("timer-conf")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("node-expiration")."</td><td>".FS::$iMgr->input("nodetimeout",$nodetimeout,4,4)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("device-expiration")."</td><td>".FS::$iMgr->input("devicetimeout",$devicetimeout,4,4)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("database")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-host")."</td><td>".FS::$iMgr->input("pghost",$pghost)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-db")."</td><td>".FS::$iMgr->input("dbname",$dbname)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-user")."</td><td>".FS::$iMgr->input("dbuser",$dbuser)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-pwd")."</td><td>".FS::$iMgr->password("dbpwd",$dbpwd)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("snmp-conf")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-read")."</td><td>".FS::$iMgr->input("snmpro",$snmpro)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-write")."</td><td>".FS::$iMgr->input("snmprw",$snmprw)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-timeout")."</td><td>".FS::$iMgr->input("snmptimeout",$snmptimeout,2,2)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-try")."</td><td>".FS::$iMgr->input("snmptry",$snmptry,2,2)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-version")."</td><td>";
			$output .= FS::$iMgr->select("snmpver");
			$output .= FS::$iMgr->selElmt("1","1",$snmpver == 1 ? true : false);
			$output .= FS::$iMgr->selElmt("2c","2",$snmpver == 2 ? true : false);
			$output .= "</select></td></tr>";
			$output .= "<tr>".FS::$iMgr->tableSubmit($this->loc->s("Save"))."</tr>";
			$output .= "</table></form>";
			return $output;
		}

		public function checkNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$firstnode) {
			if(!FS::$secMgr->isNumeric($nodetimeout) || !FS::$secMgr->isNumeric($devicetimeout) || !FS::$secMgr->isNumeric($snmptimeout) ||
				!FS::$secMgr->isNumeric($snmptry) || !FS::$secMgr->isNumeric($snmpver))
				return false;
			if($dns == "" || $pghost == "" || $dbname == "" || $dbuser == "" || $dbpwd == "" || $snmpro == "" || $snmprw == "" || 
				$firstnode == "")
				return false;
			if($nodetimeout > 3600 || $nodetimeout < 10 || $devicetimeout > 3600 || $devicetimeout < 10 || $snmptimeout > 30 || $snmptimeout < 1 || $snmptry > 10 ||
				$snmptry < 1 || ($snmpver != 1 && $snmpver != 2))
				return false;
			return true;
		}
		public function writeNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$firstnode) {
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
						if($this->writeNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$fnode) != 0) {
							FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",2,"Fail to write netdisco configuration");
							header("Location: index.php?mod=".$this->mid."&err=2");
							return;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",0,"Netdisco configuration changed");
						header("Location: index.php?mod=".$this->mid."&err=-1");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Bad netdisco configuration");
					header("Location: index.php?mod=".$this->mid."&err=1");	
					return;
				default: break;
			}
		}
	};
?>
