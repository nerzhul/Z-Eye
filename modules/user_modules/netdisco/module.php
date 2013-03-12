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
			FS::$iMgr->setCurrentModule($this);
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-write-fail")); break;
				case 5: $output .= FS::$iMgr->printError($this->loc->s("err-read-fail")); break;
				case 6: $output .= FS::$iMgr->printError($this->loc->s("err-readorwrite")); break;
				case -1: $output .= FS::$iMgr->printDebug($this->loc->s("mod-ok")); break;
				default: break;
			}
			$output .= $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = FS::$iMgr->h1("title-netdisco");
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

			$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."snmp_communities","name");
			if($count < 1) {
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
					"<br /><br /><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2\">".$this->loc->s("Go")."</a>");
				return $output;
			} 

			$netdiscoCfg = readNetdiscoConf();
			if(!is_array($netdiscoCfg)) {
				$output .= FS::$iMgr->printError($this->loc->s("err-unable-read")." /usr/local/etc/netdisco/netdisco.conf");
				return $output;
			}

			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">".$this->loc->s("global-conf")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("dns-suffix"),"suffix",$netdiscoCfg["dnssuffix"],array("tooltip" => "tooltip-dnssuffix"));
			$output .= FS::$iMgr->idxLine($this->loc->s("main-node"),"fnode",$netdiscoCfg["firstnode"],array("tooltip" => "tooltip-firstnode"));
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("timer-conf")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("node-expiration"),"nodetimeout",$netdiscoCfg["nodetimeout"],array("size" => 4, "length" => 4, "tooltip" => "tooltip-nodetimeout"));
			$output .= FS::$iMgr->idxLine($this->loc->s("device-expiration"),"devicetimeout",$netdiscoCfg["devicetimeout"],array("size" => 4, "length" => 4, "tooltip" => "tooltip-devicetimeout"));
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("database")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("pg-host"),"pghost",$netdiscoCfg["pghost"]);
			$output .= FS::$iMgr->idxLine($this->loc->s("pg-db"),"dbname",$netdiscoCfg["dbname"]);
			$output .= FS::$iMgr->idxLine($this->loc->s("pg-user"),"dbuser",$netdiscoCfg["dbuser"]);
			$output .= FS::$iMgr->idxLine($this->loc->s("pg-pwd"),"dbpwd",$netdiscoCfg["dbpwd"],array("type" => "pwd"));
			$output .= "<tr><th colspan=\"2\">".$this->loc->s("snmp-conf")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("snmp-timeout"),"snmptimeout",$netdiscoCfg["snmptimeout"],array("size" => 2, "length" => 2, "tooltip" => "tooltip-snmptimeout"));
			$output .= FS::$iMgr->idxLine($this->loc->s("snmp-try"),"snmptry",$netdiscoCfg["snmptry"],array("size" => 2, "length" => 2, "tooltip" => "tooltip-snmptry"));
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
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
							return;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",0,"Netdisco configuration changed");
						FS::$iMgr->redir("mod=".$this->mid."&err=-1");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Bad netdisco configuration");
					FS::$iMgr->redir("mod=".$this->mid."&err=1");
					return;
				default: break;
			}
		}
	};
?>
