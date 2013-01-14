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
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-write-fail")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-already-exist")); break;
				case 4: $output .= FS::$iMgr->printError($this->loc->s("err-not-exist")); break;
				case 5: $output .= FS::$iMgr->printError($this->loc->s("err-read-fail")); break;
				case 6: $output .= FS::$iMgr->printError($this->loc->s("err-readorwrite")); break;
				case -1: $output .= FS::$iMgr->printDebug($this->loc->s("mod-ok")); break;
				default: break;
			}
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

			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=2")."<ul class=\"ulform\">";
			$formoutput .= "<li>".FS::$iMgr->input("name","",20,64,$this->loc->s("snmp-community"))."</li>";
			$formoutput .= "<li>".FS::$iMgr->check("ro",array("label" => $this->loc->s("Read"), "tooltip" => $this->loc->s("tooltip-read")))."</li>";
			$formoutput .= "<li>".FS::$iMgr->check("rw",array("label" => $this->loc->s("Write"), "tooltip" => $this->loc->s("tooltip-write")))."</li>";
			$formoutput .= "<li>".FS::$iMgr->submit("",$this->loc->s("Save"))."</li>";
			$formoutput .= "</ul></form>";

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Add-community"));

			$tmpoutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Read")."</th><th>".$this->loc->s("Write")."</th><th></th></tr>";
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$tmpoutput .= "<tr><td>".$data["name"]."</td><td>".($data["ro"] == 't' ? "X" : "")."</td><td>".($data["rw"] == 't' ? "X": "")."</td><td><a href=\"index.php?mod=".$this->mid."&act=3&snmp=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."</a></td></tr>";
			}
			if($found) $output .= $tmpoutput."</table>";	
			return $output;
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
			$snmptimeout = 1;
			$snmptry = 3;
			$snmpmibs = "/usr/local/share/netdisco-mibs/";
			$snmpver = 2;

			$count = FS::$dbMgr->Count("z_eye_snmp_communities","name");
			if($count < 1) {
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community"));
				return $output;
			} 

			$netdiscoCfg = $this->readNetdiscoConf();
			if(!is_array($netdiscoCfg)) {
				$output .= FS::$iMgr->printError($this->loc->s("err-unable-read")." /usr/local/etc/netdisco/netdisco.conf");
				return $output;
			}


			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">".$this->loc->s("global-conf")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("dns-suffix")."</td><td>".FS::$iMgr->input("suffix",$netdiscoCfg["dnssuffix"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("main-node")."</td><td>".FS::$iMgr->input("fnode",$netdiscoCfg["firstnode"])."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("timer-conf")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("node-expiration")."</td><td>".FS::$iMgr->input("nodetimeout",$netdiscoCfg["nodetimeout"],4,4)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("device-expiration")."</td><td>".FS::$iMgr->input("devicetimeout",$netdiscoCfg["devicetimeout"],4,4)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("database")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-host")."</td><td>".FS::$iMgr->input("pghost",$netdiscoCfg["pghost"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-db")."</td><td>".FS::$iMgr->input("dbname",$netdiscoCfg["dbname"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-user")."</td><td>".FS::$iMgr->input("dbuser",$netdiscoCfg["dbuser"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("pg-pwd")."</td><td>".FS::$iMgr->password("dbpwd",$netdiscoCfg["dbpwd"])."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("snmp-conf")."</th></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-timeout")."</td><td>".FS::$iMgr->input("snmptimeout",$netdiscoCfg["snmptimeout"],2,2)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-try")."</td><td>".FS::$iMgr->input("snmptry",$netdiscoCfg["snmptry"],2,2)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("snmp-version")."</td><td>";
			$output .= FS::$iMgr->select("snmpver");
			$output .= FS::$iMgr->selElmt("1","1",$netdiscoCfg["snmpver"] == 1 ? true : false);
			$output .= FS::$iMgr->selElmt("2c","2",$netdiscoCfg["snmpver"] == 2 ? true : false);
			$output .= "</select></td></tr>";
			$output .= "<tr>".FS::$iMgr->tableSubmit($this->loc->s("Save"))."</tr>";
			$output .= "</table></form>";
			return $output;
		}

		private function readNetdiscoConf() {
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

		public function checkNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$firstnode) {
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

		public function writeNetdiscoConf($dns,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$firstnode) {
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
					$snmptimeout = FS::$secMgr->checkAndSecurisePostData("snmptimeout");
					$snmptry = FS::$secMgr->checkAndSecurisePostData("snmptry");
					$snmpver = FS::$secMgr->checkAndSecurisePostData("snmpver");
					$fnode = FS::$secMgr->checkAndSecurisePostData("fnode");
					if($this->checkNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) == true) {
						if($this->writeNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) != 0) {
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
				case 2: // Add SNMP community
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$ro = FS::$secMgr->checkAndSecurisePostData("ro");
					$rw = FS::$secMgr->checkAndSecurisePostData("rw");

					if(!$name || $ro && $ro != "on" || $rw && $rw != "on") {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Adding data");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}

					if(FS::$dbMgr->GetOneData("z_eye_snmp_communities","name = '".$name."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",1,"Community '".$name."' already in DB");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=3");
						return;
					}

					// User must choose read and/or write
					if($ro != "on" && $rw != "on") {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=6");
						return;
					}

					$netdiscoCfg = $this->readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					
					FS::$dbMgr->Insert("z_eye_snmp_communities","name,ro,rw","'".$name."','".($ro == "on" ? 't' : 'f')."','".
						($rw == "on" ? 't' : 'f')."'");

					$this->writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				case 3: // Remove SNMP community
					$name = FS::$secMgr->checkAndSecuriseGetData("snmp");
					if(!$name) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Deleting data");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					if(!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$name."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Community '".$name."' not in DB");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=4");
						return;
					}

					$netdiscoCfg = $this->readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					FS::$dbMgr->Delete("z_eye_snmp_communities","name = '".$name."'");
					$this->writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				default: break;
			}
		}
	};
?>
