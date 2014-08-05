<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/netdiscoCfg.api.php");
	
	if(!class_exists("iNetdisco")) {
		
	final class iNetdisco extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new FSLocales();
			$this->rulesclass = new rNetdisco($this->loc);
			
			$this->menu = _("Z-Eye Engine");
			$this->menutitle = _("Netdisco collect engine");
			
			$this->modulename = "netdisco";
		}
		
		public function Load() {
			FS::$iMgr->setTitle(_("title-netdisco"));
			return $this->showMain();
		}

		private function showMain() {
			FS::$iMgr->setURL("");
			
			$output = FS::$iMgr->h1("title-netdisco");
			$output .= FS::$iMgr->cbkForm("1");
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
			if ($count < 1) {
				$output .= FS::$iMgr->printError(_("err-no-snmp-community").
					"<br /><br />".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2", _("Go")),true);
				return $output;
			} 

			$netdiscoCfg = readNetdiscoConf();
			if (!is_array($netdiscoCfg)) {
				$output .= FS::$iMgr->printError(_("err-unable-read")." /usr/local/etc/netdisco/netdisco.conf",true);
				return $output;
			}

			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">"._("global-conf")."</th></tr>".
				FS::$iMgr->idxLine("dns-suffix","suffix",array("value" => $netdiscoCfg["dnssuffix"],"tooltip" => "tooltip-dnssuffix")).
				FS::$iMgr->idxLine("main-node","fnode",array("value" => $netdiscoCfg["firstnode"],"type" => "ip", "tooltip" => "tooltip-firstnode")).
				"<tr><th colspan=\"2\">"._("timer-conf")."</th></tr>".
				FS::$iMgr->idxLine("node-expiration","nodetimeout",array("type" => "num", "value" => $netdiscoCfg["nodetimeout"],
					"size" => 4, "length" => 4, "tooltip" => "tooltip-nodetimeout")).
				FS::$iMgr->idxLine("device-expiration","devicetimeout",array("type" => "num", "size" => 4,
					"value" => $netdiscoCfg["devicetimeout"], "length" => 4, "tooltip" => "tooltip-devicetimeout")).
				"<tr><th colspan=\"2\">"._("database")."</th></tr>".
				FS::$iMgr->idxLine("pg-host","pghost",array("value" => $netdiscoCfg["pghost"])).
				FS::$iMgr->idxLine("pg-db","dbname",array("value" => $netdiscoCfg["dbname"])).
				FS::$iMgr->idxLine("pg-user","dbuser",array("value" => $netdiscoCfg["dbuser"])).
				FS::$iMgr->idxLine("pg-pwd","dbpwd",array("value" => $netdiscoCfg["dbpwd"],"type" => "pwd")).
				"<tr><th colspan=\"2\">"._("snmp-conf")."</th></tr>".
				FS::$iMgr->idxLine("snmp-timeout","snmptimeout",
					array("type" => "num", "value" => $netdiscoCfg["snmptimeout"], "size" => 2, "length" => 2, "tooltip" => "tooltip-snmptimeout")).
				FS::$iMgr->idxLine("snmp-try","snmptry",array("type" => "num", "value" => $netdiscoCfg["snmptry"],
					"size" => 2, "length" => 2, "tooltip" => "tooltip-snmptry")).
				"<tr><td>"._("snmp-version")."</td><td>".
				FS::$iMgr->select("snmpver").
				FS::$iMgr->selElmt("1","1",$netdiscoCfg["snmpver"] == 1 ? true : false).
				FS::$iMgr->selElmt("2c","2",$netdiscoCfg["snmpver"] == 2 ? true : false).
				"</select></td></tr>".
				FS::$iMgr->tableSubmit("Save");
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
					if (checkNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) == true) {
						if (writeNetdiscoConf($suffix,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmptimeout,$snmptry,$snmpver,$fnode) != 0) {
							$this->log(2,"Fail to write netdisco configuration");
							FS::$iMgr->ajaxEchoError("err-write-fail");
							return;
						}
						$this->log(0,"Netdisco configuration changed");
						FS::$iMgr->ajaxEchoOK("Done");
						return;
					}
					$this->log(2,"Bad netdisco configuration");
					FS::$iMgr->ajaxEchoError("err-bad-datas");
					return;
				default: break;
			}
		}
	};
	
	}
	
	$module = new iNetdisco();
?>
