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
	require_once(dirname(__FILE__)."/snmpdiscovery.api.php");
	$device = FS::$secMgr->checkAndSecuriseGetData("d");
        if($device) {
		$vendor = FS::$dbMgr->GetOneData("device","vendor","name = '".$device."'");
		switch($vendor) {
			case "cisco": require_once(dirname(__FILE__)."/cisco.func.php"); break;
			case "dell": require_once(dirname(__FILE__)."/dell.func.php"); break;
			default: break;
		}
        }
	
	class iSwitchMgmt extends genModule{
		function iSwitchMgmt() { parent::genModule(); $this->loc = new lSwitchMgmt(); }

		public function Load() {
			FS::$iMgr->setCurrentModule($this);
			return $this->showMain();
		}

		private function showMain() {
			$output = "";
			if(!FS::isAjaxCall())
				$output .= "<h1>".$this->loc->s("title-network-device-mgmt")."</h1>";

			$count = FS::$dbMgr->Count("z_eye_snmp_communities","name");
                        if($count < 1) {
                                $output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
                                        "<br /><br /><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2\">".$this->loc->s("Go")."</a>");
                                return $output;
                        }

			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$port = FS::$secMgr->checkAndSecuriseGetData("p");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			if($port != NULL && $device != NULL)
				$output .= $this->showPortInfos();
			else if($device != NULL)
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
			$snmpro = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
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
			if(!FS::isAjaxCall()) {
				$output .= "<h2>".$port." sur ".$device."</h2>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("Configuration"),$sh);
				if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readportstats") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readportstats"))
					$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("bw-stats"),$sh);
				if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") ||
					FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") || FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon"))
					$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid."&d=".$device."&p=".$port,$this->loc->s("Monitoring"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("err-fail-tab")."\");}}});</script>";
				$output .= "</div>";
			} else {
				// Get Port ID
				$portid = getPortId($device,$port);
				// Port modification
				if(!$sh || $sh == 1) {
					$output .= "<script type=\"text/javascript\">function arangeform() {";
					$output .= "if(document.getElementsByName('trmode')[0].value == 1) {";
					$output .= "$('#vltr').show('slow');
						if(!$('#mabtr').is(':hidden')) $('#mabtr').hide('slow');
						if(!$('#mabdead').is(':hidden')) $('#mabdead').hide('slow');
						if(!$('#mabnoresp').is(':hidden')) $('#mabnoresp').hide('slow');
						$('#vllabel').html('".$this->loc->s("native-vlan")."');
					} else if(document.getElementsByName('trmode')[0].value == 2) {
						if(!$('#vltr').is(':hidden')) $('#vltr').hide('slow');
						if(!$('#mabtr').is(':hidden')) $('#mabtr').hide('slow');
						if(!$('#mabdead').is(':hidden')) $('#mabdead').hide('slow');
						if(!$('#mabnoresp').is(':hidden')) $('#mabnoresp').hide('slow');
						$('#vllabel').html('".$this->loc->s("Vlan")."');
					} else if(document.getElementsByName('trmode')[0].value == 3) {
						if(!$('#vltr').is(':hidden')) $('#vltr').hide('slow');
						$('#vllabel').html('".$this->loc->s("fail-vlan")."');
						if($('#mabtr').is(':hidden')) $('#mabtr').show('slow');
						if($('#mabdead').is(':hidden')) $('#mabdead').show('slow');
						if($('#mabnoresp').is(':hidden')) $('#mabnoresp').show('slow');
					}";
					$output .= "};";
					$output .= "</script>";
					$query = FS::$dbMgr->Select("device_port","name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."' AND port ='".$port."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						if($portid != -1 && (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") ||
							FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write"))) {
							$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=9",array("id" => "swpomod"));
							$output .= FS::$iMgr->hidden("portid",$portid);
							$output .= FS::$iMgr->hidden("sw",$device);
							$output .= FS::$iMgr->hidden("port",$port);
						}
						$output .= "<table><tr><th>".$this->loc->s("Field")."</th><th>".$this->loc->s("Value")."</th></tr>";
						$output .= FS::$iMgr->idxLine($this->loc->s("Description"),"desc",$data["name"],array("tooltip" => "tooltip-desc"));
						$piece = FS::$dbMgr->GetOneData("z_eye_switch_port_prises","prise","ip = '".$dip."' AND port = '".$port."'");
						$output .= FS::$iMgr->idxLine($this->loc->s("Plug"),"prise",$piece,array("tooltip" => "tooltip-plug"));
						$output .= "<tr><td>".$this->loc->s("MAC-addr")."</td><td>".$data["mac"]."</td></tr>";
						$mtu = getPortMtuWithPID($device,$portid);
						$output .= "<tr><td>".$this->loc->s("State")." / ".$this->loc->s("Speed")." / ".$this->loc->s("Duplex").($mtu != -1 ? " / ".$this->loc->s("MTU") : "")."</td><td>";
						if($data["up_admin"] == "down")
								$output .= "<span style=\"color: red;\">".$this->loc->s("Shut")."</span>";
						else if($data["up_admin"] == "up" && $data["up"] == "down")
							$output .= "<span style=\"color: orange;\">".$this->loc->s("Inactive")."</span>";
						else if($data["up"] == "up")
							$output .= "<span style=\"color: black;\">".$this->loc->s("Active")."</span>";
						else
							$output .= "unk";
						$output .= " / ".$data["speed"]." / ".($data["duplex"] == "" ? "[NA]" : $data["duplex"]).($mtu != -1 ? " / ".$mtu : "")."</td></tr>";
						$output .= "<tr><td>".$this->loc->s("Shutdown")."</td><td>".FS::$iMgr->check("shut",array("check" => $data["up_admin"] == "down" ? true : false, "tooltip" => "tooltip-shut"))."</td></tr>";
						$output .= "<tr><td>".$this->loc->s("admin-speed")."</td><td>";
                                                $sp = getPortSpeedWithPID($device,$portid);
						if($sp > 0) {
							$output .= FS::$iMgr->select("speed","",null,false,array("tooltip" => "tooltip-speed"));
							$output .= FS::$iMgr->selElmt("Auto",1,$sp == 1 ? true : false);
							if(preg_match("#Ethernet#",$port)) {
								$output .= FS::$iMgr->selElmt("10 Mbits",10000000,$sp == 10000000 ? true : false);
								if(preg_match("#FastEthernet#",$port))
									$output .= FS::$iMgr->selElmt("100 Mbits",100000000,$sp == 100000000 ? true : false);
								if(preg_match("#GigabitEthernet#",$port)) {
									$output .= FS::$iMgr->selElmt("100 Mbits",100000000,$sp == 100000000 ? true : false);
									$output .= FS::$iMgr->selElmt("1 Gbit",1000000000,$sp == 1000000000 ? true : false);
									if(preg_match("#TenGigabitEthernet#",$port))
										$output .= FS::$iMgr->selElmt("10 Gbits",10,$sp == 10 ? true : false);
								}
							}
							$output .= "</select>";
						}
						else
							$output .= $this->loc->s("Unavailable");
						$output .= "</td></tr>";
						$dup = getPortDuplexWithPID($device,$portid);
						if($dup != -1) {
							$output .= "<tr><td>".$this->loc->s("admin-duplex")."</td><td>";
							if($dup > 0 && $dup < 5) {
								$output .= FS::$iMgr->select("duplex");
								$output .= FS::$iMgr->selElmt("Auto",4,$dup == 1 ? true : false);
								$output .= FS::$iMgr->selElmt("Half",1,$dup == 2 ? true : false);
								$output .= FS::$iMgr->selElmt("Full",2,$dup == 3 ? true : false);
								$output .= "</select>";
							}
							else
								$output .= $this->loc->s("Unavailable");
						}
						$output .= "</td></tr>";
						$output .= "<tr><td>".$this->loc->s("switchport-mode")."</td><td>";
						$trmode = getSwitchportModeWithPID($device,$portid);

						$mabstate = getSwitchportMABState($device,$portid);
						if($mabstate == 1)
							$trmode = 3;
						$output .= FS::$iMgr->select("trmode","arangeform()");
						$output .= FS::$iMgr->selElmt("Access",2,$trmode == 2 ? true : false);
						$output .= FS::$iMgr->selElmt("Trunk",1,$trmode == 1 ? true : false);
						if($mabstate != -1)
							$output .= FS::$iMgr->selElmt("802.1X - MAB",3,$trmode == 3 ? true : false);
						$output .= "</select>";
						$output .= "<tr><td id=\"vllabel\">";
						$portoptlabel = "";
						$nvlan = 1;
						$vllist = array();
						switch($trmode) {
							case 1:
								$output .= $this->loc->s("native-vlan");
								$portoptlabel = $this->loc->s("encap-vlan");
								$nvlan = getSwitchTrunkNativeVlanWithPID($device,$portid);
								$vllist = getSwitchportTrunkVlansWithPid($device,$portid);
								break;
							case 2:
								$output .= $this->loc->s("Vlan");
								$nvlan = getSwitchAccessVLANWithPID($device,$portid);
								break;
							case 3:
								$output .= $this->loc->s("fail-vlan");
								$portoptlabel = $this->loc->s("MAB-opt");
								$nvlan = getSwitchportAuthFailVLAN($device,$portid);
								break;
						}
						$output .= "</td><td id=\"vln\">";
						$voicevlanoutput = FS::$iMgr->selElmt($this->loc->s("None"),4096);
						$voicevlan = getSwitchportVoiceVlanWithPID($device,$portid);
						$output .= FS::$iMgr->select("nvlan","");
						// Added none for VLAN fail
						if($trmode == 3)
							$output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$nvlan == 0 ? true : false);

						$deadvlan = getSwitchportAuthDeadVLAN($device,$portid);
						$deadvlanoutput = "";
						$norespvlan = getSwitchportAuthNoRespVLAN($device,$portid);
						$norespvlanoutput = "";
						$trunkvlanoutput = "";
						$trunkall = true;
						$vlannb = 0;

						$query = FS::$dbMgr->Select("device_vlan","vlan,description,creation","ip = '".$dip."'","vlan");
				                while($data = FS::$dbMgr->Fetch($query)) {
							$output .= FS::$iMgr->selElmt($data["vlan"]." - ".$data["description"],$data["vlan"],$nvlan == $data["vlan"] ? true : false);
							$voicevlanoutput .= FS::$iMgr->selElmt($data["vlan"]." - ".$data["description"],$data["vlan"],$voicevlan == $data["vlan"] ? true : false);
							$deadvlanoutput .= FS::$iMgr->selElmt($data["vlan"]." - ".$data["description"],$data["vlan"],$deadvlan == $data["vlan"] ? true : false);
							$norespvlanoutput .= FS::$iMgr->selElmt($data["vlan"]." - ".$data["description"],$data["vlan"],$norespvlan == $data["vlan"] ? true : false);
							$trunkvlanoutput .= FS::$iMgr->selElmt($data["vlan"]." - ".$data["description"],$data["vlan"],in_array($data["vlan"],$vllist) ? true : false);
							if($trunkall && in_array($data["vlan"],$vllist)) $trunkall = false;
							$vlannb++;
			                        }
						$output .= "</select></td></tr>";
						$output .= "<tr id=\"vltr\" ".($trmode != 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("encap-vlan")."</td><td>";
						$output .= FS::$iMgr->select("vllist[]","",NULL,true,array("size" => round($vlannb/4)));
			                        $output .= FS::$iMgr->selElmt($this->loc->s("All"),"all",$trunkall);
						$output .= $trunkvlanoutput;
						$output .= "</select>";
						$output .= "</td></tr>";
						/*
						* MAB tables
						*/

						// NoResp Vlan
                                                $output .= "<tr id=\"mabnoresp\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-noresp")."</td><td>";
                                                $output .= FS::$iMgr->select("norespvlan","",NULL,false,array("tooltip" => "MAB-noresp-tooltip"));
                                                $output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$norespvlan == 0 ? true : false);
                                                $output .= $norespvlanoutput;
                                                $output .= "</select></td></tr>";
						// Dead Vlan
						$output .= "<tr id=\"mabdead\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-dead")."</td><td>";
						$output .= FS::$iMgr->select("deadvlan","",NULL,false,array("tooltip" => "MAB-dead-tooltip"));
						$output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$deadvlan == 0 ? true : false);
						$output .= $deadvlanoutput;
						$output .= "</select></td></tr>";
						// Other options
						$output .= "<tr id=\"mabtr\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-opt")."</td><td>";
						$mabeap = getSwitchportMABType($device,$portid);
						$dot1xhostmode = getSwitchportAuthHostMode($device,$portid);
						$output .= FS::$iMgr->check("mabeap",array("check" => ($mabeap == 2 ? true : false)))." EAP<br />";
						$output .= $this->loc->s("Dot1x-hostm")." ".FS::$iMgr->select("dot1xhostmode","");
						$output .= FS::$iMgr->selElmt($this->loc->s("single-host"),1,$dot1xhostmode == 1 ? true : false);
						$output .= FS::$iMgr->selElmt($this->loc->s("multi-host"),2,$dot1xhostmode == 2 ? true : false);
						$output .= FS::$iMgr->selElmt($this->loc->s("multi-auth"),3,$dot1xhostmode == 3 ? true : false);
						$output .= FS::$iMgr->selElmt($this->loc->s("multi-domain"),4,$dot1xhostmode == 4 ? true : false);
						$output .= "</select>";
						/*
						* Voice vlan
						*/
						$output .= "</td></tr><tr><td>".$this->loc->s("voice-vlan")."</td><td>";
						$output .= FS::$iMgr->select("voicevlan","",null,false,array("tooltip" => "tooltip-voicevlan"));
						$output .= $voicevlanoutput;
						$output .= "</select></td></tr>";
						$portsecen = getPortSecEnableWithPID($device,$portid);
                                                if($portsecen != -1) {
							$output .= "<tr><td colspan=\"2\">".$this->loc->s("portsecurity")."</td></tr>";
							// check for enable/disable PortSecurity
							$output .= "<tr><td>".$this->loc->s("portsec-enable")."</td><td>".FS::$iMgr->check("psen",array("check" => $portsecen == 1 ? true : false))."</td></tr>";
							// Active Status for PortSecurity
							$output .= "<tr><td>".$this->loc->s("portsec-status")."</td><td>";
                                                        $portsecstatus = getPortSecStatusWithPID($device,$portid);
							switch($portsecstatus) {
								case 1: $output .= $this->loc->s("Active"); break;
								case 2: $output .= $this->loc->s("Inactive"); break;
								case 3: $output .= "<span style=\"color:red;\">".$this->loc->s("Violation")."</span>"; break;
								default: $output .= $this->loc->s("unk"); break;
							}
                                                        $output .= "</td></tr>";
							// Action when violation is performed
							$psviolact = getPortSecViolActWithPID($device,$portid);
							$output .= "<tr><td>".$this->loc->s("portsec-violmode")."</td><td>".FS::$iMgr->select("psviolact","",NULL,false,array("tooltip" => "portsec-viol-tooltip"));
							$output .= FS::$iMgr->selElmt($this->loc->s("Shutdown"),1,$psviolact == 1 ? true : false);
							$output .= FS::$iMgr->selElmt($this->loc->s("Restrict"),2,$psviolact == 2 ? true : false);
							$output .= FS::$iMgr->selElmt($this->loc->s("Protect"),3,$psviolact == 3 ? true : false);
							$output .= "</select>";
							// Maximum MAC addresses before violation mode
							$psmaxmac = getPortSecMaxMACWithPID($device,$portid);
							$output .= "<tr><td>".$this->loc->s("portsec-maxmac")."</td><td>".FS::$iMgr->numInput("psmaxmac",$psmaxmac,array("size" => 4, "length" => 4, "tooltip" => "portsec-maxmac-tooltip"))."</td></tr>";
						}
						$cdp = getPortCDPEnableWithPID($device,$portid);
						if($cdp != -1) {
							$output .= "<tr><td colspan=\"2\">".$this->loc->s("Others")."</td></tr>";
							$output .= FS::$iMgr->idxLine($this->loc->s("cdp-enable"),"cdpen",$cdp == 1 ? true : false,array("type" => "chk", "tooltip" => "cdp-tooltip"))."</td></tr>";
						}

						$output .= FS::$iMgr->idxLine($this->loc->s("Save-switch"),"wr",false,array("type" => "chk", "tooltip" => "tooltip-saveone"));
						$output .= "</table>";
						if($portid != -1) {
							if (FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") ||
								FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
								$output .= "<center><br />".FS::$iMgr->submit("",$this->loc->s("Save"))."</center>";
								$output .= "</form>";
								$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&d=".$device."&act=9","swpomod",array("snotif" => $this->loc->s("mod-in-progress"), "lock" => true));
							}
						}
						else
							$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-cache"));
					}
					else
						$output .= FS::$iMgr->printError("Les données demandées n'existent pas !");
				}
				// Port Stats
				else if($sh == 2) {
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readportstats") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readportstats")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$file = file(dirname(__FILE__)."/../../../datas/rrd/".$dip."_".$portid.".html");
					if($file) {
						$filebuffer = "";
						$stopbuffer = 0;
						$count = count($file);
						for($i=0;$i<$count;$i++) {
							$file[$i] = preg_replace("#src=\"(.*)\"#","src=\"datas/rrd/$1\"",$file[$i]);
							if(preg_match("#<head>#",$file[$i]) || preg_match("#<div id=\"footer#",$file[$i]) || 
								 preg_match("#<div id=\"legend#",$file[$i]))
															$stopbuffer = 1;
							else if($stopbuffer == 1 && (preg_match("#</head>#",$file[$i]) || preg_match("#</div>#",$file[$i])))
								$stopbuffer = 0;
							else if($stopbuffer == 0 && !preg_match("#<title>#",$file[$i]) && !preg_match("#<meta#",$file[$i]) 
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
				else if($sh == 3) {
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=16");
					$output .= FS::$iMgr->hidden("device",$device).FS::$iMgr->hidden("port",$port);
					$climit = FS::$dbMgr->GetOneData("z_eye_port_monitor","climit","device = '".$device."' AND port = '".$port."'");
					$wlimit = FS::$dbMgr->GetOneData("z_eye_port_monitor","wlimit","device = '".$device."' AND port = '".$port."'");
					$desc = FS::$dbMgr->GetOneData("z_eye_port_monitor","description","device = '".$device."' AND port = '".$port."'");
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->check("enmon",array("check" => (($climit > 0 || $wlimit) > 0 ? true : false),"label" => $this->loc->s("enable-monitor")))."</li><li>";
					$output .= FS::$iMgr->input("desc",$desc,20,200,$this->loc->s("Label"))."</li><li>";
					$output .= FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 10, "length" => 10, "label" => $this->loc->s("warn-step")))."</li><li>";
					$output .= FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 10, "length" => 10, "label" => $this->loc->s("crit-step")))."</li><li>";
					$output .= FS::$iMgr->submit("","Enregister")."</li>";
					$output .= "</ul>";
					$output .= "</form>";
				}
			}
			return $output;
		}

		protected function showDeviceInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			$od = FS::$secMgr->checkAndSecuriseGetData("od");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			if($od == NULL) $od = "port";
			else if($od == "desc" || $od == "name") $od = "name";
			else if($od != "vlan" && $od != "prise" && $od != "port") $od = "port";

			$output = "";

			$snmpro = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_read") && 
				!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) { 
				return FS::$iMgr->printError($this->loc->s("err-no-credentials"));
			}
			if(!FS::isAjaxCall()) {
				FS::$iMgr->showReturnMenu(true);
				$output = "<h2>".$this->loc->s("Device")." ";

				$output .= $device." (";
				$output .= $dip;

				$dloc = FS::$dbMgr->GetOneData("device","location","name = '".$device."'");
				if($dloc != NULL)
				$output .= " - ".$dloc;
				$output .= ")</h2>";

				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid."&d=".$device.($od ? "&od=".$od : "").($filter ? "&fltr=".$filter : ""),$this->loc->s("Portlist"),$showmodule);
				if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswvlans") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswvlans"))
					$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid."&d=".$device,$this->loc->s("VLANlist"),$showmodule);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid."&d=".$device,$this->loc->s("frontview"),$showmodule);
				if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswmodules") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswmodules"))
					$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&d=".$device,$this->loc->s("Internal-mod"),$showmodule);
				if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswdetails") || 
					FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswdetails"))
					$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&d=".$device,$this->loc->s("Details"),$showmodule);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid."&d=".$device,$this->loc->s("Advanced-tools"),$showmodule);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("err-fail-tab")."\");}}});</script>";
				$output .= "</div>";
			} else {
				if($dip == NULL) {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-device"));
					return $output;
				}

				if($showmodule == 1) {
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswmodules") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswmodules")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$query = FS::$dbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
					$found = 0;
					$devmod = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) $found = 1;
						if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
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
					if($found == 1) {
						$output .= "<h3>".$this->loc->s("Internal-mod")."</h3>";
						$output .= "<table><tr><th>".$this->loc->s("Description")."</th><th>".$this->loc->s("Name")."</th>
							<th></th><th></th><th></th><th></th><th>".$this->loc->s("Model")."</th></tr>".$this->showDeviceModules($devmod,1)."</table>";
					}

					return $output;
				}
				else if($showmodule == 2) {
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswdetails") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswdetails")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$query = FS::$dbMgr->Select("device","*","name ='".$device."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$output .= "<h3>".$this->loc->s("Device-detail")."</h3>";
						$output .= "<table class=\"standardTable\">";
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
								if($data3["class"] == "class2") $pwcount += 7;
								else if($data3["class"] == "class3") $pwcount += 15;
							}
							$tmpoutput .= "Module ".$data2["module"]." : ".$pwcount." / ".$data2["power"]." Watts (statut: ";
							$tmpoutput .= ($data2["status"] == "on" ? "<span style=\"color: green;\">".$data2["status"]."</span>" : $data2["status"]);
							$tmpoutput .= ")<br />";
						}
	
						$tmpoutput .= "</td></tr>";
						if($found == 1) $output .= $tmpoutput;
						$output .= "<tr><td>".$this->loc->s("IP-addr")."</td><td>".$data["ip"]."</td></tr>";
						$iswif = (preg_match("#AIR#",$data["model"]) ? true : false);
						if($iswif == false) {
							$output .= "<tr><td>".$this->loc->s("MAC-addr")."</td><td>".$data["mac"]."</td></tr>";
							$output .= "<tr><td>".$this->loc->s("VTP-domain")."</td><td>".$data["vtp_domain"]."</td></tr>";
						}
						$output .= "</table>";
						return $output;
					}
				}
				else if($showmodule == 3) {
					$query = FS::$dbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
					$found = 0;
					$devmod = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) $found = 1;
						if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
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
					if($found == 1) {
						$output .= "<h3>".$this->loc->s("frontview")."</h3>";
						$output .= "<script>
							// 3750 48 ports	
							var c3750p48x = [59,71,87,98,115,126,143,154,171,182,199,210,227,238,254,266,299,310,326,338,355,366,382,394,
							411,422,438,450,466,478,494,506,539,550,567,578,595,606,623,635,651,663,679,690,707,718,735,746];
							var c3750p48y = 38;
							var c3750g48 = [[815,58],[866,58],[815,90],[866,90]];
							// poe
							var c3750poe48x = [61,61,89,89,117,117,144,144,172,172,200,200,229,229,254,254,300,300,327,327,356,356,383,383,
													412,412,439,439,467,467,495,495,540,540,568,568,596,596,624,624,652,652,680,680,708,708,736,736];
													var c3750impp48y = 66;
													var c3750pp48y = 94;
							// 3750 24 ports
							var c3750p24x = [355,366,383,394,411,422,439,450,467,478,495,506,596,607,624,635,652,663,680,691,708,719,736,747];
							var c3750p24y = 36;
							var c3750g24 = [[816,89],[867,89]];
							// poe
							var c3750poe24x = [357,357,385,385,413,413,441,441,470,470,498,498,597,597,625,625,652,652,681,681,710,710,738,738];
													var c3750impp24y = 65;
													var c3750pp24y = 94;
							// 2960 - 24 ports
							var c2960p24x = [411,424,440,451,467,478,495,507,523,536,552,564,593,605,621,633,649,660,676,687,704,717,732,744];
							var c2960p24y = 14;
							var c2960g24 = [[778,85],[808,85],[834,85],[865,85]];
							// poe
													var c2960poe24x = [357,357,385,385,413,413,441,441,470,470,498,498,597,597,625,625,652,652,681,681,710,710,738,738];
													var c2960impp24y = 65;
													var c2960pp24y = 94;
		
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
									var normportX = null; var normportY = null;
									var trunkport = null; var icsize = null;
									var poeX = null; var poePY = null; var poeIMPY = null;
									switch(type) {
										case 1: normportX = c3750p48x; normportY = c3750p48y; trunkport = c3750g48; icsize = 7; 
											poeX = c3750poe48x; poePY = c3750pp48y; poeIMPY = c3750impp48y; break;
										case 2: normportX = c3750p24x; normportY = c3750p24y; trunkport = c3750g24; icsize = 7; 
											poeX = c3750poe24x; poePY = c3750pp24y; poeIMPY = c3750impp24y; break;
										case 3: normportX = c2960p24x; normportY = c2960p24y; trunkport = c2960g24; icsize = 6; 
											poeX = c2960poe24x; poePY = c2960pp24y; poeIMPY = c2960impp24y; break;
										case 4: break;
									}
									for(i=0;i<normportX.length;i++) {
										if(ptab[i] == 0)
											context.fillStyle = \"rgba(200, 0, 0, 0.5)\";
										else if(ptab[i] == 1)
											context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
										else if(ptab[i] == 2)
											context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
										else
											context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
										context.fillRect(normportX[i], normportY, icsize, icsize);
										context.fillStyle = \"rgba(200, 200, 0, 0.6)\";
																			if(poetab[i] == 1)
																					context.fillText(\"7.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));
																			else if(poetab[i] == 2)
																					context.fillText(\"15.0\",poeX[i]-4, (i%2 == 0 ? poeIMPY : poePY));
									}
									for(i=0;i<trunkport.length;i++) {
											if(gptab[i] == 0)
													context.fillStyle = \"rgba(255, 0, 0, 0.6)\";
											else if(gptab[i] == 1)
													context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
											else if(gptab[i] == 2)
													context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
											else
													context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
											context.fillRect(trunkport[i][0], trunkport[i][1], icsize, icsize);
									}
								}
								switch(type) {
									case 1:	img.src = '/uploads/WS-C3750-48PS-S_front.jpg'; break;
									case 2:	img.src = '/uploads/WS-C3750-24PS-S_front.jpg'; break;
									case 3: img.src = '/uploads/2960-24.jpg'; break;
									case 4: img.src = '/uploads/2960-48.jpg'; break;
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
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; var poetab = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if($j < $count) $output .= ",";
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
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; var powport = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',2,ptab,gptab,powport);</script>";
									break;
								case "WS-C2960S-24TS-L": // Gbit switches
									$poearr = array();
									$portlist = "";
									for($j=1;$j<25;$j++) {
										$portlist .= "'GigabitEthernet".($i+1)."/0/".$j."'";
										if($j < 24)
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
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN (".$portlist.")","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=1;$j<=$count;$j++) {
										$output .= $arr_res[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$dbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN ('GigabitEthernet".($i+1)."/0/25', 'GigabitEthernet".($i+1)."/0/26',
											'GigabitEthernet".($i+1)."/0/27','GigabitEthernet".($i+1)."/0/28')","port");
									$arr_res = array();
									while($data = FS::$dbMgr->Fetch($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									$count = count($arr_res);
									for($j=25;$j<=(25+$count);$j++) {
										$output .= $arr_res[$j];
										if($j < (24+$count)) $output .= ",";
									}
									$output .= "]; var powport = [";
									$count = count($poearr);
									for($j=1;$j<=$count;$j++) {
										$output .= $poearr[$j];
										if($j < $count) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',3,ptab,gptab,powport);</script>";
									break;
								default: break;
							}
						}
					}
					return $output;			
				}
				else if($showmodule == 4) { // advanced tools
					$err = FS::$secMgr->checkAndSecuriseGetData("err");
					if(FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_retagvlan") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_retagvlan")) { 
						$output .= "<script type=\"text/javascript\">";
						$output .= "function searchports() {";
						$output .= "$('#subpop').html('".$this->loc->s("search-ports")."...<br /><br /><br />');";
						$output .= "$('#pop').show();
						var ovlid = document.getElementsByName('oldvl')[0].value;";
						$output .= "$.get('index.php?mod=".$this->mid."&at=3&act=10&d=".$device."&vlan='+ovlid, function(data) {
							$('#pop').hide();
							$('#vlplist').html(data); });";
						$output .= "return false;";
						$output .= "};";
						$output .= "function checkTagForm() {
							if($('#vlplist') == null || $('#vlplist').html().length < 1) {
								alert('".$this->loc->s("must-verify-ports")." !');
								return false;
							}
							if(document.getElementsByName('accept')[0].checked == false) {
								alert('".$this->loc->s("must-confirm")." !');
								return false;
							}
							return true;
						};";
						$output .= "</script>";
						$output .= "<h3>".$this->loc->s("title-retag")."</h3>";
						if($err && $err == 1) $output .= FS::$iMgr->printError($this->loc->s("err-one-bad-value")." !");
						$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&d=".$device."&d=".$device."&act=11");
						$output .= $this->loc->s("old-vlanid")." ".FS::$iMgr->numInput("oldvl")."<br />";
						$output .= $this->loc->s("new-vlanid")." ".FS::$iMgr->numInput("newvl")."<br />";
						$output .= "Confirmer ".FS::$iMgr->check("accept");
						$output .= FS::$iMgr->JSSubmit("modify",$this->loc->s("Apply"),"return checkTagForm();")."</form><br />";
						$output .= FS::$iMgr->JSSubmit("search",$this->loc->s("Verify-ports"),"return searchports();")."<div id=\"vlplist\"></div>";

					}

					if(FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_restorestartupcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_restorestartupcfg")) {
						// Common JS WARN: it's only for CISCO
						$output .= "<script type=\"text/javascript\">function checkCopyState(copyId) {
							setTimeout(function() {
								$.post('index.php?at=3&mod=".$this->mid."&act=13&d=".$device."&saveid='+copyId, function(data) {
									if(data == 2) {
										$('#subpop').html('".$this->loc->s("Copy-in-progress")." ...');
										checkCopyState(copyId);
									}
									else if(data == 3) {
										$('#subpop').html('".$this->loc->s("Success")." !');
										setTimeout(function() { $('#pop').hide(); },1000);
									}
									else if(data == 4) {
										$.post('index.php?at=3&mod=".$this->mid."&act=14&d=".$device."&saveid='+copyId, function(data) {
											$('#subpop').html('".$this->loc->s("Fail")." !<br />Cause: '+data); 
										});
										setTimeout(function() { $('#pop').hide(); },5000);
									}
									else
										$('#subpop').html('".$this->loc->s("unk-answer").": '+data);
								}); }, 1000);
						}</script>";
					}

					if(FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_exportcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_exportcfg")) {

						// Copy startup-config -> TFTP/FTP server
						$output .= "<script type=\"text/javascript\">function arangeform() {";
						$output .= "if(document.getElementsByName('exportm')[0].value == 2 || document.getElementsByName('exportm')[0].value == 4 || document.getElementsByName('exportm')[0].value == 5) {";
						$output .= "$('#slogin').show();";
						$output .= "} else if(document.getElementsByName('exportm')[0].value == 1) {";
						$output .= "$('#slogin').hide(); }};";
						$output .= "function sendbackupreq() {";
						$output .= "$('#subpop').html('".$this->loc->s("req-sent")."...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
						$output .= "$('#pop').show();";
						$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=12&d=".$device."', { exportm: document.getElementsByName('exportm')[0].value, srvip: document.getElementsByName('srvip')[0].value,
						srvfilename: document.getElementsByName('srvfilename')[0].value, srvuser: document.getElementsByName('srvuser')[0].value, srvpwd: document.getElementsByName('srvpwd')[0].value,
						io: document.getElementsByName('io')[0].value },
						function(data) { 
							var copyId = data;
							$('#subpop').html('".$this->loc->s("Copy-in-progress")."...');
							checkCopyState(copyId);
						});";
						$output .= "return false;";
						$output .= "};";
						$output .= "</script>";
						$output .= "<h3>".$this->loc->s("title-transfer-conf")."</h3>";
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

					if(FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_restorestartupcfg") ||
						FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_restorestartupcfg")) {
						// Copy startup-config -> running-config
						$output .= "<script type=\"text/javascript\">function restorestartupconfig() {";
						$output .= "$('#subpop').html('".$this->loc->s("req-sent")."...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
						$output .= "$('#pop').show();";
						$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=15&d=".$device."', function(data) { 
							var copyId = data;
							$('#subpop').html('".$this->loc->s("restore-in-progress")."...');
							checkCopyState(copyId);
						});";
						$output .= "return false;";
						$output .= "};";
						$output .= "</script>";
						$output .= "<h3>".$this->loc->s("title-restore-startup")."</h3>";
						$output .= FS::$iMgr->JSSubmit("",$this->loc->s("Restore"),"return restorestartupconfig();");
					}
					return $output;
				}
				else if($showmodule == 5) {
					$output .= "<h3>".$this->loc->s("VLANlist")."</h3>";
					$found = 0;
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_readswvlans") && 
                                        	!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_readswvlans")) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-rights"));
						return $output;
					}
					$query = FS::$dbMgr->Select("device_vlan","vlan,description,creation","ip = '".$dip."'","vlan");
					$tmpoutput = "<table><tr><th>ID</th><th>".$this->loc->s("Description")."</th><th>".$this->loc->s("creation-date")."</th></tr>";
					while($data = FS::$dbMgr->Fetch($query)) {
						if(!$found) $found = 1;
						$crdate = preg_split("#\.#",$data["creation"]);
						$tmpoutput .= "<tr><td>".$data["vlan"]."</td><td>".$data["description"]."</td><td>".$crdate[0]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-vlan")." !");
					return $output;	
				}
				else if($showmodule == 6) {

					$iswif = (preg_match("#AIR#",FS::$dbMgr->GetOneData("device","model","name = '".$device."'")) ? true : false);

					if($iswif == false) {
						$poearr = array();
						// POE States
						$query = FS::$dbMgr->Select("device_port_power","port,class","ip = '".$dip."'");
						while($data = FS::$dbMgr->Fetch($query))
							$poearr[$data["port"]] = $data["class"];
					}
		
					$prisearr = array();
					$query = FS::$dbMgr->Select("z_eye_switch_port_prises","port,prise","ip = '".$dip."'");
					while($data = FS::$dbMgr->Fetch($query))
						$prisearr[$data["port"]] = $data["prise"];
		
					$found = 0;
					if($iswif == false) {
						// Script pour modifier le nom de la prise
						$output .= "<script type=\"text/javascript\">";
						$output .= "function modifyPrise(src,sbmit,sw_,swport_,swpr_) { ";
						$output .= "if(sbmit == true) { ";
						$output .= "$.post('index.php?at=3&mod=".$this->mid."&d=".$device."&act=2', { sw: sw_, swport: swport_, swprise: document.getElementsByName(swpr_)[0].value }, function(data) { ";
						$output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
						$output .= "}); } ";
						$output .= "else $(src).toggle(); }";
						$output .= "</script>";
					}
					$tmpoutput = "<table class=\"standardTable\"><tr><th><a href=\"index.php?mod=".$this->mid."&d=".$device."&od=port\">Port</a></th><th>";
					$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&d=".$device."&od=desc\">".$this->loc->s("Description")."</a></th>
						<th>".$this->loc->s("MAC-addr-iface")."</th><th>Up (Link/Admin)</th>";
					if($iswif == false)
						$tmpoutput .= "<th>".$this->loc->s("Duplex")." (Link/Admin)</th>";
					$tmpoutput .= "<th>Vitesse</th>";
					if($iswif == false)
						$tmpoutput .= "<th>POE</th>";
					$tmpoutput .= "<th>";
					if($iswif == true) $tmpoutput .= $this->loc->s("Channel")."</th><th>".$this->loc->s("Power")."</th><th>SSID";
					else $tmpoutput .= "Vlans</th><th>".$this->loc->s("Connected-devices")."</th></tr>";
					$query = FS::$dbMgr->Select("device_port","port,name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."'",$od);
					while($data = FS::$dbMgr->Fetch($query)) {
						if(preg_match("#unrouted#",$data["port"]))
							continue;
						$filter_ok = 0;
						if($filter == NULL) $filter_ok = 1;
		
						if($found == 0) $found = 1;
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
						if($data["up_admin"] == "down")
							$tmpoutput2 .= "<span style=\"color: red;\">".$this->loc->s("Shut")."</span>";
						else if($data["up_admin"] == "up" && $data["up"] == "down")
							$tmpoutput2 .= "<span style=\"color: orange;\">".$this->loc->s("Inactive")."</span>";
						else if($data["up"] == "up")
							$tmpoutput2 .= "<span style=\"color: black;\">".$this->loc->s("Active")."</span>";
						else
							$tmpoutput2 .= "unk";
						$tmpoutput2 .= "</td><td>";
						if($iswif == false) {
		
							$tmpoutput2 .= "<div id=\"swdp_".$convport."\">";
							$tmpoutput2 .= "<div id=\"swdp_".$convport."l\" class=\"modport\"><span style=\"color: black;\">";
							$dup = (strlen($data["duplex"]) > 0 ? $data["duplex"] : "[NA]");
							$dupadm = (strlen($data["duplex_admin"]) > 0 ? $data["duplex_admin"] : "[NA]");
							if($dup == "half" && $dupadm != "half") $dup = "<span style=\"color: red;\">half</span>";
							$tmpoutput2 .= $dup." / ".$dupadm;
							$tmpoutput2 .= "</span></div></div>";
		
							$tmpoutput2 .= "</td><td>";
						}
						$tmpoutput2 .= $data["speed"]."</td><td>";
		
						if($iswif == false) {
							// POE
							if(isset($poearr[$data["port"]])) {
								if($poearr[$data["port"]] == "class0") $tmpoutput2 .= "0.0 Watts";
								else if($poearr[$data["port"]] == "class2") $tmpoutput2 .= "7.0 Watts";
								else if($poearr[$data["port"]] == "class3") $tmpoutput2 .= "15.0 Watts";
								else $tmpoutput2 .= "Unk class";
							}
							else
							$tmpoutput2 .= "N";
							$tmpoutput2 .= "</td><td>";
						}
		
						$query2 = FS::$dbMgr->Select("device_port_vlan","vlan,native,voice","ip = '".$dip."' AND port = '".$data["port"]."'","vlan");
		
						$nvlan = $data["vlan"];
						$vlanlist = "";
						$vlancount = 0;
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							if($data2["native"] == "t" && $data2["vlan"] != 1) $nvlan = $data2["vlan"];
							if($data2["vlan"] == $filter) $filter_ok = 1;
							if($vlancount == 3) {
								$vlancount = 0;
								$vlanlist .= "<br />";
							}
							$vlanlist .= "<a href=\"index.php?mod=".$this->mid."&vlan=".$data2["vlan"]."\">".$data2["vlan"]."</a>";
							if($data2["native"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(n)</span>";
							if($data2["voice"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(v)</span>";
							$vlanlist .= ",";
							$vlancount++;
						}
		
						if($iswif == false) {
							$tmpoutput2 .= substr($vlanlist,0,strlen($vlanlist)-1);
		
						}
						if($iswif == false) {
							$tmpoutput2 .= "</td><td>";
							$query2 = FS::$dbMgr->Select("node","mac","switch = '".$dip."' AND port = '".$data["port"]."'","mac");
							while($data2 = FS::$dbMgr->Fetch($query2)) {
								$tmpoutput2 .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data2["mac"]."\">".$data2["mac"]."</a><br />";
								$query3 = FS::$dbMgr->Select("node_ip","ip","mac = '".$data2["mac"]."'","time_last",1,1);
								while($data3 = FS::$dbMgr->Fetch($query3)) {
									$tmpoutput2 .= "&nbsp;&nbsp;<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
									$query4 = FS::$dbMgr->Select("node_nbt","nbname,domain,nbuser","mac = '".$data2["mac"]."' AND ip = '".$data3["ip"]."'");
									if($data4 = FS::$dbMgr->Fetch($query4)) {
										if($data4["domain"] != "")
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

						if($filter_ok == 1)
							$tmpoutput .= $tmpoutput2;
					}

					if($found != 0) {
						$output .= $tmpoutput;
						$output .= "</table>";
					}
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-device"));
				}
				else {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-tab"));
				}
			}
			return $output;
		}

		private function showDeviceModules($devmod,$idx,$level=10) {
			if($level == 0)
				return "";
			if(!isset($devmod[$idx])) return "";
			$output = "";
			$count = count($devmod[$idx]);
			for($i=0;$i<$count;$i++) {
				$output .= "<tr><td>" .$devmod[$idx][$i]["desc"]."</td><td>".$devmod[$idx][$i]["name"]."</td><td>";
				if(strlen($devmod[$idx][$i]["hwver"]) > 0)
					$output .= "hw: ".$devmod[$idx][$i]["hwver"];

				$output .= "</td><td>";

				if(strlen($devmod[$idx][$i]["fwver"]) > 0)
                                        $output .= "fw: ".$devmod[$idx][$i]["fwver"];

				$output .= "</td><td>";

				if(strlen($devmod[$idx][$i]["swver"]) > 0)
                                        $output .= "sw: ".$devmod[$idx][$i]["swver"];

				$output .= "</td><td>";

				if(strlen($devmod[$idx][$i]["serial"]) > 0)
                                        $output .= "serial: ".$devmod[$idx][$i]["serial"];

				$output .= "</td><td>";

				$output .= "Type: ".$devmod[$idx][$i]["type"];

				$output .= "</td><td>";
				if(strlen($devmod[$idx][$i]["model"]) > 0)
                                        $output .= "Modèle: ".$devmod[$idx][$i]["model"];

				$output .= "</td></tr>";

				if($idx != 0)
					$output .= $this->showDeviceModules($devmod,$devmod[$idx][$i]["idx"],$level-1);
			}
			return $output;
		}

		private function getDeviceSwitches($devmod,$idx) {
			if(!isset($devmod[$idx])) return "";
			$output = "";
			$count = count($devmod[$idx]);
			for($i=0;$i<$count;$i++) {
				$output .= $devmod[$idx][$i]["desc"];
				if($i+1<$count) $output .= "/";
			}
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
			if(FS::$sessMgr->hasRight("mrule_switches_discover")) {
				$formoutput = "<script type=\"text/javascript\">function showwait() {";
				$formoutput .= "$('#subpop').html('".$this->loc->s("Discovering-in-progress")."...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
				$formoutput .= "$('#pop').show();";
				$formoutput .= "};</script>".FS::$iMgr->form("index.php?mod=".$this->mid."&act=18",array("id" => "discoverdev"));
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->IPInput("dip","",20,40,"Adresse IP:");
				$formoutput .= "</li><li>".FS::$iMgr->JSSubmit("",$this->loc->s("Discover"),"showwait()")."</li>";
				$formoutput .= "</ul></form>";
				$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=18","discoverdev",array("snotif" => $this->loc->s("Discovering-in-progress"), "lock" => true));
				$showtitle = false;
				$output .= "<h2>".$this->loc->s("title-global-fct")."</h2>";
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Discover-device"));
			}

			$foundsw = 0;
			$foundwif = 0;
			$outputswitch = "<table id=\"dev\"><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("MAC-addr")."</th><th>".
				$this->loc->s("Model")."</th><th>".$this->loc->s("OS")."</th><th>".$this->loc->s("Place")."</th><th>".$this->loc->s("Serialnb")."</th><th>".$this->loc->s("State")."</th></tr>";

			$outputwifi = "<h2>".$this->loc->s("title-WiFi-AP")."</h2>";
			$outputwifi .= "<table id=\"dev\"><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("Model")."</th><th>".
				$this->loc->s("OS")."</th><th>".$this->loc->s("Place")."</th><th>".$this->loc->s("Serialnb")."</th></tr>";

			$query = FS::$dbMgr->Select("device","*","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				// Rights: show only reading/writing switches
				$snmpro = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$data["name"]."'");
				$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$data["name"]."'");
				if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmpro."_read") &&
					!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
					!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$data["ip"]."_read") && 
					!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$data["ip"]."_write")) 
					continue;

				// Split WiFi and Switches
				if(preg_match("#AIR#",$data["model"])) {
					if($foundwif == 0) $foundwif = 1;
					$outputwifi .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>";
					$outputwifi .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
				}
				else {
					if($foundsw == 0) $foundsw = 1;
					$outputswitch .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>".$data["mac"]."</td><td>";
					$outputswitch .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td><td>
					<div id=\"st".preg_replace("#[.]#","-",$data["ip"])."\">".FS::$iMgr->img("styles/images/loader.gif",24,24)."</div><script type=\"text/javascript\">
					$.post('index.php?mod=".$this->mid."&act=19', { dip: '".$data["ip"]."' }, function(data) {
					$('#st".preg_replace("#[.]#","-",$data["ip"])."').html(data); });</script></td></tr>";
				}
			}
			if($foundsw != 0 || $foundwif != 0) {
				$rightsok = false;
				if(FS::$sessMgr->hasRight("mrule_switches_globalsave")) {
					$rightsok = true;
					// Write all devices button
					$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=20",array("id" => "saveall"));
					$formoutput .= FS::$iMgr->submit("sallsw",$this->loc->s("save-all-switches"),array("tooltip" => "tooltip-save"));
					$formoutput .= "</form>";
					$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=20","saveall",array("snotif" => $this->loc->s("saveorder-launched"), "stimeout" => 10000, "lock" => true));
				}
				if(FS::$sessMgr->hasRight("mrule_switches_globalbackup")) {
					$rightsok = true;
					// Backup all devices button
					$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=21",array("id" => "backupall"));
					$formoutput .= FS::$iMgr->submit("bkallsw",$this->loc->s("backup-all-switches"),array("tooltip" => "tooltip-backup"));
					$formoutput .= "</form>";
					$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=21","backupall",array("snotif" => $this->loc->s("backuporder-launched"), "stimeout" => 10000, "lock" => true));
				}
				if($rightsok) {
					if($titleshow) $output .= "<h2>".$this->loc->s("title-global-fct")."</h2>";
					// Openable divs
					$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Advanced-Functions"));
				}
			}
			if($foundsw != 0 || $foundwif != 0)
				$output .= "<h2>".$this->loc->s("title-router-switch")."</h2>";

			if($foundsw != 0) {
				$output .= $outputswitch;
				$output .= "</table>";
			}
			if($foundwif != 0) {
				$output .= $outputwifi;
				$output .= "</table>";
			}
			if($foundsw != 0 || $foundwif != 0) {
				$output .= "<script type=\"text/javascript\">
					$.event.props.push('dataTransfer');
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
									FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"$(\'#pop\').hide()")."</form>');
									$('#pop').show();
							}
					});
					</script>";
			}

			if($foundsw == 0 && $foundwif == 0)
				$output .= FS::$iMgr->printError($this->loc->s("err-no-device2"));

			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 2: // Plug fast edit
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$prise = FS::$secMgr->checkAndSecurisePostData("swprise");
					if($port == NULL || $sw == NULL /*|| $prise != NULL && !preg_match("#^[A-Z][1-9]\.[1-9A-Z][0-9]?\.[1-9][0-9A-Z]?$#",$prise)*/) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (plug fast edit)");
						echo "ERROR";
						return;
					}

					$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
						echo "NORIGHTS";
						return;	
                        		}

					if($prise == NULL) $prise = "";
					// Modify Plug for switch port
					FS::$dbMgr->Delete("z_eye_switch_port_prises","ip = '".$sw."' AND port = '".$port."'");
					FS::$dbMgr->Insert("z_eye_switch_port_prises","ip,port,prise","'".$sw."','".$port."','".$prise."'");

					// Return text for AJAX call
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Set plug for device '".$sw."' to '".$prise."' on port '".$port."'");
					if($prise == "") $prise = "Modifier";
					echo $prise;
					return;
				case 3: // Desc fast edit
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$desc = FS::$secMgr->checkAndSecurisePostData("swdesc");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $desc == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (desc fast edit)");
						echo "ERROR";
						return;
					}
					$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
						echo "NORIGHTS";
						return;	
					}
					if(FS::$dbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if(setPortDesc($sw,$port,$desc) == 0) {
							echo $desc;
							if($save == "true")
								writeMemory($sw);
							FS::$dbMgr->Update("device_port","name = '".$desc."'","ip = '".$sw."' AND port = '".$port."'");
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Set description for '".$sw."' to '".$desc."' on port '".$port."'");
						}
						else {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",1,"Failed to set description on device '".$sw."' and port .'".$port."'");
							echo "ERROR";
						}
					}
					return;
				case 5: // Duplex fast edit
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$dup = FS::$secMgr->checkAndSecurisePostData("swdp");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $dup == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (duplex fast edit)");
						echo "ERROR";
						return;
					}
					$device = FS::$dbMgr->GetOneData("device","name","ip = '".$sw."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$sw."_write")) {
						echo "NORIGHTS";
						return;	
					}
					if(FS::$dbMgr->GetOneData("device_port","type","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if($this->setPortDuplex($sw,$port,$dup) == 0) {
							if($save == "true")
								writeMemory($sw);

							$duplex = "auto";
							if($dup == 1) $duplex = "half";
							else if($dup == 2) $duplex = "full";

							FS::$dbMgr->Update("device_port","duplex_admin = '".$duplex."'","ip = '".$sw."' AND port = '".$port."'");
							$ldup = FS::$dbMgr->GetOneData("device_port","duplex","ip = '".$sw."' AND port = '".$port."'");
							$ldup = (strlen($ldup) > 0 ? $ldup : "[NA]");
							if($ldup == "half" && $duplex != "half") $ldup = "<span style=\"color: red;\">".$ldup."</span>";
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
					$shut = FS::$secMgr->checkAndSecurisePostData("shut");
					$cdpen = FS::$secMgr->checkAndSecurisePostData("cdpen");
					$trunk = FS::$secMgr->checkAndSecurisePostData("trmode");
					$nvlan = FS::$secMgr->checkAndSecurisePostData("nvlan");
					$duplex = FS::$secMgr->checkAndSecurisePostData("duplex");
					$speed = FS::$secMgr->checkAndSecurisePostData("speed");
					$voicevlan = FS::$secMgr->checkAndSecurisePostData("voicevlan");
					$wr = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $trunk == NULL || $nvlan == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (plug edit)");
						if(FS::isAjaxCall())
							echo "Some fields are missing (port, switch, trunk or native vlan)";
						else
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=1");
						return;
					}

					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$sw."'");

					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$sw."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo $this->loc->s("err-no-credentials");
						return;	
					}
					$pid = getPortId($sw,$port);
					if($pid == -1) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"PID is incorrect (plug edit)");
						if(FS::isAjaxCall())
							echo "PID is incorrect (".$pid.")";
						else
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
						return;
					}

					$logoutput = "Modify port '".$port."' on device '".$sw."'";
					$logvals = array();
					$idx = getPortIndexes($sw,$pid);

					if($duplex && FS::$secMgr->isNumeric($duplex)) {
						if($duplex < 1 || $duplex > 4) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are wrong: duplex (plug edit)");
							if(FS::isAjaxCall())
								echo "Duplex field is wrong (".$duplex.")";
							else
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=1");
							return;
						}

						if($idx != NULL) {
							$logvals["duplex"]["src"] = getPortDuplexWithPID($sw,$pid);
							setPortDuplexWithPid($sw,$idx[0].".".$idx[1],$duplex);
							$logvals["duplex"]["dst"] = $duplex;
						}
					}

					if($speed && FS::$secMgr->isNumeric($speed)) {
						if($idx != NULL) {
							$logvals["speed"]["src"] = getPortSpeedWithPID($sw,$pid);
							setPortSpeedWithPid($sw,$idx[0].".".$idx[1],$speed);
							$logvals["speed"]["dst"] = $speed;
						}
					}

					$logvals["accessvlan"]["src"] = getSwitchAccessVLANWithPID($sw,$pid);
					$logvals["trunkencap"]["src"] = getSwitchTrunkEncapWithPID($sw,$pid);
					$logvals["mode"]["src"] = getSwitchportModeWithPID($sw,$pid);
					$logvals["trunkvlan"]["src"] = getSwitchportTrunkVlansWithPid($sw,$pid);
					$logvals["nativevlan"]["src"] = getSwitchTrunkNativeVlanWithPID($sw,$pid);

					// Mab & 802.1X
					$mabst = getSwitchportMABState($sw,$pid);
					if($mabst != -1)
						$logvals["mabst"]["src"] = $mabst;
					$failvlan = getSwitchportAuthFailVLAN($sw,$pid);
					if($failvlan != -1)
						$logvals["failvlan"]["src"] = $failvlan;
					$norespvlan = getSwitchportAuthNoRespVLAN($sw,$pid);
					if($norespvlan != -1)
						$logvals["norespvlan"]["src"] = $norespvlan;
					$deadvlan = getSwitchportAuthDeadVLAN($sw,$pid);
                                        if($deadvlan != -1)
                                                $logvals["deadvlan"]["src"] = $deadvlan;
					$controlmode = getSwitchportControlMode($sw,$pid);
					if($controlmode != -1)
						$logvals["controlmode"]["src"] = $controlmode;
					$authhostmode = getSwitchportAuthHostMode($sw,$pid);
					if($authhostmode != -1)
						$logvals["authhostmode"]["src"] = $authhostmode;

					if($trunk == 1) {
						$vlanlist = FS::$secMgr->checkAndSecurisePostData("vllist");

						setSwitchAccessVLANWithPID($sw,$pid,1);
						$logvals["accessvlan"]["dst"] = 1;
						// mab disable
						if($mabst != -1) {
							setSwitchportMABEnableWithPID($sw,$pid,2);
							$logvals["mabst"]["dst"] = 2;
						}
						if($failvlan != -1) {
							setSwitchportAuthFailVLAN($sw,$pid,0);
							$logvals["failvlan"]["dst"] = 0;
						}
						if($norespvlan != -1) {
							setSwitchportAuthNoRespVLAN($sw,$pid,0);
							$logvals["norespvlan"]["dst"] = 0;
						}
						if($deadvlan != -1) {
                                                        setSwitchportAuthDeadVLAN($sw,$pid,0);
                                                        $logvals["deadvlan"]["dst"] = 0;
                                                }
						if($controlmode != -1) {
							setSwitchportControlMode($sw,$pid,3);
							$logvals["controlmode"]["dst"] = 3;
						}
						// dot1x disable
						if($authhostmode != -1) {
							setSwitchportAuthHostMode($sw,$pid,1);
							$logvals["authhostmode"]["dst"] = 1;
						}

			                        // set settings
						if(setSwitchTrunkEncapWithPID($sw,$pid,4) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
						}
						$logvals["trunkencap"]["dst"] = 4;
						if(setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
						}
						$logvals["mode"]["dst"] = $trunk;
						if(in_array("all",$vlanlist)) {
							if(setSwitchNoTrunkVlanWithPID($sw,$pid) != 0) {
                                                                header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
                                                                return;
                                                        }
						}
						else {
							if(setSwitchTrunkVlanWithPID($sw,$pid,$vlanlist) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
							}
						}
						$logvals["trunkvlan"]["dst"] = $vlanlist;
						if(setSwitchTrunkNativeVlanWithPID($sw,$pid,$nvlan) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
						}
						$logvals["nativevlan"]["dst"] = $nvlan;
					} else if($trunk == 2) {
						setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
						$logvals["nativevlan"]["dst"] = 1;
						setSwitchNoTrunkVlanWithPID($sw,$pid);
						$logvals["trunkvlan"]["dst"] = "";
						// mab disable
						if($mabst != -1) {
							setSwitchportMABEnableWithPID($sw,$pid,2);
							$logvals["mabst"]["dst"] = 2;
						}
						if($failvlan != -1) {
							setSwitchportAuthFailVLAN($sw,$pid,0);
							$logvals["failvlan"]["dst"] = 0;
						}
						if($norespvlan != -1) {
							setSwitchportAuthNoRespVLAN($sw,$pid,0);
							$logvals["norespvlan"]["dst"] = 0;
						}
						if($deadvlan != -1) {
                                                        setSwitchportAuthDeadVLAN($sw,$pid,0);
                                                        $logvals["deadvlan"]["dst"] = 0;
                                                }
						if($controlmode != -1) {
							setSwitchportControlMode($sw,$pid,3);
							$logvals["controlmode"]["dst"] = 3;
						}
						// dot1x disable
						if($authhostmode != -1) {
							setSwitchportAuthHostMode($sw,$pid,1);
							$logvals["authhostmode"]["dst"] = 1;
						}
						// set settings
						if(setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
								if(FS::isAjaxCall())
									echo "Fail to set Switchport mode";
								else
									header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						$logvals["mode"]["dst"] = $trunk;
						if(setSwitchTrunkEncapWithPID($sw,$pid,5) != 0) {
							if(FS::isAjaxCall())
								echo "Fail to set Switchport Trunk encapsulated VLANs";
							else
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
						}
						$logvals["trunkencap"]["dst"] = 5;
						if(setSwitchAccessVLANWithPID($sw,$pid,$nvlan) != 0) {
							if(FS::isAjaxCall())
								echo "Fail to set Switchport Access Vlan";
							else
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						$logvals["accessvlan"]["dst"] = $nvlan;

					} else if($trunk == 3) {
						$dot1xhostmode = FS::$secMgr->checkAndSecurisePostData("dot1xhostmode");
						$mabeap = FS::$secMgr->checkAndSecurisePostData("mabeap");
						$noresp = FS::$secMgr->checkAndSecurisePostData("norespvlan");
						$dead = FS::$secMgr->checkAndSecurisePostData("deadvlan");
						if($dot1xhostmode < 1 || $dot1xhostmode > 4) {
							if(FS::isAjaxCall())
								echo "Dot1x hostmode is wrong (".$dot1xhostmode.")";
							else
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
						}
						// switchport mode access & no vlan assigned
						setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
						$logvals["nativevlan"]["dst"] = 1;
			                        setSwitchNoTrunkVlanWithPID($sw,$pid);
                        			$logvals["trunkvlan"]["dst"] = "";
						setSwitchportModeWithPID($sw,$pid,2);
						$logvals["mode"]["dst"] = 2;
						setSwitchTrunkEncapWithPID($sw,$pid,5);
						$logvals["trunkencap"]["dst"] = 5;
						setSwitchAccessVLANWithPID($sw,$pid,1);
						$logvals["accessvlan"]["dst"] = 1;

						// enable mab
						if($mabst != -1) {
							setSwitchportMABEnableWithPID($sw,$pid,1);
							$logvals["mabst"]["dst"] = 1;
						}
						$mabtype = getSwitchportMABType($sw,$pid);
						if($mabtype != -1) {
							$logvals["mabtype"]["src"] = $mabtype;
							// set MAB to EAP or not
							setSwitchMABTypeWithPID($sw,$pid,$mabeap == "on" ? 2 : 1);
							$logvals["mabtype"]["dst"] = ($mabeap == "on" ? 2 : 1);
						}
						if($failvlan != -1) {
							// enable authfail & noresp vlans
							setSwitchportAuthFailVLAN($sw,$pid,$nvlan);
							$logvals["failvlan"]["dst"] = $nvlan;
						}
						if($norespvlan != -1) {
							setSwitchportAuthNoRespVLAN($sw,$pid,$noresp);
							$logvals["norespvlan"]["dst"] = $noresp;
						}
						if($deadvlan != -1) {
                                                        setSwitchportAuthDeadVLAN($sw,$pid,$dead);
                                                        $logvals["deadvlan"]["dst"] = $dead;
                                                }
						if($controlmode != -1) {
							// authentication port-control auto
							setSwitchportControlMode($sw,$pid,2);
							$logvals["controlmode"]["dst"] = 2;
						}
						// Host Mode for Authentication
						if($authhostmode != -1) {
							setSwitchportAuthHostMode($sw,$pid,$dot1xhostmode);
							$logvals["authhostmode"]["dst"] = $dot1xhostmode;
						}
					}
					$logvals["hostmode"]["src"] = getPortStateWithPID($sw,$pid);
					if(setPortStateWithPID($sw,$pid,($shut == "on" ? 2 : 1)) != 0) {
						if(FS::isAjaxCall())
							echo "Fail to set switchport shut/no shut state";
						else
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
						return;
					}
					$logvals["hostmode"]["dst"] = ($shut == "on" ? 2 : 1);
					$logvals["voicevlan"]["src"] = getSwitchportVoiceVlanWithPID($sw,$pid);
					if(setSwitchportVoiceVlanWithPID($sw,$pid,$voicevlan) != 0) {
						if(FS::isAjaxCall())
							echo "Fail to set switchport voice vlan";
						else
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
						return;
					}
					$logvals["voicevlan"]["dst"] = $voicevlan;
					$logvals["desc"]["src"] = getPortDesc($sw,$pid);
					setPortDescWithPID($sw,$pid,$desc);
					$logvals["desc"]["dst"] = $desc;

					$cdpstate = getPortCDPEnableWithPID($sw,$pid);
					if($cdpstate != -1) {
						$logvals["cdp"]["src"] = ($cdpstate == 1 ? true : false);
						setPortCDPEnableWithPID($sw,$pid,$cdpen == "on" ? 1 : 2);
						$logvals["cdp"]["dst"] = ($cdpen == "on" ? true : false);
					}

					$portsecen = getPortSecEnableWithPID($sw,$pid);
                                        if($portsecen != -1) {
						$psen = FS::$secMgr->checkAndSecurisePostData("psen");
						$logvals["psen"]["src"] = ($portsecen == 1 ? true : false);
                                                setPortSecEnableWithPID($sw,$pid,$psen == "on" ? 1 : 2);
                                                $logvals["psen"]["dst"] = ($psen == "on" ? true : false);

						$portsecvact = getPortSecViolActWithPID($sw,$pid);
						$psviolact = FS::$secMgr->checkAndSecurisePostData("psviolact");
						$logvals["psviolact"]["src"] = $portsecvact;
                                                setPortSecViolActWithPID($sw,$pid,$psviolact);
                                                $logvals["psviolact"]["dst"] = $psviolact;

						$psecmaxmac = getPortSecMaxMACWithPID($sw,$pid);
                                                $psmaxmac = FS::$secMgr->checkAndSecurisePostData("psmaxmac");
                                                $logvals["psmaxmac"]["src"] = $psecmaxmac;
                                                setPortSecMaxMACWithPID($sw,$pid,$psmaxmac);
                                                $logvals["psmaxmac"]["dst"] = $psmaxmac;
					}

					if($wr == "on")
						writeMemory($sw);


					if($prise == NULL) $prise = "";
					pg_query("DELETE FROM z_eye_switch_port_prises where ip = '".$dip."' AND port = '".$port."'");
					pg_query("INSERT INTO z_eye_switch_port_prises (ip,port,prise) VALUES ('".$dip."','".$port."','".$prise."')");

					FS::$dbMgr->Update("device_port","name = '".$desc."'","ip = '".$dip."' AND port = '".$port."'");
					FS::$dbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$dip."' AND port = '".$port."'");
					pg_query("UPDATE device_port SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."'");
					pg_query("UPDATE device_port_vlan SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."' and native='t'");
					FS::$dbMgr->Delete("device_port_vlan","ip = '".$dip."' AND port='".$port."'");
					$vllist = FS::$secMgr->checkAndSecurisePostData("vllist");
					if($trunk == 1) {
						if($vllist) {
							// Insert VLAN in database only if not in trunk All mode
							if(!in_array("all",$vllist)) {
								$count = count($vllist);
								for($i=0;$i<$count;$i++)
									FS::$dbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$vllist[$i]."','f',NOW(),NOW()");
							}
						}
					}
					else if ($trunk == 2) {
						FS::$dbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$nvlan."','t',NOW(),NOW()");
					}

					foreach($logvals as $keys => $values) {
						if(is_array($values["src"]) || is_array($values["dst"])) {
							if(count(array_diff($values["src"],$values["dst"])) != 0) {
								$logoutput .= "\n".$keys.": ";
								$count = count($values["src"]);
								for($i=0;$i<$count;$i++) $logoutput .= $values["src"][$i].",";
								$logoutput .= " => ";
								$count = count($values["dst"]);
								for($i=0;$i<$count;$i++) $logoutput .= $values["dst"][$i].",";
							}
						}
						else if($values["src"] != $values["dst"]) {
							$logoutput .= "\n".$keys.": ".$values["src"]." => ".$values["dst"];
						}
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,$logoutput);
					if(FS::isAjaxCall())
						echo $this->loc->s("done-with-success");
					else
						header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port);
					return;
				case 10: // replace vlan portlist
					echo "<h3>".$this->loc->s("title-port-modiflist")."</h3>";
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$vlan = FS::$secMgr->checkAndSecuriseGetData("vlan");
					if(!$device) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (vlan replacement, portlist)");
						echo FS::$iMgr->printError($this->loc->s("err-no-device"));
						return;
					}

					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
						return;	
					}

					if(!$vlan || !FS::$secMgr->isNumeric($vlan)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (vlan replacement, portlist)");
						echo FS::$iMgr->printError($this->loc->s("err-vlan-fail")." !");
						return;
					}

					$plist = getPortList($device,$vlan);
					$count = count($plist);
					if($count > 0) {
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
					if(!$device || !$old || !$new || !FS::$secMgr->isNumeric($old) || !FS::$secMgr->isNumeric($new) || $old > 4096 || $new > 4096 || $old < 0 || $new < 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (vlan replacement)");
						header("Location: index.php?mod=".$this->mid."&d=".$device."&sh=4&err=1");
						return;
					}

					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						header("Location: index.php?mod=".$this->mid."&d=".$device."&err=99");
						return;	
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Replace VLAN '".$old."' by '".$new."' on device '".$device."'");
					replaceVlan($device,$old,$new);
					header("Location: index.php?mod=".$this->mid."&d=".$device."&sh=4");
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
					if(!$device || !$trmode || ($trmode != 1 && $trmode != 2 && $trmode != 4 && $trmode != 5) ||
						!$sip || !FS::$secMgr->isIP($sip) || !$filename || strlen($filename) == 0 || !$io || ($io != 1 && $io != 2)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (backup statup-config)");
						echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
						return;	
					}
					if($trmode == 2 || $trmode == 4 || $trmode == 5) {
						$username = FS::$secMgr->checkAndSecurisePostData("srvuser");
						$password = FS::$secMgr->checkAndSecurisePostData("srvpwd");
						if(!$username || $username == "" || !$password || $password == "") {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (backup startup-confi)");
							echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
							return;
						}
						if($io == 1) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Export '".$device."' config to '".$sip."':'".$filename."'");
							echo exportConfigToAuthServer($device,$sip,$trmode,$filename,$username,$password);
						}
						else if($io == 2) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Import '".$device."' config from '".$sip."':'".$filename."'");
							echo importConfigFromAuthServer($device,$sip,$trmode,$filename,$username,$password);
						}
					}
					else if($trmode == 1) {
						if($io == 1) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Export '".$device."' config to '".$sip."':'".$filename."'");
							echo  exportConfigToTFTP($device,$sip,$filename);
						}
						else {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Import '".$device."' config from '".$sip."':'".$filename."'");
							echo  importConfigFromTFTP($device,$sip,$filename);
						}
					} else {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Invalid export type '".$trmode."'");
						FS::$iMgr->printError($this->loc->s("err-invalid-export"));
					}
					return;
				/*
				* Verify backup state
				*/
				case 13:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
					if(!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (verify backup state)");
						echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
						return;	
					}
					echo getCopyState($device,$saveid);
					return;
				case 14:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
					if(!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (verify backup error)");
						echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
						return;	
					}
					$err = getCopyError($device,$saveid);
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
					if(!$device) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (restore startup-config)");
						echo FS::$iMgr->printError($this->loc->s("err-bad-datas")." !");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						echo FS::$iMgr->printError($this->loc->s("err-no-credentials"));
						return;	
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Launch restore startup-config for device '".$device."'");
					echo restoreStartupConfig($device);
					return;
				// Port monitoring
				case 16:
					$device = FS::$secMgr->checkAndSecurisePostData("device");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
					$climit = FS::$secMgr->checkAndSecurisePostData("climit");
					$wlimit = FS::$secMgr->checkAndSecurisePostData("wlimit");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					if(!$device || !$port) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (port monitoring)");
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write") && 
						!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_writeportmon") && !FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_writeportmon")) {
						header("Location: index.php?mod=".$this->mid."&err=99");
						return;	
					}

					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					if(!$dip) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Bad device '".$device."' (port monitoring)");
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}

					$dport = FS::$dbMgr->GetOneData("device_port","name","ip = '".$dip."' AND port = '".$port."'");
					if(!$dport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Bad port '".$dport."' for device '".$dip."' (port monitoring)");
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}
					if($enmon == "on") {
						if(!$climit || !$wlimit || !FS::$secMgr->isNumeric($wlimit) || !FS::$secMgr->isNumeric($climit) || $climit <= 0 || $wlimit <= 0) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing/wrong (port monitoring)");
							header("Location: index.php?mod=".$this->mid."&d=".$device."&p=".$port."&sh=3&err=2");
							return;
						}
						FS::$dbMgr->Delete("z_eye_port_monitor","device = '".$device."' AND port = '".$port."'");
						FS::$dbMgr->Insert("z_eye_port_monitor","device,port,climit,wlimit,description","'".$device."','".$port."','".$climit."','".$wlimit."','".$desc."'");
					}
					else
						FS::$dbMgr->Delete("z_eye_port_monitor","device = '".$device."' AND port = '".$port."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Port monitoring for device '".$device."' and port '".$dport."' edited. Enabled: ".($enmod == "on" ? "yes" : "no").
						" wlimit: ".$wlimit." climit: ".$climit." desc: '".$desc."'");
					header("Location: index.php?mod=".$this->mid."&d=".$device."&p=".$port."&sh=3");
					return;
				case 17: // device cleanup
					$device = FS::$secMgr->checkAndSecurisePostData("device");
					if(!$device) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (Device cleanup)");
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
					$snmprw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_write") &&
						!FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_write")) {
						header("Location: index.php?mod=".$this->mid."&err=99");
						return;	
					}
					$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
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
					FS::$dbMgr->Delete("z_eye_port_id_cache","device = '".$device."'");
					FS::$dbMgr->Delete("z_eye_port_monitor","device = '".$device."'");
					FS::$dbMgr->Delete("z_eye_switch_port_prises","ip = '".$dip."'");
					FS::$dbMgr->Delete("z_eye_snmp_cache","device = '".$device."'");
					FS::$dbMgr->Delete("device","ip = '".$dip."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Remove device '".$device."' from Z-Eye");
					header("Location: index.php?mod=".$this->mid);
				case 18: // Device discovery
					if(!FS::$sessMgr->hasRight("mrule_switches_discover")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"User ".FS::$sessMgr->getUserName()." wants to discover a device !");
						header("Location: index.php?mod=".$this->mid."&err=3");
						return;
					}
					$dip = FS::$secMgr->getPost("dip","i4");
					if(!$dip) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (device discovery)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}
					exec("netdisco -d ".$dip);

					$devro = "";
		                        $devrw = "";
					$snmpro = array();
					$snmprw = array();

					loadNetdiscoCommunities($snmpro,$snmprw);
					$devname = FS::$dbMgr->GetOneData("device","name","ip = '".$dip."'");

                			$foundro = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$devname."'");
			                $foundrw = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$devname."'");
                        		if($foundro && checkSnmp($dip,$foundro) == 0)
			                        $devro = $foundro;
                        		if($foundrw && checkSnmp($dip,$foundrw) == 0)
			                	$devrw = $foundrw;

                        		for($i=0;$i<count($snmpro) && $devro == "";$i++) {
			                        if(checkSnmp($dip,$snmpro[$i]) == 0)
                        		 	       $devro = $snmpro[$i];
			                }

					for($i=0;$i<count($snmprw) && $devrw == "";$i++) {
                        		        if(checkSnmp($dip,$snmprw[$i]) == 0)
			                		$devrw = $snmprw[$i];
                        		}
					if($foundro != $devro && strlen($devro) > 0 || $foundrw != $devrw && strlen($devrw) > 0)
						FS::$dbMgr->Insert("z_eye_snmp_cache","device,snmpro,snmprw","'".$devname."','".$devro."','".$devrw."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"Launch discovering for device '".$dip."'");
					if(FS::isAjaxCall())
                                                echo $this->loc->s("done-with-success");
                                        else
						header("Location: index.php?mod=".$this->mid);
					return;
				case 19: // device status
					$dip = FS::$secMgr->getPost("dip","i4");
					if(!$dip) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are missing (AJAX device status)");
						echo "<span style=\"color:red\">IP Error ".$dip."</span>";
						return;
					}
					$out = "";
					exec("ping -W 100 -c 1 ".$dip." | grep ttl | wc -l|awk '{print $1}'",$out);
					if(!is_array($out) || count($out) > 1)
						echo "<span style=\"color:red;\">".$this->loc->s("err-output")." ".var_dump($out)."</span>";
					else if($out[0] > 1)
						echo "<span style=\"color:red;\">".$this->loc->s("err-output-value")." ".$out."</span>";
					else if($out[0] == 0)
						echo "<span style=\"color:red;\">".$this->loc->s("Offline")."</span>";
					else if($out[0] == 1)
						echo "<span style=\"color:green;\">".$this->loc->s("Online")."</span>";
					return;
				case 20: // Save all devices
					if(!FS::$sessMgr->hasRight("mrule_switches_globalsave")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"User ".FS::$sessMgr->getUserName()." wants to save all devices !");
						header("Location: index.php?mod=".$this->mid."&err=3");
						return;
					}
					$query = FS::$dbMgr->Select("device","name");
					while($data = FS::$dbMgr->Fetch($query)) {
						writeMemory($data["name"]);
					}
					
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"User ".FS::$sessMgr->getUserName()." saved all devices");
					if(FS::isAjaxCall())
						echo $this->loc->s("saveorder-terminated");
					else
						header("Location: index.php?mod=".$this->mid."&err=-1");
					return;
				case 21: // Backup all devices
					if(!FS::$sessMgr->hasRight("mrule_switches_globalbackup")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"User ".FS::$sessMgr->getUserName()." wants to backup all devices !");
						header("Location: index.php?mod=".$this->mid."&err=3");
						return;
					}
					$output = "";
					$query = FS::$dbMgr->Select("z_eye_save_device_servers","addr,type,path,login,pwd");
					while($data = FS::$dbMgr->Fetch($query)) {
						if(!FS::$secMgr->isIP($data["addr"]))
							continue;
							
						$query2 = FS::$dbMgr->Select("device","ip,name");
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							if($data["type"] == 1)
								$copyId = exportConfigToTFTP($data2["name"],$data["addr"],$data["path"]."conf-".$data2["name"]);
							else if($data["type"] == 2 || $data["type"] == 4 || $data["type"] == 5)
								$copyId = exportConfigToAuthServer($data2["name"],$data["addr"],$data["type"],$data["path"]."conf-".$data2["name"],$data["login"],$data["pwd"]);
							
							sleep(1);
							$copyState = getCopyState($data2["name"],$copyId);
							while($copyState == 2) {
								sleep(1);
								$copyState = getCopyState($data2["name"],$copyId);
							}
							
							if($copyState == 4) {
								$copyErr = getCopyError($data2["name"],$copyId);
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
					if(FS::isAjaxCall()) {
						if(strlen($output) > 0) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",1,"Some devices cannot be backup: ".$output);
							echo $this->loc->s("err-thereis-errors")."<br />".$output;
						}
						else {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"User ".FS::$sessMgr->getUserName()." backup all devices");
							echo $this->loc->s("backuporder-terminated");
						}
					}
					else {
						if(strlen($output) > 0) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",1,"Some devices cannot be backup: ".$output);
							header("Location: index.php?mod=".$this->mid."&err=1");
						}
						else {
							FS::$log->i(FS::$sessMgr->getUserName(),"switches",0,"User ".FS::$sessMgr->getUserName()." backup all devices");
							header("Location: index.php?mod=".$this->mid);
						}
					}
					return;
				default: break;
			}
		}
	};
?>
