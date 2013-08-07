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
	
	require_once(dirname(__FILE__)."/snmpdiscovery.api.php");
	
	$device = FS::$secMgr->checkAndSecuriseGetData("d");
	if (FS::isAjaxCall() && !$device)
		$device = FS::$secMgr->checkAndSecurisePostData("sw");
	require_once(dirname(__FILE__)."/cisco.func.php");
	require_once(dirname(__FILE__)."/dell.func.php");
	require_once(dirname(__FILE__)."/device.api.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");
	
	final class iSwitchMgmt extends FSModule {
		function __construct($locales) { 
			parent::__construct($locales); 
			$this->modulename = "switches";

			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			if (FS::isAjaxCall() && !$device)
				$device = FS::$secMgr->checkAndSecurisePostData("sw");
			if ($device) {
				$this->vendor = FS::$dbMgr->GetOneData("device","vendor","name = '".$device."'");
				switch($this->vendor) {
					case "cisco": $this->devapi = new CiscoAPI(); break;
					case "dell": $this->devapi = new DellAPI(); break;
					default: $this->devapi = new DeviceAPI(); break;
				}
				$this->devapi->setLocales($this->loc);
			}
			else
				$this->vendor = "";
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-network-device-mgmt"));
			return $this->showMain();
		}

		private function showMain() {
			$output = "";
			if (!FS::isAjaxCall())
				$output .= FS::$iMgr->h1("title-network-device-mgmt");

			$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."snmp_communities","name");
                        if ($count < 1) {
                                $output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
                                        "<br /><br /><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2\">".$this->loc->s("Go")."</a>");
                                return $output;
                        }

			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$port = FS::$secMgr->checkAndSecuriseGetData("p");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			if ($port != NULL && $device != NULL)
				$output .= $this->showPortInfos();
			else if ($device != NULL)
				$output .= $this->showDeviceInfos();
			else
				$output .= $this->showDeviceList();

			return $output;
		}

		private function showPortInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
		        $port = FS::$secMgr->checkAndSecuriseGetData("p");
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$device."'");
			$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
			if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_read") && 
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
				return FS::$iMgr->printError($this->loc->s("err-no-credentials"));
			}
			switch($err) {
				case 1:	$output .= FS::$iMgr->printError($this->loc->s("err-fail-mod-switch")." !"); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-bad-datas")); break;
				default: break;
			}
			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h2($port." ".$this->loc->s("on")." <a href=\"index.php?mod=".$this->mid."&d=".$device."\">".$device."</a>",true);
				$panElmts = array();
				$panElmts[] = array(1,"mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("Configuration"));
				if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshuser","device = '".$device."'") &&
					(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshportinfos") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshportinfos")))
					$panElmts[] = array(4,"mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("switch-view"));
				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readportstats") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readportstats"))
					$panElmts[] = array(2,"mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("bw-stats"));
				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") ||
					FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon"))
					$panElmts[] = array(3,"mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("Monitoring"));
				$output .= FS::$iMgr->tabPan($panElmts,$sh);
			} else {
				$this->devapi->setDevice($device);
				// Get Port ID
				$portid = $this->devapi->getPortId($port);
				$this->devapi->setPortId($portid);
				// Port modification
				if (!$sh || $sh == 1) {
					$query = FS::$dbMgr->Select("device_port","name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."' AND port ='".$port."'");
					if ($data = FS::$dbMgr->Fetch($query)) {
						if ($portid != -1 && (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") ||
							FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write"))) {
							$output .= FS::$iMgr->cbkForm("9");
							$output .= FS::$iMgr->hidden("portid",$portid);
							$output .= FS::$iMgr->hidden("sw",$device);
							$output .= FS::$iMgr->hidden("port",$port);
						}
						$output .= "<table><tr><th>".$this->loc->s("Field")."</th><th>".$this->loc->s("Value")."</th></tr>";
						$output .= FS::$iMgr->idxLine($this->loc->s("Description"),"desc",$data["name"],array("tooltip" => "tooltip-desc"));

						$prise = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$dip."' AND port = '".$port."'");
						$room = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","room","ip = '".$dip."' AND port = '".$port."'");
						$output .= FS::$iMgr->idxLine($this->loc->s("Room"),"room",$room,array("tooltip" => "tooltip-room"));
						$output .= FS::$iMgr->idxLine($this->loc->s("Plug"),"prise",$prise,array("tooltip" => "tooltip-plug"));
						$output .= "<tr><td>".$this->loc->s("MAC-addr")."</td><td>".$data["mac"]."</td></tr>";
						$mtu = $this->devapi->getPortMtu();
						$output .= "<tr><td>".$this->loc->s("State")." / ".$this->loc->s("Speed")." / ".$this->loc->s("Duplex").($mtu != -1 ? " / ".$this->loc->s("MTU") : "")."</td><td>";
						if ($data["up_admin"] == "down")
								$output .= "<span style=\"color: red;\">".$this->loc->s("Shut")."</span>";
						else if ($data["up_admin"] == "up" && $data["up"] == "down")
							$output .= "<span style=\"color: orange;\">".$this->loc->s("Inactive")."</span>";
						else if ($data["up"] == "up")
							$output .= "<span style=\"color: black;\">".$this->loc->s("Active")."</span>";
						else
							$output .= "unk";
						$output .= " / ".$data["speed"]." / ".($data["duplex"] == "" ? "[NA]" : $data["duplex"]).($mtu != -1 ? " / ".$mtu : "")."</td></tr>";
						$output .= $this->devapi->showStateOpts();
					
						$output .= $this->devapi->showSpeedOpts();

						$output .= $this->devapi->showDuplexOpts();

						$output .= $this->devapi->showVlanOpts();

						$output .= $this->devapi->showPortSecurityOpts();

						$output .= "<tr><td colspan=\"2\">".$this->loc->s("Others")."</td></tr>";

						$output .= $this->devapi->showCDPOpts();

						$output .= $this->devapi->showDHCPSnoopingOpts();

						$output .= $this->devapi->showSaveCfg();

						$output .= "</table>";
						if ($portid != -1) {
							if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") ||
								FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
								$output .= "<center><br />".FS::$iMgr->submit("",$this->loc->s("Save"))."</center>";
								$output .= "</form>";
							}
							$this->devapi->unsetPortId();
						}
						else
							$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-cache"));
					}
					else
						$output .= FS::$iMgr->printError("Les données demandées n'existent pas !");
				}
				// Port Stats
				else if ($sh == 2) {
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readportstats") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readportstats")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$file = file(dirname(__FILE__)."/../../datas/rrd/".$dip."_".$portid.".html");
					if ($file) {
						$filebuffer = "";
						$stopbuffer = 0;
						$count = count($file);
						for($i=0;$i<$count;$i++) {
							$file[$i] = preg_replace("#src=\"(.*)\"#","src=\"datas/rrd/$1\"",$file[$i]);
							if (preg_match("#<head>#",$file[$i]) || preg_match("#<div id=\"footer#",$file[$i]) || 
								 preg_match("#<div id=\"legend#",$file[$i]))
															$stopbuffer = 1;
							else if ($stopbuffer == 1 && (preg_match("#</head>#",$file[$i]) || preg_match("#</div>#",$file[$i])))
								$stopbuffer = 0;
							else if ($stopbuffer == 0 && !preg_match("#<title>#",$file[$i]) && !preg_match("#<meta#",$file[$i]) 
								&& !preg_match("#<h1>(.*)</h1>#",$file[$i]) && !preg_match("#<body#",$file[$i]) &&
								!preg_match("#<html#",$file[$i]) && !preg_match("#<!--#",$file[$i]))
								$filebuffer .= $file[$i];
						}
						$output .= "<br />".$filebuffer."<br /><center><span style=\"font-size: 9px;\">".$this->loc->s("generated-mrtg")."</span></center>";
					}
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-port-bw")." !");
				}
				// Monitoring
				else if ($sh == 3) {
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=16");
					$output .= FS::$iMgr->hidden("device",$device).FS::$iMgr->hidden("port",$port);
					$climit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_monitor","climit","device = '".$device."' AND port = '".$port."'");
					$wlimit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_monitor","wlimit","device = '".$device."' AND port = '".$port."'");
					$desc = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_monitor","description","device = '".$device."' AND port = '".$port."'");
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->check("enmon",array("check" => (($climit > 0 || $wlimit) > 0 ? true : false),"label" => $this->loc->s("enable-monitor")))."</li><li>";
					$output .= FS::$iMgr->input("desc",$desc,20,200,$this->loc->s("Label"))."</li><li>";
					$output .= FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 10, "length" => 10, "label" => $this->loc->s("warn-step")))."</li><li>";
					$output .= FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 10, "length" => 10, "label" => $this->loc->s("crit-step")))."</li><li>";
					$output .= FS::$iMgr->submit("","Enregister")."</li>";
					$output .= "</ul>";
					$output .= "</form>";
				}
				else if ($sh == 4) {
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshportinfos") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshportinfos")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}

					$sshuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshuser","device = '".$device."'");
					if (!$sshuser) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-sshlink-configured")."<br /><br />
							<a href=\"index.php?mod=".$this->mid."&d=".$device."&sh=7\">".$this->loc->s("Go")."</a>");
						return $output;
					}
					
					$sshpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshpwd","device = '".$device."'");
					$enablepwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","enablepwd","device = '".$device."'");
					if ($sshpwd && $enablepwd) {
						$stdio = $this->devapi->connectToDevice($dip,$sshuser,base64_decode($sshpwd),base64_decode($enablepwd));
						if (FS::$secMgr->isNumeric($stdio) && $stdio > 0) {
							switch($stdio) {
								case 1: $output .= FS::$iMgr->printError($this->loc->s("err-conn-fail")); break;
								case 2: $output .= FS::$iMgr->printError($this->loc->s("err-auth-fail")); break;
								case 3: $output .= FS::$iMgr->printError($this->loc->s("err-enable-auth-fail")); break;
								case NULL: $output .= FS::$iMgr->printError($this->loc->s("err-not-implemented")); break; 
							}	
							return $output;
						}
						$output .= FS::$iMgr->h2("iface-dev-cfg").
							"<pre style=\"width: 50%; display:inline-block;\">".preg_replace("#[\n]#","<br />",$this->devapi->sendSSHCmd("show running-config interface ".$port))."</pre>";
						$output .= FS::$iMgr->h2("iface-dev-status").
							"<pre style=\"width: 50%; display:inline-block;\">".preg_replace("#[\n]#","<br />",$this->devapi->sendSSHCmd("show interface ".$port))."</pre>";
					}
					
				}
			}
			return $output;
		}

		protected function showDeviceInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			$od = FS::$secMgr->checkAndSecuriseGetData("od");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$this->devapi->setDevice($device);
			$dip = $this->devapi->getDeviceIP();
			if ($od == NULL) $od = "port";
			else if ($od == "desc" || $od == "name") $od = "name";
			else if ($od != "vlan" && $od != "prise" && $od != "port") $od = "port";

			$output = "";

			$snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$device."'");
			$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
			if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_read") && 
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) { 
				return FS::$iMgr->printError($this->loc->s("err-no-credentials"));
			}
			if (!FS::isAjaxCall()) {
				FS::$iMgr->showReturnMenu(true);
				$dloc = FS::$dbMgr->GetOneData("device","location","name = '".$device."'");
				$output = FS::$iMgr->h2($this->loc->s("Device")." ".$device." (".$dip.($dloc != NULL ? " - ".$dloc : "").")",true);

				$panElmts = array();
				$panElmts[] = array(6,"mod=".$this->mid."&d=".$device.($od ? "&od=".$od : "").($filter ? "&fltr=".$filter : ""),$this->loc->s("Portlist"));
				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswvlans") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswvlans"))
					$panElmts[] = array(5,"mod=".$this->mid."&d=".$device,$this->loc->s("VLANlist"));

				$panElmts[] = array(3,"mod=".$this->mid."&d=".$device,$this->loc->s("frontview"));

				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswmodules") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswmodules") ||
					FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswdetails") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswdetails"))
					$panElmts[] = array(1,"mod=".$this->mid."&d=".$device,$this->loc->s("Internal-mod"));

				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshshowstart") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshshowstart")) 
					$panElmts[] = array(8,"mod=".$this->mid."&d=".$device,$this->loc->s("Startup-Cfg"));
				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshshowrun") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshshowrun"))
					$panElmts[] = array(9,"mod=".$this->mid."&d=".$device,$this->loc->s("Running-Cfg"));

				$panElmts[] = array(4,"mod=".$this->mid."&d=".$device,$this->loc->s("Advanced-tools"));

				if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_sshpwd") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshpwd"))
					$panElmts[] = array(7,"mod=".$this->mid."&d=".$device,$this->loc->s("SSH"));
				$output .= FS::$iMgr->tabPan($panElmts,$showmodule);
			} else {
				if ($dip == NULL) {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-device"));
					return $output;
				}

				if ($showmodule == 1) {
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswmodules") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswmodules") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswdetails") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswdetails")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswdetails") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswdetails")) { 
						$query = FS::$dbMgr->Select("device","*","name ='".$device."'");
						if ($data = FS::$dbMgr->Fetch($query)) {
							$output .= FS::$iMgr->h3("Device-detail");
							$output .= "<table>";
							$output .= "<tr><td>".$this->loc->s("Name")."</td><td>".$data["name"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("Place")." / ".$this->loc->s("Contact")."</td><td>".$data["location"]." / ".$data["contact"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("Model")." / ".$this->loc->s("Serialnb")."</td><td>".$data["model"]." / ".$data["serial"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("OS")." / ".$this->loc->s("Version")."</td><td>".$data["os"]." / ".$data["os_ver"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("Description")."</td><td>".$data["description"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("Uptime")."</td><td>".$data["uptime"]."</td></tr>";
							$found = 0;
							$tmpoutput = "<tr><td>".$this->loc->s("Energy")."</td><td>";
	
							$query2 = FS::$dbMgr->Select("device_power","module,power,status","ip = '".$dip."'");
							while($data2 = FS::$dbMgr->Fetch($query2)) {
								$found = 1;
								$query3 = FS::$dbMgr->Select("device_port_power","module,class","module = '".$data2["module"]."' AND ip = '".$dip."'");
								$pwcount = 0;
								while($data3 = FS::$dbMgr->Fetch($query3)) {
									if ($data3["class"] == "class2") $pwcount += 7;
									else if ($data3["class"] == "class3") $pwcount += 15;
								}
								$tmpoutput .= "Module ".$data2["module"]." : ".$pwcount." / ".$data2["power"]." Watts (statut: ";
								$tmpoutput .= ($data2["status"] == "on" ? "<span style=\"color: green;\">".$data2["status"]."</span>" : $data2["status"]);
								$tmpoutput .= ")<br />";
							}
	
							$tmpoutput .= "</td></tr>";
							if ($found == 1) $output .= $tmpoutput;
							$output .= "<tr><td>".$this->loc->s("IP-addr")."</td><td>".$data["ip"]."</td></tr>";
							$iswif = (preg_match("#AIR#",$data["model"]) ? true : false);
							if ($iswif == false) {
								$output .= "<tr><td>".$this->loc->s("MAC-addr")."</td><td>".$data["mac"]."</td></tr>";
								$output .= "<tr><td>".$this->loc->s("VTP-domain")."</td><td>".$data["vtp_domain"]."</td></tr>";
							}
							$output .= "</table>";
						}
					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswmodules") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswmodules")) { 
						$query = FS::$dbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'",array("order" => "parent,name"));
						$found = 0;
						$devmod = array();
						while($data = FS::$dbMgr->Fetch($query)) {
							if ($found == 0) $found = 1;
							if (!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
							$idx = count($devmod[$data["parent"]]);
							$devmod[$data["parent"]][$idx] = array();
							$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
							$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
							$devmod[$data["parent"]][$idx]["name"] = $data["name"];
							$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
							$devmod[$data["parent"]][$idx]["type"] = $data["type"];
							$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
							$devmod[$data["parent"]][$idx]["model"] = $data["model"];
							$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
							$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
						}
						if ($found == 1) {
							$output .= FS::$iMgr->h3("Internal-mod");
							$output .= "<table><tr><th>".$this->loc->s("Description")."</th><th>".$this->loc->s("Name")."</th>
								<th>".$this->loc->s("Type")."</th><th></th><th></th><th></th><th></th><th>".$this->loc->s("Model")."</th></tr>".$this->showDeviceModules($devmod,1)."</table>";
						}
					}
					return $output;
				}
				else if ($showmodule == 3) {
					$query = FS::$dbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'",array("order" => "parent,name"));
					$found = 0;
					$devmod = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if ($found == 0) $found = 1;
						if (!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
						$idx = count($devmod[$data["parent"]]);
						$devmod[$data["parent"]][$idx] = array();
						$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
						$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
						$devmod[$data["parent"]][$idx]["name"] = $data["name"];
						$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
						$devmod[$data["parent"]][$idx]["type"] = $data["type"];
						$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
						$devmod[$data["parent"]][$idx]["model"] = $data["model"];
						$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
						$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
					}
					if ($found == 1) {
						$output .= FS::$iMgr->h3("frontview");
						$output .= "<script>
							/*
							* 48 ports + 4 uplinks
							*/
							var sw48p4x = [34,33,63,62,93,92,123,121,153,151,183,181,222,222,251,251,281,281,311,311,341,341,371,371,
								410,411,439,440,469,470,499,499,529,529,558,559,599,600,628,629,658,659,688,689,718,718,747,748];
							var sw48p4y = [12,102,12,102,12,102,12,102,12,102,12,102,13,101,13,101,13,101,13,101,13,102,13,102,
								13,101,13,101,13,101,13,101,13,101,13,101,14,101,14,101,14,101,14,101,14,101,14,101];
							var sw2448p4 = [[816,15],[817,101],[846,15],[847,101]];
							var sw2448p4b = [[783,101],[813,101],[844,101],[874,101]];
							// poe
							var sw48poe4x = [29,29,59,59,89,89,118,118,148,148,178,178,217,217,247,247,277,277,307,307,336,336,366,366,
							406,406,436,436,466,466,495,495,525,525,555,555,595,595,625,625,655,655,685,685,714,714,743,743];
							var sw48poe4py = 85;
							var sw48poe4iy = 51;

							function drawContext(obj,type,ptab,gptab,poetab) {
								var canvas = document.getElementById(obj);
								var context = canvas.getContext(\"2d\");
								context.fillStyle = \"rgba(0,118,176,0)\";
								context.fillRect(0, 0, context.canvas.width, context.canvas.height);
								context.translate(context.canvas.width/2, context.canvas.height/2);
								context.translate(-context.canvas.width/2, -context.canvas.height/2);
								context.beginPath();
								context.moveTo(-2,-2);
								context.lineTo(892,-2);
								context.lineTo(892,119);
								context.lineTo(-2,119);
								context.lineTo(-2,-2);
								context.closePath(); // complete custom shape

								context.moveTo(0,0);
								var img = new Image();
								img.onload = function() {
									context.drawImage(img, 0,0,892,119);
									var startIdx = 0; var stopULIdx = sw2448p4.length; 
									var normportX = sw48p4x; var normportY = sw48p4y;
									var trunkport = sw2448p4;
									var poeX = sw48poe4x; var poePY = sw48poe4py; var poeIMPY = sw48poe4iy;
									switch(type) { 
										case 2:	startIdx = 24; stopULIdx = 2; break;
										case 3: startIdx = 24; trunkport = sw2448p4b; break; 
									}
									for(i=startIdx;i<normportX.length;i++) {
										curptab = ptab[i];
										if (startIdx > 0) curptab = ptab[i-startIdx]; 
										if (curptab == 0)
											context.fillStyle = \"rgba(200, 0, 0, 0.6)\";
										else if (curptab == 1)
											context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
										else if (curptab == 2)
											context.fillStyle = \"rgba(0, 255, 50, 0.9)\";
										else
											context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
										context.fillRect(normportX[i], normportY[i], 7, 7);
										context.fillStyle = \"rgba(200, 200, 0, 1)\";
										if (startIdx > 0) {
											if (poetab[i-startIdx] == 1)
												context.fillText(\"7.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));
											else if (poetab[i-startIdx] == 2)
												context.fillText(\"15.0\",poeX[i]-2, (i%2 == 0 ? poeIMPY : poePY));
											/*else
												context.fillText(\"0.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));*/
										}
										else {
											if (poetab[i] == 1)
												context.fillText(\"7.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));
											else if (poetab[i] == 2)
												context.fillText(\"15.0\",poeX[i]-2, (i%2 == 0 ? poeIMPY : poePY));
											/*else
												context.fillText(\"0.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));*/
										}
									}
									for(i=0;i<stopULIdx;i++) {
										if (gptab[i] == 0)
												context.fillStyle = \"rgba(255, 0, 0, 0.6)\";
										else if (gptab[i] == 1)
												context.fillStyle = \"rgba(255, 150, 0, 1.0)\";
										else if (gptab[i] == 2)
												context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
										else
												context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
										if (stopULIdx == 2) {
											if (i == 0)
												context.fillRect(trunkport[i+1][0], trunkport[i+1][1], 7, 7);
											else
												context.fillRect(trunkport[i+2][0], trunkport[i+2][1], 7, 7);
										}
										else
											context.fillRect(trunkport[i][0], trunkport[i][1], 7, 7);
									}
								}
								switch(type) {
									case 1:	img.src = '/styles/images/Switch48-4.png'; break;
									case 2:	img.src = '/styles/images/Switch24-2-r.png'; break;
									case 3: img.src = '/styles/images/Switch24-2-r2.png'; break;
								}
							}
						</script>";
						$swlist = $this->getDeviceSwitches($devmod,1);
						$swlist = preg_split("#\/#",$swlist);
						$countsw = count($swlist)-1;
						for($i=$countsw;$i>=0;--$i) {
							switch($swlist[$i]) {
								case "WS-C3750-48P": case "WS-C3750-48TS": case "WS-C3750-48PS": case "WS-C3750G-48TS": case "WS-C3750-48PS": { // 100 Mbits switches
									$poearr = array();
									// POE States
									$query = FS::$dbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
									while($data = FS::$dbMgr->Fetch($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}

									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; var poetab = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',1,ptab,gptab,poetab);</script>";
									break;
								}
								case "WS-C3750-24TS": case "WS-C3750G-24TS": case "WS-C3750G-24WS": case "WS-C3750G-24T": case "WS-C3750-FS":
								case "WS-C3750-24PS": case "WS-C3750-24P": // 100 Mbits switches
									$poearr = array();
									// POE States
									$query = FS::$dbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
									while($data = FS::$dbMgr->Fetch($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}
	
									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}

									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; var powport = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',2,ptab,gptab,powport);</script>";
									break;
								case "WS-C2960S-24TS-L": // Gbit switches
									$poearr = array();
									$portlist = "";
									for($j=1;$j<25;$j++) {
										$portlist .= "'GigabitEthernet".($i+1)."/0/".$j."'";
										if ($j < 24)
											$portlist .= ",";
									}
									// POE States
									$query = FS::$dbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port IN (".$portlist.")");
									while($data = FS::$dbMgr->Fetch($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}
	
									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN (".$portlist.")",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN ('GigabitEthernet".($i+1)."/0/25', 'GigabitEthernet".($i+1)."/0/26',
											'GigabitEthernet".($i+1)."/0/27','GigabitEthernet".($i+1)."/0/28')",array("order" => "port"));
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if (preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if ($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if ($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if ($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=25;$j<=(25+$count);$j++) {
										$output .= $arr_res[$j];
										if ($j < (24+$count)) $output .= ",";
									}
									$output .= "]; var powport = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if ($j < $count) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',3,ptab,gptab,powport);</script>";
									break;
								default: break;
							}
						}
					}
					return $output;			
				}
				else if ($showmodule == 4) { // advanced tools
					$err = FS::$secMgr->checkAndSecuriseGetData("err");
					if (FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_dhcpsnmgmt") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_dhcpsnmgmt")) { 
						$output .= FS::$iMgr->h3("title-dhcpsnooping");
						$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&d=".$device."&act=24",array("id" => "dhcpsnfrm"));
						$enable = $this->devapi->getDHCPSnoopingStatus();
						$opt82 = $this->devapi->getDHCPSnoopingOpt82();
						$match = $this->devapi->getDHCPSnoopingMatchMAC();
						$output .= $this->loc->s("Enable")." ".FS::$iMgr->check("enable",array("tooltip" => "tooltip-dhcpsnoopingen", "check" => $enable == 1))."<br />";
						$output .= $this->loc->s("Use-DHCP-opt-82")." ".FS::$iMgr->check("opt82",array("tooltip" => "tooltip-dhcpsnoopingopt", "check" => $opt82 == 1))."<br />";
						$output .= $this->loc->s("Match-MAC-addr")." ".FS::$iMgr->check("matchmac",array("tooltip" => "tooltip-dhcpsnoopingmatch", "check" => $match == 1))."<br />";

						$vlanlist = array();
						$query = FS::$dbMgr->Select("device_vlan","vlan,description","ip = '".$dip."'");
						while($data = FS::$dbMgr->Fetch($query))
							$vlanlist[$data["vlan"]] = $data["description"];

						$dhcpsnvlanlist = $this->devapi->getDHCPSnoopingVlans();
						if ($dhcpsnvlanlist && is_array($dhcpsnvlanlist)) {
							$output .= $this->loc->s("Apply-VLAN").": <br />";
							$output .= FS::$iMgr->select("vlansnooping","",NULL,true,array("tooltip" => "tooltip-dhcpsnoopingvlan", "size" => count($dhcpsnvlanlist)/4));

							foreach($dhcpsnvlanlist as $vlan => $value)
								$output .= FS::$iMgr->selElmt($vlan." - ".$vlanlist[$vlan],$vlan,$value == 1);

							$output .= "</select><br />";
						}
						$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</form>";
					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_retagvlan") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_retagvlan")) { 
						$js = "function searchports() {
							waitingPopup('".$this->loc->s("search-ports")."...');
							var ovlid = document.getElementsByName('oldvl')[0].value;
							$.get('index.php?mod=".$this->mid."&at=3&act=10&d=".$device."&vlan='+ovlid, function(data) {
							$('#vlplist').html(data); unlockScreen(true); });
							return false; };";
						$js .= "function checkTagForm() {
							if ($('#vlplist') == null || $('#vlplist').html().length < 1) {
								alert('".$this->loc->s("must-verify-ports")." !');
								return false;
							}
							if (document.getElementsByName('accept')[0].checked == false) {
								alert('".$this->loc->s("must-confirm")." !');
								return false;
							}
							return true;
						};";
						$output .= FS::$iMgr->js($js);
						$output .= FS::$iMgr->h3("title-retag");
						if ($err && $err == 1) $output .= FS::$iMgr->printError($this->loc->s("err-one-bad-value")." !");
						$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&d=".$device."&d=".$device."&act=11");
						$output .= $this->loc->s("old-vlanid")." ".FS::$iMgr->numInput("oldvl")."<br />";
						$output .= $this->loc->s("new-vlanid")." ".FS::$iMgr->numInput("newvl")."<br />";
						$output .= FS::$iMgr->JSSubmit("searchvlan",$this->loc->s("Verify-ports"),"return searchports();")."<div id=\"vlplist\"></div>";
						$output .= $this->loc->s("Confirm")." ".FS::$iMgr->check("accept");
						$output .= FS::$iMgr->JSSubmit("modify",$this->loc->s("Apply"),"return checkTagForm();")."</form><br />";

					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_restorestartupcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_restorestartupcfg")) {
						// Common JS WARN: it's only for CISCO
						$output .= FS::$iMgr->js("function checkCopyState(copyId) {
							setTimeout(function() {
								$.post('index.php?at=3&mod=".$this->mid."&act=13&d=".$device."&saveid='+copyId, function(data) {
									if (data == 2) {
										$('#subpop').html('".$this->loc->s("Copy-in-progress")." ...');
										checkCopyState(copyId);
									}
									else if (data == 3) {
										$('#subpop').html('".$this->loc->s("Success")." !');
										setTimeout(function() { unlockScreen(true); },1000);
									}
									else if (data == 4) {
										$.post('index.php?at=3&mod=".$this->mid."&act=14&d=".$device."&saveid='+copyId, function(data) {
											$('#subpop').html('".$this->loc->s("Fail")." !<br />Cause: '+data); 
										});
										setTimeout(function() { unlockScreen(true); },5000);
									}
									else {
										$('#subpop').html('".$this->loc->s("unk-answer").": '+data);
										setTimeout(function() { unlockScreen(true); },5000);
									}
								}); }, 1000);
						}");
					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_exportcfg")) {

						// Copy startup-config -> TFTP/FTP server
						$js = "function arangeform() {";
						$js .= "if (document.getElementsByName('exportm')[0].value == 2 || document.getElementsByName('exportm')[0].value == 4 || document.getElementsByName('exportm')[0].value == 5) {";
						$js .= "$('#slogin').show();";
						$js .= "} else if (document.getElementsByName('exportm')[0].value == 1) {";
						$js .= "$('#slogin').hide(); }};";
						$js .= "function sendbackupreq() {";
						$js .= "waitingPopup('".$this->loc->s("req-sent")."...');";
						$js .= "$.post('index.php?at=3&mod=".$this->mid."&act=12&d=".$device."', { exportm: document.getElementsByName('exportm')[0].value, srvip: document.getElementsByName('srvip')[0].value,
						srvfilename: document.getElementsByName('srvfilename')[0].value, srvuser: document.getElementsByName('srvuser')[0].value, srvpwd: document.getElementsByName('srvpwd')[0].value,
						io: document.getElementsByName('io')[0].value },
						function(data) { 
							var copyId = data;
							$('#subpop').html('".$this->loc->s("Copy-in-progress")."...');
							checkCopyState(copyId);
						});";
						$js .= "return false;};";
						$output .= FS::$iMgr->js($js);

						$output .= FS::$iMgr->h3("title-transfer-conf");
						$output .= $this->loc->s("Server-type")." ".FS::$iMgr->select("exportm","arangeform();");
						$output .= FS::$iMgr->selElmt("TFTP",1);
						$output .= FS::$iMgr->selElmt("FTP",2);
						$output .= FS::$iMgr->selElmt("SCP",4);
						$output .= FS::$iMgr->selElmt("SFTP",5);
						$output .= "</select><br />";
						$output .= $this->loc->s("transfer-way")." ".FS::$iMgr->select("io");
						$output .= FS::$iMgr->selElmt($this->loc->s("Export"),1);
						$output .= FS::$iMgr->selElmt($this->loc->s("Import"),2);
						$output .= "</select><br />";
						$output .= $this->loc->s("Server-addr")." ".FS::$iMgr->IPInput("srvip")."<br />";
						$output .= $this->loc->s("Filename")." ".FS::$iMgr->input("srvfilename")."<br />";
						$output .= "<div id=\"slogin\" style=\"display:none;\">".$this->loc->s("User")." ".FS::$iMgr->input("srvuser");
						$output .= " ".$this->loc->s("Password")." ".FS::$iMgr->password("srvpwd")."</div>";
						$output .= FS::$iMgr->JSSubmit("",$this->loc->s("Send"),"return sendbackupreq();");
					}

					if (FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_restorestartupcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_restorestartupcfg")) {
						// Copy startup-config -> running-config
						$output .= FS::$iMgr->js("function restorestartupconfig() {
							waitingPopup('".$this->loc->s("req-sent")."...');
						$.post('index.php?at=3&mod=".$this->mid."&act=15&d=".$device."', function(data) { 
							var copyId = data;
							$('#subpop').html('".$this->loc->s("restore-in-progress")."...');
							checkCopyState(copyId);
						});
						return false;
						};");
						$output .= FS::$iMgr->h3("title-restore-startup");
						$output .= FS::$iMgr->JSSubmit("",$this->loc->s("Restore"),"return restorestartupconfig();");
					}
					return $output;
				}
				else if ($showmodule == 5) {
					$output .= FS::$iMgr->h3("VLANlist");
					$found = 0;
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswvlans") && 
                                        	!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswvlans")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$query = FS::$dbMgr->Select("device_vlan","vlan,description,creation","ip = '".$dip."'",array("order" => "vlan"));
					$tmpoutput = "<table id=\"tvlanList\"><thead><tr><th class=\"headerSortDown\">ID</th><th>".$this->loc->s("Description").
						"</th><th>".$this->loc->s("ip-network")."</th><th>".$this->loc->s("creation-date")."</th></tr></thead>";
					while($data = FS::$dbMgr->Fetch($query)) {
						if (!$found) $found = 1;
						$netid = "";
						$cidr = "";

						$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask","vlanid = '".$data["vlan"]."'");
						if ($data2 = FS::$dbMgr->Fetch($query2)) {
							$netid = $data2["netid"];
							$net = new FSNetwork();
							$net->SetNetAddr($netid);
							$net->SetNetMask($data2["netmask"]);
							$net->calcCIDR();
							$cidr = $net->getCIDR();
						}

						$crdate = preg_split("#\.#",$data["creation"]);
						$tmpoutput .= "<tr><td>".$data["vlan"]."</td><td>".$data["description"]."</td><td>".($netid ? $netid."/".$cidr : "")."</td><td>".$crdate[0]."</td></tr>";
					}
					if ($found) {
						$output .= $tmpoutput."</table>";
						FS::$iMgr->jsSortTable("tvlanList");
					}
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-vlan")." !");
					return $output;	
				}
				else if ($showmodule == 6) {

					$iswif = (preg_match("#AIR#",FS::$dbMgr->GetOneData("device","model","name = '".$device."'")) ? true : false);

					if ($iswif == false) {
						$poearr = array();
						// POE States
						$query = FS::$dbMgr->Select("device_port_power","port,class","ip = '".$dip."'");
						while($data = FS::$dbMgr->Fetch($query))
							$poearr[$data["port"]] = $data["class"];
					}

					$prisearr = array();
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","port,prise","ip = '".$dip."'");
					while($data = FS::$dbMgr->Fetch($query))
						$prisearr[$data["port"]] = $data["prise"];

					$found = 0;
					if ($iswif == false) {
						// Script pour modifier le nom de la prise
						$output .= FS::$iMgr->js("function modifyPrise(src,sbmit,sw_,swport_,swpr_) {
						if (sbmit == true) {
						$.post('index.php?at=3&mod=".$this->mid."&d=".$device."&act=2', { sw: sw_, swport: swport_, swprise: document.getElementsByName(swpr_)[0].value }, function(data) {
						$(src+'l').html(data); $(src+' a').toggle();
						}); }
						else $(src).toggle(); }");
					}
					$tmpoutput = "<table id=\"tportList\"><thead><tr><th class=\"headerSortDown\"><a href=\"index.php?mod=".$this->mid."&d=".$device."&od=port\">Port</a></th><th>";
					$tmpoutput .= $this->loc->s("Description")."</th>
						<th>".$this->loc->s("Plug")."</th><th>Up (Link/Admin)</th>";
					if ($iswif == false)
						$tmpoutput .= "<th>".$this->loc->s("Duplex")." (Link/Admin)</th>";
					$tmpoutput .= "<th>Vitesse</th>";
					if ($iswif == false)
						$tmpoutput .= "<th>POE</th>";
					$tmpoutput .= "<th>";
					if ($iswif == true) $tmpoutput .= $this->loc->s("Channel")."</th><th>".$this->loc->s("Power")."</th><th>SSID";
					else $tmpoutput .= "Vlans</th><th>".$this->loc->s("Connected-devices")."</th></tr></thead>";
					$query = FS::$dbMgr->Select("device_port","port,name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."'",array("order" => $od));
					while($data = FS::$dbMgr->Fetch($query)) {
						if (preg_match("#unrouted#",$data["port"]))
							continue;
						$filter_ok = 0;
						if ($filter == NULL) $filter_ok = 1;

						if ($found == 0) $found = 1;
						$convport = preg_replace("#\/#","-",$data["port"]);
						$swpdata = (isset($prisearr[$data["port"]]) ? $prisearr[$data["port"]] : "");
						$tmpoutput2 = "<tr id=\"".$convport."\"><td><a href=\"index.php?mod=".$this->mid."&d=".$device."&p=".$data["port"]."\">".$data["port"]."</a></td><td>";

						// Editable Desc
						$tmpoutput2 .= $data["name"];
						$tmpoutput2 .= "</td><td>";
						// Editable piece
						$tmpoutput2 .= "<div id=\"swpr_".$convport."\">";
						$tmpoutput2 .= "<a onclick=\"javascript:modifyPrise('#swpr_".$convport." a',false);\"><div id=\"swpr_".$convport."l\" class=\"modport\">";
						$tmpoutput2 .= ($swpdata == "" ? "Modifier" : $swpdata);
						$tmpoutput2 .= "</div></a><a style=\"display: none;\">";
						$tmpoutput2 .= FS::$iMgr->input("swprise-".$convport,$swpdata,10,10);
						$tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPrise('#swpr_".$convport."',true,'".$dip."','".$data["port"]."','swprise-".$convport."');\" />";
						$tmpoutput2 .= "</a></div>";
						$tmpoutput2 .= "</td><td>";
						// Editable state
						$tmpoutput2 .= "<div id=\"swst_".$convport."\">";
						if ($data["up_admin"] == "down")
							$tmpoutput2 .= "<span style=\"color: red;\">".$this->loc->s("Shut")."</span>";
						else if ($data["up_admin"] == "up" && $data["up"] == "down")
							$tmpoutput2 .= "<span style=\"color: orange;\">".$this->loc->s("Inactive")."</span>";
						else if ($data["up"] == "up")
							$tmpoutput2 .= "<span style=\"color: black;\">".$this->loc->s("Active")."</span>";
						else
							$tmpoutput2 .= "unk";
						$tmpoutput2 .= "</td><td>";
						if ($iswif == false) {

							$tmpoutput2 .= "<div id=\"swdp_".$convport."\">";
							$tmpoutput2 .= "<div id=\"swdp_".$convport."l\" class=\"modport\"><span style=\"color: black;\">";
							$dup = (strlen($data["duplex"]) > 0 ? $data["duplex"] : "[NA]");
							$dupadm = (strlen($data["duplex_admin"]) > 0 ? $data["duplex_admin"] : "[NA]");
							if ($dup == "half" && $dupadm != "half") $dup = "<span style=\"color: red;\">half</span>";
							$tmpoutput2 .= $dup." / ".$dupadm;
							$tmpoutput2 .= "</span></div></div>";
		
							$tmpoutput2 .= "</td><td>";
						}
						$tmpoutput2 .= $data["speed"]."</td><td>";
		
						if ($iswif == false) {
							// POE
							if (isset($poearr[$data["port"]])) {
								if ($poearr[$data["port"]] == "class0") $tmpoutput2 .= "0.0 Watts";
								else if ($poearr[$data["port"]] == "class2") $tmpoutput2 .= "7.0 Watts";
								else if ($poearr[$data["port"]] == "class3") $tmpoutput2 .= "15.0 Watts";
								else $tmpoutput2 .= "Unk class";
							}
							else
							$tmpoutput2 .= "N";
							$tmpoutput2 .= "</td><td>";
						}
		
						$query2 = FS::$dbMgr->Select("device_port_vlan","vlan,native,voice","ip = '".$dip."' AND port = '".$data["port"]."'",array("order" => "vlan"));
		
						$nvlan = $data["vlan"];
						$vlanlist = "";
						$vlancount = 0;
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							if ($data2["native"] == "t" && $data2["vlan"] != 1) $nvlan = $data2["vlan"];
							if ($data2["vlan"] == $filter) $filter_ok = 1;
							if ($vlancount == 3) {
								$vlancount = 0;
								$vlanlist .= "<br />";
							}
							$vlanlist .= "<a href=\"index.php?mod=".$this->mid."&vlan=".$data2["vlan"]."\">".$data2["vlan"]."</a>";
							if ($data2["native"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(n)</span>";
							if ($data2["voice"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(v)</span>";
							$vlanlist .= ",";
							$vlancount++;
						}
		
						if ($iswif == false) {
							$tmpoutput2 .= substr($vlanlist,0,strlen($vlanlist)-1);
		
						}
						if ($iswif == false) {
							$tmpoutput2 .= "</td><td>";
							$query2 = FS::$dbMgr->Select("node","mac","switch = '".$dip."' AND port = '".$data["port"]."'",array("order" => "mac"));
							while($data2 = FS::$dbMgr->Fetch($query2)) {
								$tmpoutput2 .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data2["mac"]."\">".$data2["mac"]."</a><br />";
								$query3 = FS::$dbMgr->Select("node_ip","ip","mac = '".$data2["mac"]."'",array("order" => "time_last","ordersens" => 1,"limit" => 5));
								while($data3 = FS::$dbMgr->Fetch($query3)) {
									$tmpoutput2 .= "&nbsp;&nbsp;<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
									$query4 = FS::$dbMgr->Select("node_nbt","nbname,domain,nbuser","mac = '".$data2["mac"]."' AND ip = '".$data3["ip"]."'");
									if ($data4 = FS::$dbMgr->Fetch($query4)) {
										if ($data4["domain"] != "")
											$tmpoutput2 .= "&nbsp;&nbsp;\\\\<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["domain"]."\">".$data4["domain"]."</a>\\<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
										else
											$tmpoutput2 .= "&nbsp;&nbsp;<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
										$tmpoutput2 .= "&nbsp;&nbsp;".($data4["nbuser"] == "" ? "[UNK]" : $data4["nbuser"])."@<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
									}
								}
							}
						}
						else {
							$channel = FS::$dbMgr->GetOneData("device_port_wireless","channel","ip = '".$dip."' AND port = '".$data["port"]."'");
							$power = FS::$dbMgr->GetOneData("device_port_wireless","power","ip = '".$dip."' AND port = '".$data["port"]."'");
							$ssid = FS::$dbMgr->GetOneData("device_port_ssid","ssid","ip = '".$dip."' AND port = '".$data["port"]."'");
							$tmpoutput2 .= $channel."</td><td>".$power."</td><td>".$ssid;
						}
						$tmpoutput2 .= "</td></tr>";

						if ($filter_ok == 1)
							$tmpoutput .= $tmpoutput2;
					}

					if ($found != 0) {
						$output .= $tmpoutput;
						$output .= "</table>";
						FS::$iMgr->jsSortTable("tportList");
					}
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-device"));
					}
					else if ($showmodule == 7) {
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_sshpwd") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshpwd")) {
							return FS::$iMgr->printError($this->loc->s("err-no-rights"));
						}
						$sshuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshuser","device = '".$device."'");
						$output .= $this->loc->s("ssh-link-state").": ";
						if ($sshuser) {
							$output .= "<span style=\"color: green;\">".$this->loc->s("Enabled")."</span> ";
							$output .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=23&d=".$device);
						}
						else
							$output .= "<span style=\"color: red;\">".$this->loc->s("Disabled")."</span>";
						$output .= FS::$iMgr->cbkForm("22&d=".$device);
						$output .= "<table><tr><th>".$this->loc->s("Field")."</th><th>".$this->loc->s("Value")."</th></tr>";	
						$output .= FS::$iMgr->idxLine($this->loc->s("User"),"sshuser",$sshuser);
						$output .= FS::$iMgr->idxLine($this->loc->s("SSH-pwd"),"sshpwd","",array("type" => "pwd"));
						$output .= FS::$iMgr->idxLine($this->loc->s("SSH-pwd-repeat"),"sshpwd2","",array("type" => "pwd"));
						$output .= FS::$iMgr->idxLine($this->loc->s("enable-pwd"),"enablepwd","",array("type" => "pwd"));
						$output .= FS::$iMgr->idxLine($this->loc->s("enable-pwd-repeat"),"enablepwd2","",array("type" => "pwd"));
						$output .= FS::$iMgr->tableSubmit("Save");
					}
					else if ($showmodule == 8) {
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshshowstart") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshshowstart")) {
							return FS::$iMgr->printError($this->loc->s("err-no-rights"));
						}
						$sshuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshuser","device = '".$device."'");
						if (!$sshuser) {
							$output .= FS::$iMgr->printError($this->loc->s("err-no-sshlink-configured")."<br /><br />
								<a href=\"index.php?mod=".$this->mid."&d=".$device."&sh=7\">".$this->loc->s("Go")."</a>");
							return $output;
						}
					
						$sshpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshpwd","device = '".$device."'");
						$enablepwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","enablepwd","device = '".$device."'");
						if ($sshpwd && $enablepwd) {
							$stdio = $this->devapi->connectToDevice($dip,$sshuser,base64_decode($sshpwd),base64_decode($enablepwd));
							if (FS::$secMgr->isNumeric($stdio) && $stdio > 0) {
								switch($stdio) {
									case 1: return FS::$iMgr->printError($this->loc->s("err-conn-fail")); break;
									case 2: return FS::$iMgr->printError($this->loc->s("err-auth-fail")); break;
									case 3: return FS::$iMgr->printError($this->loc->s("err-enable-auth-fail")); break;
									default: return FS::$iMgr->printError($this->loc->s("err-not-implemented")); break; 
								}	
						}
						$output .= "<pre style=\"width: 50%; display:inline-block;\">".preg_replace("#[\n]#","<br />",$this->devapi->showSSHStartCfg())."</pre>";
						}
					}
					else if ($showmodule == 9) {
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_sshshowrun") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshshowrun")) {
							return FS::$iMgr->printError($this->loc->s("err-no-rights"));
						}
						$sshuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshuser","device = '".$device."'");
						if (!$sshuser) {
							$output .= FS::$iMgr->printError($this->loc->s("err-no-sshlink-configured")."<br /><br />
								<a href=\"index.php?mod=".$this->mid."&d=".$device."&sh=7\">".$this->loc->s("Go")."</a>");
							return $output;
						}
					
						$sshpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","sshpwd","device = '".$device."'");
						$enablepwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_pwd","enablepwd","device = '".$device."'");
						if ($sshpwd && $enablepwd) {
							$ret = $this->devapi->connectToDevice($dip,$sshuser,base64_decode($sshpwd),base64_decode($enablepwd));
							if (FS::$secMgr->isNumeric($ret) && $ret > 0) {
								switch($ret) {
									case 1: return FS::$iMgr->printError($this->loc->s("err-conn-fail")); break;
									case 2: return FS::$iMgr->printError($this->loc->s("err-auth-fail")); break;
									case 3: return FS::$iMgr->printError($this->loc->s("err-enable-auth-fail")); break;
									case NULL: return FS::$iMgr->printError($this->loc->s("err-not-implemented")); break; 
								}	
							}
							else 
								$output .= "<pre style=\"width: 50%; display:inline-block;\">".preg_replace("#[\n]#","<br />",$this->devapi->showSSHRunCfg($stdio))."</pre>";
						}
					}
					else {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-tab"));
					}
				}
				return $output;
			}

			private function showDeviceModules($devmod,$idx,$level=10) {
				if ($level == 0)
					return "";
				if (!isset($devmod[$idx])) return "";
				$output = "";
				$count = count($devmod[$idx]);
				for($i=0;$i<$count;$i++) {
					$output .= "<tr><td>" .$devmod[$idx][$i]["desc"]."</td><td>".$devmod[$idx][$i]["name"]."</td><td>";

					$output .= $devmod[$idx][$i]["type"];

					$output .= "</td><td>";
					if (strlen($devmod[$idx][$i]["hwver"]) > 0)
						$output .= "hw: ".$devmod[$idx][$i]["hwver"];

					$output .= "</td><td>";

					if (strlen($devmod[$idx][$i]["fwver"]) > 0)
						$output .= "fw: ".$devmod[$idx][$i]["fwver"];

					$output .= "</td><td>";

					if (strlen($devmod[$idx][$i]["swver"]) > 0)
						$output .= "sw: ".$devmod[$idx][$i]["swver"];

					$output .= "</td><td>";

					if (strlen($devmod[$idx][$i]["serial"]) > 0)
						$output .= "serial: ".$devmod[$idx][$i]["serial"];

					$output .= "</td><td>";
					if (strlen($devmod[$idx][$i]["model"]) > 0)
						$output .= $devmod[$idx][$i]["model"];

					$output .= "</td></tr>";

					if ($idx != 0)
						$output .= $this->showDeviceModules($devmod,$devmod[$idx][$i]["idx"],$level-1);
				}
				return $output;
			}

			private function getDeviceSwitches($devmod,$idx) {
				if (!isset($devmod[$idx])) return "";
				$output = "";
				$count = count($devmod[$idx]);
				for($i=0;$i<$count;$i++) {
					$output .= $devmod[$idx][$i]["desc"];
					if ($i+1<$count) $output .= "/";
				}
				return $output;
			}

			private function showDeviceDiscovery() {
				$output = FS::$iMgr->cbkForm("18");
				$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->IPInput("dip","",20,40,"Adresse IP:");
				$output .= "</li><li>".FS::$iMgr->Submit("",$this->loc->s("Discover"))."</li>";
				$output .= "</ul></form>";
				return $output;
			}

			protected function showDeviceList() {
				$output = "";
				$err = FS::$secMgr->checkAndSecuriseGetData("err");
				switch($err) {
					case 1: $output .= FS::$iMgr->printError($this->loc->s("err-some-backup-fail")); break;
					case 2: $output .= FS::$iMgr->printError($this->loc->s("err-some-field-missing")); break;
					case 3: $output .= FS::$iMgr->printError($this->loc->s("err-no-rights")); break;
					case 99: $output .= FS::$iMgr->printError($this->loc->s("err-no-credentials")); break;
					case -1: $output .= FS::$iMgr->printSuccess($this->loc->s("done-with-success")); break;
					default: break;
				}

				$showtitle = true;
				if (FS::$sessMgr->hasRight("mrule_switches_discover")) {
					$showtitle = false;
					$output .= FS::$iMgr->h2("title-global-fct");
					$output .= FS::$iMgr->opendiv(1,$this->loc->s("Discover-device"),array("line" => true));
				}

				$foundsw = 0;
				$foundwif = 0;
				$outputswitch = "<table id=\"dev\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("MAC-addr")."</th><th>".
					$this->loc->s("Model")."</th><th>".$this->loc->s("OS")."</th><th>".$this->loc->s("Place")."</th><th>".$this->loc->s("Serialnb")."</th><th>".$this->loc->s("State")."</th></tr></thead>";

				$outputwifi = FS::$iMgr->h2("title-WiFi-AP");
				$outputwifi .= "<table id=\"dev2\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("Model")."</th><th>".
					$this->loc->s("OS")."</th><th>".$this->loc->s("Place")."</th><th>".$this->loc->s("Serialnb")."</th></tr></thead>";

				$query = FS::$dbMgr->Select("device","*","",array("order" => "name"));
				while($data = FS::$dbMgr->Fetch($query)) {
					// Rights: show only reading/writing switches
					$snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$data["name"]."'");
					$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$data["name"]."'");
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$data["ip"]."_read") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$data["ip"]."_write")) 
						continue;

					// Split WiFi and Switches
					if (preg_match("#AIR#",$data["model"])) {
						if ($foundwif == 0) $foundwif = 1;
						$outputwifi .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>";
						$outputwifi .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
					}
					else {
						if ($foundsw == 0) $foundsw = 1;
						$outputswitch .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>".$data["mac"]."</td><td>";
						$outputswitch .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td><td>
						<div id=\"st".preg_replace("#[.]#","-",$data["ip"])."\">".FS::$iMgr->img("styles/images/loader.gif",24,24)."</div>".
						FS::$iMgr->js("$.post('index.php?mod=".$this->mid."&act=19', { dip: '".$data["ip"]."' }, function(data) {
						$('#st".preg_replace("#[.]#","-",$data["ip"])."').html(data); });")."</td></tr>";
					}
				}
				if ($foundsw != 0 || $foundwif != 0) {
					if (FS::$sessMgr->hasRight("mrule_switches_globalsave") || FS::$sessMgr->hasRight("mrule_switches_globalbackup")) {
						if ($showtitle) $output .= FS::$iMgr->h2("title-global-fct");
						// Openable divs
						$output .= FS::$iMgr->opendiv(2,$this->loc->s("Advanced-Functions"));
					}
				}
				if ($foundsw != 0 || $foundwif != 0)
					$output .= FS::$iMgr->h2("title-router-switch");

				if ($foundsw != 0) {
					$output .= $outputswitch;
					$output .= "</table>";
					FS::$iMgr->jsSortTable("dev");
				}
				if ($foundwif != 0) {
					$output .= $outputwifi;
					$output .= "</table>";
					FS::$iMgr->jsSortTable("dev2");
				}
				if ($foundsw != 0 || $foundwif != 0) {
					$output .= FS::$iMgr->js("$.event.props.push('dataTransfer');
						$('#dev #draga').on({
							mouseover: function(e) { $('#trash').show(); },
							mouseleave: function(e) { $('#trash').hide(); },
							dragstart: function(e) { $('#trash').show(); e.dataTransfer.setData('text/html', $(this).text()); },
							dragenter: function(e) { e.preventDefault();},
							dragover: function(e) { e.preventDefault(); },
							dragleave: function(e) { },
							drop: function(e) {},
							dragend: function() { $('#trash').hide(); }
						});
						$('#trash').on({
							dragover: function(e) { e.preventDefault(); },
							drop: function(e) { $('#subpop').html('".$this->loc->s("sure-remove-device")." \''+e.dataTransfer.getData('text/html')+'\' ?".
								FS::$iMgr->form("index.php?mod=".$this->mid."&act=17").
								FS::$iMgr->hidden("device","'+e.dataTransfer.getData('text/html')+'").
								FS::$iMgr->submit("",$this->loc->s("Remove")).
								FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"unlockScreen()")."</form>');
								lockScreen();
							}
						});");
				}

				if ($foundsw == 0 && $foundwif == 0)
					$output .= FS::$iMgr->printError($this->loc->s("err-no-device2"));

				return $output;
			}

			private function showAdvancedFunctions() {
				$output = "";
				if (FS::$sessMgr->hasRight("mrule_switches_globalsave")) {
					// Write all devices button
					$output .= FS::$iMgr->cbkForm("20");
					$output .= FS::$iMgr->submit("sallsw",$this->loc->s("save-all-switches"),array("tooltip" => "tooltip-save"))."</form>";
				}
				if (FS::$sessMgr->hasRight("mrule_switches_globalbackup")) {
					$rightsok = true;
					// Backup all devices button
					$output .= FS::$iMgr->cbkForm("21");
					$output .= FS::$iMgr->submit("bkallsw",$this->loc->s("backup-all-switches"),array("tooltip" => "tooltip-backup"))."</form>";
				}
				return $output;
			}

			public function getIfaceElmt() {
				$el = FS::$secMgr->checkAndSecuriseGetData("el");
				switch($el) {
					case 1: return $this->showDeviceDiscovery();
					case 2: return $this->showAdvancedFunctions();
					default: return;
				}
			}

			public function handlePostDatas($act) {
				switch($act) {
					case 2: // Plug fast edit
						$port = FS::$secMgr->checkAndSecurisePostData("swport");
						$sw = FS::$secMgr->checkAndSecurisePostData("sw");
						$prise = FS::$secMgr->checkAndSecurisePostData("swprise");
						if ($port == NULL || $sw == NULL /*|| $prise != NULL && !preg_match("#^[A-Z][1-9]\.[1-9A-Z][0-9]?\.[1-9][0-9A-Z]?$#",$prise)*/) {
							$this->log(2,"Some fields are missing (plug fast edit)");
							echo "ERROR";
							return;
						}

						$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
							echo "NORIGHTS";
							return;	
						}

						if ($prise == NULL) $prise = "";
						// Modify Plug for switch port
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."switch_port_prises","ip = '".$sw."' AND port = '".$port."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."switch_port_prises","ip,port,prise","'".$sw."','".$port."','".$prise."'");

						// Return text for AJAX call
						$this->log(0,"Set plug for device '".$sw."' to '".$prise."' on port '".$port."'");
						if ($prise == "") $prise = "Modifier";
						echo $prise;
						return;
					case 3: // Desc fast edit
						$port = FS::$secMgr->checkAndSecurisePostData("swport");
						$sw = FS::$secMgr->checkAndSecurisePostData("sw");
						$desc = FS::$secMgr->checkAndSecurisePostData("swdesc");
						$save = FS::$secMgr->checkAndSecurisePostData("wr");
						if ($port == NULL || $sw == NULL || $desc == NULL) {
							$this->log(2,"Some fields are missing (desc fast edit)");
							echo "ERROR";
							return;
						}
						$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
						$this->devapi->setDevice($device);
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
							echo "NORIGHTS";
							return;	
						}
						if (FS::$dbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'") != NULL) {
							if ($this->devapi->setPortDesc($desc) == 0) {
								echo $desc;
								if ($save == "true")
									$this->devapi->writeMemory();
								FS::$dbMgr->Update("device_port","name = '".$desc."'","ip = '".$sw."' AND port = '".$port."'");
								$this->log(0,"Set description for '".$sw."' to '".$desc."' on port '".$port."'");
							}
							else {
								$this->log(1,"Failed to set description on device '".$sw."' and port .'".$port."'");
								echo "ERROR";
							}
						}
						return;
					case 5: // Duplex fast edit
						$port = FS::$secMgr->checkAndSecurisePostData("swport");
						$sw = FS::$secMgr->checkAndSecurisePostData("sw");
						$dup = FS::$secMgr->checkAndSecurisePostData("swdp");
						$save = FS::$secMgr->checkAndSecurisePostData("wr");
						if ($port == NULL || $sw == NULL || $dup == NULL) {
							$this->log(2,"Some fields are missing (duplex fast edit)");
							echo "ERROR";
							return;
						}
						$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
						$this->devapi->setDevice($device);
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
							echo "NORIGHTS";
							return;	
						}
						if (FS::$dbMgr->GetOneData("device_port","type","ip = '".$sw."' AND port = '".$port."'") != NULL) {
							if ($this->setPortDuplex($dup) == 0) {
								if ($save == "true")
									$this->devapi->writeMemory();

								$duplex = "auto";
								if ($dup == 1) $duplex = "half";
								else if ($dup == 2) $duplex = "full";

								FS::$dbMgr->Update("device_port","duplex_admin = '".$duplex."'","ip = '".$sw."' AND port = '".$port."'");
								$ldup = FS::$dbMgr->GetOneData("device_port","duplex","ip = '".$sw."' AND port = '".$port."'");
								$ldup = (strlen($ldup) > 0 ? $ldup : "[NA]");
								if ($ldup == "half" && $duplex != "half") $ldup = "<span style=\"color: red;\">".$ldup."</span>";
								echo "<span style=\"color:black;\">".$ldup." / ".$duplex."</span>";
							}
							else
									echo "ERROR";
						}
						return;
					case 9: // Switch Plug edit
						$sw = FS::$secMgr->checkAndSecurisePostData("sw");
						$port = FS::$secMgr->checkAndSecurisePostData("port");
						$desc = FS::$secMgr->checkAndSecurisePostData("desc");
						$prise = FS::$secMgr->checkAndSecurisePostData("prise");
						$room = FS::$secMgr->checkAndSecurisePostData("room");
						if ($port == NULL || $sw == NULL || !$this->devapi->checkFields()) {
							$this->log(2,"Some fields are missing (plug edit)");
							echo "Some fields are missing (port, switch, trunk or native vlan)";
							return;
						}

						$this->devapi->setDevice($sw);
						$dip = $this->devapi->getDeviceIP();

						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$sw."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo $this->loc->s("err-no-credentials");
							return;	
						}
						$pid = $this->devapi->getPortId($port);
						if ($pid == -1) {
							$this->log(2,"PID is incorrect (plug edit)");
							echo "PID is incorrect (".$pid.")";
							return;
						}
						$this->devapi->setPortId($pid);
	
						$logoutput = "Modify port '".$port."' on device '".$sw."'";
						$logvals = array();
						$idx = $this->devapi->getPortIndexes();
	
						$this->devapi->handleDuplex($logvals);
	
						$this->devapi->handleSpeed($logvals);
	
						$this->devapi->handleVlan($logvals);

						$this->devapi->handleState($logvals);

						if ($this->devapi->handleVoiceVlan($logvals) != 0)
							return;

						$logvals["desc"]["src"] = $this->devapi->getPortDesc();
						$this->devapi->setPortDesc($desc);
						$logvals["desc"]["dst"] = $desc;

						$this->devapi->handleCDP($logvals);

						$this->devapi->handleDHCPSnooping($logvals);

						$this->devapi->handlePortSecurity($logvals);

						$this->devapi->handleSaveCfg();

						if ($prise == NULL) $prise = "";
						if ($room == NULL) $room = "";
						FS::$dbMgr->BeginTr();
						FS::$dbMgr->Delete("z_eye_switch_port_prises","ip = '".$dip."' AND port = '".$port."'");
						FS::$dbMgr->Insert("z_eye_switch_port_prises","ip,port,prise,room","'".$dip."','".$port."','".$prise."','".$room."'");
	
						FS::$dbMgr->Update("device_port","name = '".$desc."'","ip = '".$dip."' AND port = '".$port."'");
						FS::$dbMgr->CommitTr();
	
						foreach($logvals as $keys => $values) {
							if (is_array($values["src"]) || isset($values["dst"]) && is_array($values["dst"])) {
								if (count(array_diff($values["src"],$values["dst"])) != 0) {
									$logoutput .= "\n".$keys.": ";
									$count = count($values["src"]);
									for($i=0;$i<$count;$i++) $logoutput .= $values["src"][$i].",";
									$logoutput .= " => ";
									$count = count($values["dst"]);
									for($i=0;$i<$count;$i++) $logoutput .= $values["dst"][$i].",";
								}
							}
							else if (isset($values["dst"]) && $values["src"] != $values["dst"]) {
								$logoutput .= "\n".$keys.": ".$values["src"]." => ".$values["dst"];
							}
						}
						$this->log(0,$logoutput);
						if (FS::isAjaxCall())
							echo $this->loc->s("done-with-success");
						else
							FS::$iMgr->redir("mod=".$this->mid."&d=".$sw."&p=".$port);
						return;
					case 10: // replace vlan portlist
						echo FS::$iMgr->h3("title-port-modiflist");
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$vlan = FS::$secMgr->checkAndSecuriseGetData("vlan");
						if (!$device) {
							$this->log(2,"Some fields are missing (vlan replacement, portlist)");
							echo FS::$iMgr->printError($this->loc->s("err-no-device"));
							return;
						}

						$this->devapi->setDevice($device);
						$dip = $this->devapi->getDeviceIP();
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
							return;	
						}
	
						if (!$vlan || !FS::$secMgr->isNumeric($vlan)) {
							$this->log(2,"Some fields are missing/wrong (vlan replacement, portlist)");
							echo FS::$iMgr->printError($this->loc->s("err-vlan-fail")." !");
							return;
						}
	
						$plist = $this->devapi->getPortList($vlan);
						$count = count($plist);
						if ($count > 0) {
							echo "<ul>";
							for($i=0;$i<$count;$i++)
								echo "<li>".$plist[$i]."</li>";
							echo "</ul>";
						}
						else
							FS::$iMgr->printError($this->loc->s("err-vlan-not-on-device"));
						return;
					case 11: // Vlan replacement
						$old = FS::$secMgr->checkAndSecurisePostData("oldvl");
						$new = FS::$secMgr->checkAndSecurisePostData("newvl");
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						if (!$device || !$old || !$new || !FS::$secMgr->isNumeric($old) || !FS::$secMgr->isNumeric($new) || $old > 4096 || $new > 4096 || $old < 0 || $new < 0) {
							$this->log(2,"Some fields are missing/wrong (vlan replacement)");
							FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=4&err=1");
							return;
						}

						$this->devapi->setDevice($device);
						$dip = $this->devip->getDeviceIP();
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&err=99");
							return;	
						}
						$this->log(0,"Replace VLAN '".$old."' by '".$new."' on device '".$device."'");
						$this->devapi->replaceVlan($old,$new);
						FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=4");
						return;
					/*
					* Backup startup-config
					*/
					case 12:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$trmode = FS::$secMgr->checkAndSecurisePostData("exportm");
						$sip =  FS::$secMgr->checkAndSecurisePostData("srvip");
						$filename = FS::$secMgr->checkAndSecurisePostData("srvfilename");
						$io = FS::$secMgr->checkAndSecurisePostData("io");
						if (!$device || !$trmode || ($trmode != 1 && $trmode != 2 && $trmode != 4 && $trmode != 5) ||
							!$sip || !FS::$secMgr->isIP($sip) || !$filename || strlen($filename) == 0 || !$io || ($io != 1 && $io != 2)) {
							$this->log(2,"Some fields are missing/wrong (backup statup-config)");
							echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
							return;
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
							return;	
						}
						if ($trmode == 2 || $trmode == 4 || $trmode == 5) {
							$username = FS::$secMgr->checkAndSecurisePostData("srvuser");
							$password = FS::$secMgr->checkAndSecurisePostData("srvpwd");
							if (!$username || $username == "" || !$password || $password == "") {
								$this->log(2,"Some fields are missing/wrong (backup startup-confi)");
								echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
								return;
							}
							if ($io == 1) {
								$this->log(0,"Export '".$device."' config to '".$sip."':'".$filename."'");
								echo $this->devapi->exportConfigToAuthServer($device,$sip,$trmode,$filename,$username,$password);
							}
							else if ($io == 2) {
								$this->log(0,"Import '".$device."' config from '".$sip."':'".$filename."'");
								echo $this->devapi->importConfigFromAuthServer($device,$sip,$trmode,$filename,$username,$password);
							}
						}
						else if ($trmode == 1) {
							if ($io == 1) {
								$this->log(0,"Export '".$device."' config to '".$sip."':'".$filename."'");
								echo  $this->devapi->exportConfigToTFTP($device,$sip,$filename);
							}
							else {
								$this->log(0,"Import '".$device."' config from '".$sip."':'".$filename."'");
								echo  $this->devapi->importConfigFromTFTP($device,$sip,$filename);
							}
						} else {
							$this->log(2,"Invalid export type '".$trmode."'");
							FS::$iMgr->printError($this->loc->s("err-invalid-export"));
						}
						return;
					/*
					* Verify backup state
					*/
					case 13:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
						if (!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
							$this->log(2,"Some fields are missing/wrong (verify backup state)");
							echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
							return;
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
							return;	
						}
						echo $this->devapi->getCopyState($saveid);
						return;
					case 14:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
						if (!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
							$this->log(2,"Some fields are missing/wrong (verify backup error)");
							echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
							return;
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
							return;	
						}
						$err = $this->devapi->getCopyError($saveid);
						switch($err) {
							case 2: echo $this->loc->s("err-transfer-right"); break;
							case 3: echo $this->loc->s("err-transfer-timeout"); break;
							case 4: echo $this->loc->s("err-transfer-no-mem"); break;
							case 5: echo $this->loc->s("err-transfer-src"); break;
							case 6: echo $this->loc->s("err-transfer-protocol"); break;
							case 7: echo $this->loc->s("err-transfer-apply"); break;
							case 8: echo $this->loc->s("err-transfer-not-ready"); break;
							case 9: echo $this->loc->s("err-transfer-abandonned"); break;
							default: echo $this->loc->s("err-transfer-unk"); break;
						}
						return;
					/*
					* Restore startup-config
					*/
					case 15:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						if (!$device) {
							$this->log(2,"Some fields are missing/wrong (restore startup-config)");
							echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
							return;
						}
						$this->devapi->setDevice($device);
						$dip = $this->devapi->getDeviceIP();
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
							return;	
						}
						$this->log(0,"Launch restore startup-config for device '".$device."'");
						echo $this->devapi->restoreStartupConfig();
						return;
					// Port monitoring
					case 16:
						$device = FS::$secMgr->checkAndSecurisePostData("device");
						$port = FS::$secMgr->checkAndSecurisePostData("port");
						$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
						$climit = FS::$secMgr->checkAndSecurisePostData("climit");
						$wlimit = FS::$secMgr->checkAndSecurisePostData("wlimit");
						$desc = FS::$secMgr->checkAndSecurisePostData("desc");
						if (!$device || !$port) {
							$this->log(2,"Some fields are missing (port monitoring)");
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
							return;
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon")) {
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
							return;	
						}
	
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						if (!$dip) {
							$this->log(2,"Bad device '".$device."' (port monitoring)");
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
							return;
						}
	
						$dport = FS::$dbMgr->GetOneData("device_port","name","ip = '".$dip."' AND port = '".$port."'");
						if (!$dport) {
							$this->log(2,"Bad port '".$dport."' for device '".$dip."' (port monitoring)");
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
							return;
						}
						if ($enmon == "on") {
							if (!$climit || !$wlimit || !FS::$secMgr->isNumeric($wlimit) || !FS::$secMgr->isNumeric($climit) || $climit <= 0 || $wlimit <= 0) {
								$this->log(2,"Some fields are missing/wrong (port monitoring)");
								FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&p=".$port."&sh=3&err=2");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."port_monitor","device = '".$device."' AND port = '".$port."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."port_monitor","device,port,climit,wlimit,description","'".$device."','".$port."','".$climit."','".$wlimit."','".$desc."'");
						}
						else {
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."port_monitor","device = '".$device."' AND port = '".$port."'");
						}
						$this->log(0,"Port monitoring for device '".$device."' and port '".$dport."' edited. Enabled: ".($enmon == "on" ? "yes" : "no").
							" wlimit: ".$wlimit." climit: ".$climit." desc: '".$desc."'");
						FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&p=".$port."&sh=3");
						return;
					case 17: // device cleanup
						$device = FS::$secMgr->checkAndSecurisePostData("device");
						if (!$device) {
							$this->log(2,"Some fields are missing (Device cleanup)");
							FS::$iMgr->redir("mod=".$this->mid."&err=1");
							return;
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
							return;	
						}
						$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");

						FS::$dbMgr->BeginTr();
						FS::$dbMgr->Delete("device_ip","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_module","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_port","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_port_power","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_port_wireless","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_port_ssid","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_port_vlan","ip = '".$dip."'");
						FS::$dbMgr->Delete("device_power","ip = '".$dip."'");
						FS::$dbMgr->Delete("node","switch = '".$dip."'");
						FS::$dbMgr->Delete("node_ip","ip = '".$dip."'");
						FS::$dbMgr->Delete("node_nbt","ip = '".$dip."'");
						FS::$dbMgr->Delete("admin","device = '".$dip."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."port_id_cache","device = '".$device."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."port_monitor","device = '".$device."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."switch_port_prises","ip = '".$dip."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snmp_cache","device = '".$device."'");
						FS::$dbMgr->Delete("device","ip = '".$dip."'");
						FS::$dbMgr->CommitTr();

						$this->log(0,"Remove device '".$device."' from Z-Eye");
						FS::$iMgr->redir("mod=".$this->mid);
					case 18: // Device discovery
						if (!FS::$sessMgr->hasRight("mrule_switches_discover")) {
							$this->log(2,"User ".FS::$sessMgr->getUserName()." wants to discover a device !");
							FS::$iMgr->ajaxEcho("err-no-rights");
							return;
						}
						$dip = FS::$secMgr->getPost("dip","i4");
						if (!$dip) {
							$this->log(2,"Some fields are missing (device discovery)");
							FS::$iMgr->ajaxEcho("err-bad-datas");
							return;
						}
						exec("/usr/local/bin/netdisco -d ".$dip);

						$devro = "";
						$devrw = "";
						$snmpro = array();
						$snmprw = array();

						loadNetdiscoCommunities($snmpro,$snmprw);
						$devname = FS::$dbMgr->GetOneData("device","name","ip = '".$dip."'");

						$foundro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$devname."'");
						$foundrw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$devname."'");
						if ($foundro && checkSnmp($dip,$foundro) == 0)
							$devro = $foundro;
						if ($foundrw && checkSnmp($dip,$foundrw) == 0)
							$devrw = $foundrw;

						for($i=0;$i<count($snmpro) && $devro == "";$i++) {
							if (checkSnmp($dip,$snmpro[$i]) == 0)
							       $devro = $snmpro[$i];
						}

						for($i=0;$i<count($snmprw) && $devrw == "";$i++) {
							if (checkSnmp($dip,$snmprw[$i]) == 0)
								$devrw = $snmprw[$i];
						}
						if ($foundro != $devro && strlen($devro) > 0 || $foundrw != $devrw && strlen($devrw) > 0)
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snmp_cache","device,snmpro,snmprw","'".$devname."','".$devro."','".$devrw."'");
						$this->log(0,"Launch discovering for device '".$dip."'");
						FS::$iMgr->redir("mod=".$this->mid,true);
						return;
					case 19: // device status
						$dip = FS::$secMgr->getPost("dip","i4");
						if (!$dip) {
							$this->log(2,"Some fields are missing (AJAX device status)");
							echo "<span style=\"color:red\">IP Error ".$dip."</span>";
							return;
						}
						$out = "";
						exec("ping -W 100 -c 1 ".$dip." | grep ttl | wc -l|awk '{print $1}'",$out);
						if (!is_array($out) || count($out) > 1)
							echo "<span style=\"color:red;\">".$this->loc->s("err-output")." ".var_dump($out)."</span>";
						else if ($out[0] > 1)
							echo "<span style=\"color:red;\">".$this->loc->s("err-output-value")." ".$out."</span>";
						else if ($out[0] == 0)
							echo "<span style=\"color:red;\">".$this->loc->s("Offline")."</span>";
						else if ($out[0] == 1)
							echo "<span style=\"color:green;\">".$this->loc->s("Online")."</span>";
						return;
					case 20: // Save all devices
						if (!FS::$sessMgr->hasRight("mrule_switches_globalsave")) {
							$this->log(2,"User ".FS::$sessMgr->getUserName()." wants to save all devices !");
							FS::$iMgr->redir("mod=".$this->mid."&err=3");
							return;
						}
						$query = FS::$dbMgr->Select("device","name,vendor");
						while($data = FS::$dbMgr->Fetch($query)) {
							$this->vendor = $data["vendor"];
							switch($this->vendor) {
								case "cisco": $this->devapi = new CiscoAPI(); break;
								case "dell": $this->devapi = new DellAPI(); break;
								default: $this->devapi = new DeviceAPI(); break;
							}
							$this->devapi->setDevice($data["name"]);
							$this->devapi->writeMemory();
						}
						
						$this->log(0,"User ".FS::$sessMgr->getUserName()." saved all devices");
						if (FS::isAjaxCall())
							echo $this->loc->s("saveorder-terminated");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=-1");
						return;
					case 21: // Backup all devices
						if (!FS::$sessMgr->hasRight("mrule_switches_globalbackup")) {
							$this->log(2,"User ".FS::$sessMgr->getUserName()." wants to backup all devices !");
							FS::$iMgr->redir("mod=".$this->mid."&err=3");
							return;
						}
						$output = "";
						$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login,pwd");
						while($data = FS::$dbMgr->Fetch($query)) {
							if (!FS::$secMgr->isIP($data["addr"]))
								continue;
								
							$query2 = FS::$dbMgr->Select("device","ip,name,vendor");
							while($data2 = FS::$dbMgr->Fetch($query2)) {
								$this->vendor = $data2["vendor"];
								switch($this->vendor) {
									case "cisco": $this->devapi = new CiscoAPI(); break;
									case "dell": $this->devapi = new DellAPI(); break;
									default: $this->devapi = new DeviceAPI(); break;
								}
								$this->devapi->setDevice($data2["name"]);
								if ($data["type"] == 1)
									$copyId = $this->devapi->exportConfigToTFTP($data["addr"],$data["path"]."conf-".$data2["name"]);
								else if ($data["type"] == 2 || $data["type"] == 4 || $data["type"] == 5)
									$copyId = $this->devapi->exportConfigToAuthServer($data["addr"],$data["type"],$data["path"]."conf-".$data2["name"],$data["login"],$data["pwd"]);
								
								sleep(1);
								$copyState = $this->devapi->getCopyState($copyId);
								while($copyState == 2) {
									sleep(1);
									$copyState = $this->devapi->getCopyState($copyId);
								}
								
								if ($copyState == 4) {
									$copyErr = $this->devapi->getCopyError($copyId);
									$output .= "Backup fail for device ".$data2["name"]." (reason: ";
									switch($copyErr) {
										case 2: $output .= "bad filename/path/rights"; break;
										case 3: $output .= "timeout"; break;
										case 4: $output .= "no memory available"; break;
										case 5: $output .= "config error"; break;
										case 6: $output .= "unsupported protocol"; break;
										case 7:	$output .= "config apply fail"; break;
										default: $output .= "unknown"; break;
									}
									$output .= ")<br />";
								}
							}
						}
						if (FS::isAjaxCall()) {
							if (strlen($output) > 0) {
								$this->log(1,"Some devices cannot be backup: ".$output);
								echo $this->loc->s("err-thereis-errors")."<br />".$output;
							}
							else {
								$this->log(0,"User ".FS::$sessMgr->getUserName()." backup all devices");
								echo $this->loc->s("backuporder-terminated");
							}
						}
						else {
							if (strlen($output) > 0) {
								$this->log(1,"Some devices cannot be backup: ".$output);
								FS::$iMgr->redir("mod=".$this->mid."&err=1");
							}
							else {
								$this->log(0,"User ".FS::$sessMgr->getUserName()." backup all devices");
								FS::$iMgr->redir("mod=".$this->mid);
							}
						}
						return;
					case 22: // SSH pwd set
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$dip = "";	
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!$device || !($dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'"))) {
							if (FS::isAjaxCall())
								echo $this->loc->s("err-bad-datas");
							else
								FS::$iMgr->redir("mod=".$this->mid."&err=2");
							return;
						}

						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_sshpwd") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshpwd")) {
							if (FS::isAjaxCall())
								echo $this->loc->s("err-no-rights");
							else
								FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=1");
							return;
						}
							
						$sshuser = FS::$secMgr->checkAndSecurisePostData("sshuser");
						$sshpwd = FS::$secMgr->checkAndSecurisePostData("sshpwd");
						$sshpwd2 = FS::$secMgr->checkAndSecurisePostData("sshpwd2");
						$enablepwd = FS::$secMgr->checkAndSecurisePostData("enablepwd");
						$enablepwd2 = FS::$secMgr->checkAndSecurisePostData("enablepwd2");
						if (!$sshuser || !$sshpwd || !$sshpwd2 || !$enablepwd || !$enablepwd2) {
							if (FS::isAjaxCall())
								echo $this->loc->s("err-bad-datas");
							else
								FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=2");
							return;
						}

						if ($sshpwd != $sshpwd2 || $enablepwd != $enablepwd2) {
							if (FS::isAjaxCall())
								echo $this->loc->s("err-pwd-mismatch");
							else
								FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=3");
							return;
						}

						if ($sshpwd != $sshpwd2 || $enablepwd != $enablepwd2) {
							if (FS::isAjaxCall())
								echo $this->loc->s("err-pwd-mismatch");
							else
								FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=4");
							return;
						}
					
						$res = $this->devapi->connectToDevice($dip,$sshuser,$sshpwd,$enablepwd);
						switch($res) {
							case 1:
								if (FS::isAjaxCall())
									echo $this->loc->s("err-conn-fail");
								else
									FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=5");
								return;
							case 2:
								if (FS::isAjaxCall())
									echo $this->loc->s("err-auth-fail");
								else
									FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=6");
								return;
							case 3:
								if (FS::isAjaxCall())
									echo $this->loc->s("err-enable-auth-fail");
								else
									FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=7");
								return;
						}
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."switch_pwd","device = '".$device."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."switch_pwd","device,sshuser,sshpwd,enablepwd","'".$device."','".$sshuser."','".base64_encode($sshpwd)."','".
							base64_encode($enablepwd)."'");
						FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7",true);
						return;
					// Remove SSH link
					case 23:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$dip = "";	
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!$device || !($dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'"))) {
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
							return;
						}

						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_sshpwd") && 
							!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_sshpwd")) {
							FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7&err=1");
							return;
						}
							
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."switch_pwd","device = '".$device."'");
						FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=7");
						return;
					// Modify DHCP Snooping (switch)
					case 24:
						$device = FS::$secMgr->checkAndSecuriseGetData("d");
						$dip = "";	
						$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
						if (!$device || !($dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'"))) {
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
							return;
						}

						if (!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_dhcpsnmgmt") &&
							!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_dhcpsnmgmt")) { 
							FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&err=2");
							return;
						}

						$enable = FS::$secMgr->checkAndSecurisePostData("enable");
						$opt82 = FS::$secMgr->checkAndSecurisePostData("opt82");
						$matchmac = FS::$secMgr->checkAndSecurisePostData("matchmac");
						$vlans = FS::$secMgr->checkAndSecurisePostData("vlansnooping");

						if ($vlans && !is_array($vlans)) {
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
							return;
						}

						$vlanlist = $this->devapi->getDHCPSnoopingVlans();
						foreach($vlanlist as $vlan => $value)
							$vlanlist[$vlan] = 2;
						$count = count($vlans);
						for($i=0;$i<$count;$i++) {
							if (!FS::$secMgr->isNumeric($vlans[$i])) {
								FS::$iMgr->redir("mod=".$this->mid."&err=2");
								return;
							}
							$vlanlist[$vlans[$i]] = 1;
						}
								
						$this->devapi->setDHCPSnoopingStatus($enable == "on" ? 1 : 2);
						$this->devapi->setDHCPSnoopingOpt82($opt82 == "on" ? 1 : 2);
						$this->devapi->setDHCPSnoopingMatchMAC($matchmac == "on" ? 1 : 2);
						$this->devapi->setDHCPSnoopingVlans($vlanlist);

						FS::$iMgr->redir("mod=".$this->mid."&d=".$device."&sh=4");
						return;
				default: break;
			}
		}
		private $devapi;
		private $vendor;
	};
?>
