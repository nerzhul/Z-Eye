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
	require_once(dirname(__FILE__)."/netdiscoCfg.api.php");
	
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
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
					"<br /><br /><a href=\"index.php?mod=".$this->mid."&sh=2\">".$this->loc->s("Go")."</a>");
				return $output;
			} 

			$netdiscoCfg = readNetdiscoConf();
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
					if(checkNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) == true) {
						if(writeNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) != 0) {
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

					$netdiscoCfg = readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					
					FS::$dbMgr->Insert("z_eye_snmp_communities","name,ro,rw","'".$name."','".($ro == "on" ? 't' : 'f')."','".
						($rw == "on" ? 't' : 'f')."'");

					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
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
					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				default: break;
			}
		}
	};
?>
