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

	require_once(dirname(__FILE__)."/device.api.php");

	class CiscoAPI extends DeviceAPI {
		function CiscoAPI() { $this->vendor = "cisco"; }

		/*
		* Interface functions
		*/

		public function showStateOpts() {
			$state = $this->getPortState();
			return FS::$iMgr->idxLine("Shutdown","shut",array("value" => ($state == 2),"type" => "chk", "tooltip" => "tooltip-shut"));
		}

		public function handleState($logvals, $port = "", $shut = -1) {
			if ($port == "") {
				$port = FS::$secMgr->checkAndSecurisePostData("port");
			}
			if ($shut == -1) {
				$shut = FS::$secMgr->checkAndSecurisePostData("shut");
			}

			$portstate = $this->getPortState();

			// If it's same state do nothing
			if($portstate == ($shut == "on" ? 2 : 1)) {
				return;
			}

			$logvals["hostmode"]["src"] = $portstate;
			if($this->setPortState($shut == "on" ? 2 : 1) != 0) {
				echo "Fail to set switchport shut/no shut state";
				return;
			}
			$logvals["hostmode"]["dst"] = ($shut == "on" ? 2 : 1);
			FS::$dbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$this->devip."' AND port = '".$port."'");
		}

		public function showSpeedOpts() {
			$output = "";
			$sp = $this->getPortSpeed();
			$output .= "<tr><td>".$this->loc->s("admin-speed")."</td><td>";
			if($sp > 0) {
				$output .= FS::$iMgr->select("speed",array("tooltip" => "tooltip-speed"));
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
			return $output;
		}

		public function handleSpeed($logvals) {
			$speed = FS::$secMgr->checkAndSecurisePostData("speed");
			if($speed && FS::$secMgr->isNumeric($speed)) {
				$portspeed = $this->getPortSpeed();

				// If it's same speed do nothing
				if($portspeed == $speed)
					return;

				$logvals["speed"]["src"] = $portspeed;
				$this->setPortSpeed($speed);
				$logvals["speed"]["dst"] = $speed;
			}
		}

		public function showDuplexOpts() {
			$output = "";
			$dup = $this->getPortDuplex();
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
			return $output;
		}

		public function handleDuplex($logvals) {
			$duplex = FS::$secMgr->checkAndSecurisePostData("duplex");
			if($duplex && FS::$secMgr->isNumeric($duplex)) {
				if($duplex < 1 || $duplex > 4) {
					$this->log(2,"Some fields are wrong: duplex (plug edit)");
					FS::$iMgr->ajaxEchoError("Duplex field is wrong (".$duplex.")","",true);
					return;
				}

				$portduplex = $this->getPortDuplex();

				// If same duplex, do nothing
				if($portduplex == $duplex)
					return;

				$logvals["duplex"]["src"] = $portduplex;
				$this->setPortDuplex($duplex);
				$logvals["duplex"]["dst"] = $duplex;
			}
		}

		public function showPortSecurityOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_portsec") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_portsec")) {
				$portsecen = $this->getPortSecEnable();
				if($portsecen != -1) {
					$output .= "<tr><td colspan=\"2\">".$this->loc->s("portsecurity")."</td></tr>";
					// check for enable/disable PortSecurity
					$output .= "<tr><td>".$this->loc->s("portsec-enable")."</td><td>".FS::$iMgr->check("psen",array("check" => $portsecen == 1 ? true : false))."</td></tr>";
					// Active Status for PortSecurity
					$output .= "<tr><td>".$this->loc->s("portsec-status")."</td><td>";
					$portsecstatus = $this->getPortSecStatus();
					switch($portsecstatus) {
						case 1: $output .= $this->loc->s("Active"); break;
						case 2: $output .= $this->loc->s("Inactive"); break;
						case 3: $output .= "<span style=\"color:red;\">".$this->loc->s("Violation")."</span>"; break;
						default: $output .= $this->loc->s("unk"); break;
					}
					$output .= "</td></tr>";
					// Action when violation is performed
					$psviolact = $this->getPortSecViolAct();
					$output .= "<tr><td>".$this->loc->s("portsec-violmode")."</td><td>".FS::$iMgr->select("psviolact",array("tooltip" => "portsec-viol-tooltip"));
					$output .= FS::$iMgr->selElmt($this->loc->s("Shutdown"),1,$psviolact == 1 ? true : false);
					$output .= FS::$iMgr->selElmt($this->loc->s("Restrict"),2,$psviolact == 2 ? true : false);
					$output .= FS::$iMgr->selElmt($this->loc->s("Protect"),3,$psviolact == 3 ? true : false);
					$output .= "</select>";
					// Maximum MAC addresses before violation mode
					$psmaxmac = $this->getPortSecMaxMAC();
					$output .= "<tr><td>".$this->loc->s("portsec-maxmac")."</td><td>".FS::$iMgr->numInput("psmaxmac",$psmaxmac,array("size" => 4, "length" => 4, "tooltip" => "portsec-maxmac-tooltip"))."</td></tr>";
				}
			}
			return $output;
		}

		public function handlePortSecurity($logvals) {
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_portsec") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_portsec")) {

				$portsecen = $this->getPortSecEnable();

				// if not -1, portsec is available on this switch
				if($portsecen != -1) {
					$psen = FS::$secMgr->checkAndSecurisePostData("psen");

					// Modify only if different
					if($portsecen != $psen) {
						$logvals["psen"]["src"] = ($portsecen == 1 ? true : false);
						$this->setPortSecEnable($psen == "on" ? 1 : 2);
						$logvals["psen"]["dst"] = ($psen == "on" ? true : false);
					}

					$portsecvact = $this->getPortSecViolAct();
					$psviolact = FS::$secMgr->checkAndSecurisePostData("psviolact");

					// Modify only if different
					if($portsecvact != $psviolact) {
						$logvals["psviolact"]["src"] = $portsecvact;
						$this->setPortSecViolAct($psviolact);
						$logvals["psviolact"]["dst"] = $psviolact;
					}

					$psecmaxmac = $this->getPortSecMaxMAC();
					$psmaxmac = FS::$secMgr->checkAndSecurisePostData("psmaxmac");

					// Modify only if different
					if($psecmaxmac != $psmaxmac) {
						$logvals["psmaxmac"]["src"] = $psecmaxmac;
						$this->setPortSecMaxMAC($psmaxmac);
						$logvals["psmaxmac"]["dst"] = $psmaxmac;
					}
				}
			}
		}

		public function showVoiceVlanOpts($voicevlanoutput) {
			$output = "";
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_voicevlan") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_voicevlan")) {
				$output .= "<tr><td>".$this->loc->s("voice-vlan")."</td><td>";
				$output .= FS::$iMgr->select("voicevlan",array("tooltip" => "tooltip-voicevlan"));
				$output .= $voicevlanoutput;
				$output .= "</select></td></tr>";
			}
			return $output;
		}

		public function handleVoiceVlan($logvals) {
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_voicevlan") ||
                        	FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_voicevlan")) {
				$voicevlan = FS::$secMgr->checkAndSecurisePostData("voicevlan");

				$portvoicevlan = $this->getSwitchportVoiceVlan();

				// Do nothing if it's the same
				if($voicevlan == $portvoicevlan)
					return 0;

				$logvals["voicevlan"]["src"] = $portvoicevlan;
				if($this->setSwitchportVoiceVlan($voicevlan) != 0) {
					FS::$iMgr->ajaxEchoError("Fail to set switchport voice vlan","",true);
					return 1;
				}
				$logvals["voicevlan"]["dst"] = $voicevlan;
			}
			return 0;
		}

		public function showVlanOpts() {
			FS::$iMgr->js("function arangeform() {
				if(document.getElementsByName('trmode')[0].value == 1) {
					$('#vltr').show('slow');
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
			}};");
			$output = "<tr><td>".$this->loc->s("switchport-mode")."</td><td>";
			$trmode = $this->getSwitchportMode();

			$mabstate = $this->getSwitchportMABState();
			if($mabstate == 1)
				$trmode = 3;
			$output .= FS::$iMgr->select("trmode",array("js" => "arangeform()"));
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
					$nvlan = $this->getSwitchTrunkNativeVlan();
					$vllist = $this->getSwitchportTrunkVlans();
					break;
				case 2:
					$output .= $this->loc->s("Vlan");
					$nvlan = $this->getSwitchAccessVLAN();
					break;
				case 3:
					$output .= $this->loc->s("fail-vlan");
					$portoptlabel = $this->loc->s("MAB-opt");
					$nvlan = $this->getSwitchportAuthFailVLAN();
					break;
			}
			$output .= "</td><td id=\"vln\">";
			$output .= FS::$iMgr->select("nvlan");
			// Added none for VLAN fail
			if($trmode == 3)
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$nvlan == 0 ? true : false);

			$voicevlanoutput = FS::$iMgr->selElmt($this->loc->s("None"),4096);
			$voicevlan = $this->getSwitchportVoiceVlan();
			$deadvlan = $this->getSwitchportAuthDeadVLAN();
			$deadvlanoutput = "";
			$norespvlan = $this->getSwitchportAuthNoRespVLAN();
			$norespvlanoutput = "";
			$trunkvlanoutput = "";
			$trunkall = true;
			$vlannb = 0;

			$query = FS::$dbMgr->Select("device_vlan","vlan,description,creation","ip = '".$this->devip."'",array("order" => "vlan"));
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
			$output .= FS::$iMgr->select("vllist",array("multi" => true, "size" => round($vlannb/4)));
			$output .= FS::$iMgr->selElmt($this->loc->s("All"),"all",$trunkall);
			$output .= $trunkvlanoutput;
			$output .= "</select>";
			$output .= "</td></tr>";
			/*
			* MAB tables
			*/

			// NoResp Vlan
			$output .= "<tr id=\"mabnoresp\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-noresp")."</td><td>";
			$output .= FS::$iMgr->select("norespvlan",array("tooltip" => "MAB-noresp-tooltip"));
			$output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$norespvlan == 0 ? true : false);
			$output .= $norespvlanoutput;
			$output .= "</select></td></tr>";
			// Dead Vlan
			$output .= "<tr id=\"mabdead\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-dead")."</td><td>";
			$output .= FS::$iMgr->select("deadvlan",array("tooltip" => "MAB-dead-tooltip"));
			$output .= FS::$iMgr->selElmt($this->loc->s("None"),0,$deadvlan == 0 ? true : false);
			$output .= $deadvlanoutput;
			$output .= "</select></td></tr>";
			// Other options
			$output .= "<tr id=\"mabtr\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("MAB-opt")."</td><td>";
			$mabeap = $this->getSwitchportMABType();
			$dot1xhostmode = $this->getSwitchportAuthHostMode();
			$output .= FS::$iMgr->check("mabeap",array("check" => ($mabeap == 2 ? true : false)))." EAP<br />";
			$output .= $this->loc->s("Dot1x-hostm")." ".FS::$iMgr->select("dot1xhostmode");
			$output .= FS::$iMgr->selElmt($this->loc->s("single-host"),1,$dot1xhostmode == 1 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("multi-host"),2,$dot1xhostmode == 2 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("multi-auth"),3,$dot1xhostmode == 3 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("multi-domain"),4,$dot1xhostmode == 4 ? true : false);
			$output .= "</select></td></tr>";

			$output .= $this->showVoiceVlanOpts($voicevlanoutput);
			return $output;
		}

		public function handleVlan($logvals) {
			$trunk = FS::$secMgr->checkAndSecurisePostData("trmode");
			$nvlan = FS::$secMgr->checkAndSecurisePostData("nvlan");
			$port = FS::$secMgr->checkAndSecurisePostData("port");

			$logvals["accessvlan"]["src"] = $this->getSwitchAccessVLAN();
			$logvals["trunkencap"]["src"] = $this->getSwitchTrunkEncap();
			$logvals["mode"]["src"] = $this->getSwitchportMode();
			$logvals["trunkvlan"]["src"] = $this->getSwitchportTrunkVlans();
			$logvals["nativevlan"]["src"] = $this->getSwitchTrunkNativeVlan();

			// @TODO
			// Mab & 802.1X
			$mabst = $this->getSwitchportMABState();
			if($mabst != -1)
				$logvals["mabst"]["src"] = $mabst;
			$failvlan = $this->getSwitchportAuthFailVLAN();
			if($failvlan != -1)
				$logvals["failvlan"]["src"] = $failvlan;
			$norespvlan = $this->getSwitchportAuthNoRespVLAN();
			if($norespvlan != -1)
				$logvals["norespvlan"]["src"] = $norespvlan;
			$deadvlan = $this->getSwitchportAuthDeadVLAN();
			if($deadvlan != -1)
				$logvals["deadvlan"]["src"] = $deadvlan;
			$controlmode = $this->getSwitchportControlMode();
			if($controlmode != -1)
				$logvals["controlmode"]["src"] = $controlmode;
			$authhostmode = $this->getSwitchportAuthHostMode();
			if($authhostmode != -1)
				$logvals["authhostmode"]["src"] = $authhostmode;

			if($trunk == 1) {
				$vlanlist = FS::$secMgr->checkAndSecurisePostData("vllist");

				if($this->getSwitchAccessVLAN() != 1)
					$this->setSwitchAccessVLAN(1);

				$logvals["accessvlan"]["dst"] = 1;
				// mab disable
				if($mabst != -1 && $mabst != 2) {
					$this->setSwitchportMABEnable(2);
					$logvals["mabst"]["dst"] = 2;
				}
				if($failvlan != -1 && $failvlan != 0) {
					$this->setSwitchportAuthFailVLAN(0);
					$logvals["failvlan"]["dst"] = 0;
				}
				if($norespvlan != -1 && $norespvlan != 0) {
					$this->setSwitchportAuthNoRespVLAN(0);
					$logvals["norespvlan"]["dst"] = 0;
				}
				if($deadvlan != -1 && $deadvlan != 0) {
					$this->setSwitchportAuthDeadVLAN(0);
					$logvals["deadvlan"]["dst"] = 0;
				}
				if($controlmode != -1 && $controlmode != 3) {
					$this->setSwitchportControlMode(3);
					$logvals["controlmode"]["dst"] = 3;
				}
				// dot1x disable
				if($authhostmode != -1 && $authhostmode != 1) {
					$this->setSwitchportAuthHostMode(1);
					$logvals["authhostmode"]["dst"] = 1;
				}

				// set settings
				if($this->getSwitchTrunkEncap() != 4) {
					if($this->setSwitchTrunkEncap(4) != 0) {
						FS::$iMgr->redir("mod=".$this->mid."&d=".$this->device."&p=".$port."&err=2");
						return;
					}
					$logvals["trunkencap"]["dst"] = 4;
				}

				if($this->getSwitchportMode() != $trunk) {
					if($this->setSwitchportMode($trunk) != 0) {
						FS::$iMgr->redir("mod=".$this->mid."&d=".$this->device."&p=".$port."&err=2");
						return;
					}
					$logvals["mode"]["dst"] = $trunk;
				}

				if(in_array("all",$vlanlist)) {
					if($this->setSwitchNoTrunkVlan() != 0) {
						FS::$iMgr->redir("mod=".$this->mid."&d=".$this->device."&p=".$port."&err=2");
						return;
					}
				}
				else {
					if($this->setSwitchTrunkVlan($vlanlist) != 0) {
						FS::$iMgr->redir("mod=".$this->mid."&d=".$this->device."&p=".$port."&err=2");
						return;
					}
				}
				$logvals["trunkvlan"]["dst"] = $vlanlist;


				if($this->getSwitchTrunkNativeVlan() != $nvlan) {
					if($this->setSwitchTrunkNativeVlan($nvlan) != 0) {
						FS::$iMgr->redir("mod=".$this->mid."&d=".$this->device."&p=".$port."&err=2");
						return;
					}
					$logvals["nativevlan"]["dst"] = $nvlan;
				}

			} else if($trunk == 2) {
				if($this->getSwitchTrunkNativeVlan() != 1) {
					$this->setSwitchTrunkNativeVlan(1);
					$logvals["nativevlan"]["dst"] = 1;
				}

				$this->setSwitchNoTrunkVlan();
				$logvals["trunkvlan"]["dst"] = "";
				// mab disable
				if($mabst != -1 && $mabst != 2) {
					$this->setSwitchportMABEnable(2);
					$logvals["mabst"]["dst"] = 2;
				}
				if($failvlan != -1 && $failvlan != 0) {
					$this->setSwitchportAuthFailVLAN(0);
					$logvals["failvlan"]["dst"] = 0;
				}
				if($norespvlan != -1 && $norespvlan != 0) {
					$this->setSwitchportAuthNoRespVLAN(0);
					$logvals["norespvlan"]["dst"] = 0;
				}
				if($deadvlan != -1 && $deadvlan != 0) {
					$this->setSwitchportAuthDeadVLAN(0);
					$logvals["deadvlan"]["dst"] = 0;
				}
				if($controlmode != -1 && $controlmode != 3) {
					$this->setSwitchportControlMode(3);
					$logvals["controlmode"]["dst"] = 3;
				}
				// dot1x disable
				if($authhostmode != -1 && $authhostmode != 1) {
					$this->setSwitchportAuthHostMode(1);
					$logvals["authhostmode"]["dst"] = 1;
				}
				// set settings
				if($this->getSwitchportMode() != $trunk) {
					if($this->setSwitchportMode($trunk) != 0) {
						echo "Fail to set Switchport mode";
						return;
					}
					$logvals["mode"]["dst"] = $trunk;
				}

				if($this->getSwitchTrunkEncap() != 5) {
					if($this->setSwitchTrunkEncap(5) != 0) {
						echo "Fail to set Switchport Trunk encapsulated VLANs";
						return;
					}
					$logvals["trunkencap"]["dst"] = 5;
				}

				if($this->getSwitchAccessVLAN() != $nvlan) {
					if($this->setSwitchAccessVLAN($nvlan) != 0) {
						echo "Fail to set Switchport Access Vlan";
						return;
					}
					$logvals["accessvlan"]["dst"] = $nvlan;
				}

			} else if($trunk == 3) {
				$dot1xhostmode = FS::$secMgr->checkAndSecurisePostData("dot1xhostmode");
				$mabeap = FS::$secMgr->checkAndSecurisePostData("mabeap");
				$noresp = FS::$secMgr->checkAndSecurisePostData("norespvlan");
				$dead = FS::$secMgr->checkAndSecurisePostData("deadvlan");
				if($dot1xhostmode < 1 || $dot1xhostmode > 4) {
					echo "Dot1x hostmode is wrong (".$dot1xhostmode.")";
					return;
				}
				// switchport mode access & no vlan assigned
				if($this->getSwitchTrunkNativeVlan() != 1) {
					$this->setSwitchTrunkNativeVlan(1);
					$logvals["nativevlan"]["dst"] = 1;
				}

				$this->setSwitchNoTrunkVlan();
				$logvals["trunkvlan"]["dst"] = "";

				if($this->getSwitchportMode() != 2) {
					$this->setSwitchportMode(2);
					$logvals["mode"]["dst"] = 2;
				}

				if($this->getSwitchTrunkEncap() != 5) {
					$this->setSwitchTrunkEncap(5);
					$logvals["trunkencap"]["dst"] = 5;
				}

				if($this->getSwitchAccessVLAN() != 1) {
					$this->setSwitchAccessVLAN(1);
					$logvals["accessvlan"]["dst"] = 1;
				}

				// enable mab
				if($mabst != -1 && $mabst != 1) {
					$this->setSwitchportMABEnable(1);
					$logvals["mabst"]["dst"] = 1;
				}

				$mabtype = $this->getSwitchportMABType();
				if($mabtype != -1) {
					$logvals["mabtype"]["src"] = $mabtype;
					// set MAB to EAP or not
					$this->setSwitchMABType($mabeap == "on" ? 2 : 1);
					$logvals["mabtype"]["dst"] = ($mabeap == "on" ? 2 : 1);
				}

				if($failvlan != -1 && $failvlan != $nvlan) {
					// enable authfail & noresp vlans
					$this->setSwitchportAuthFailVLAN($nvlan);
					$logvals["failvlan"]["dst"] = $nvlan;
				}

				if($norespvlan != -1 && $norespvlan != $noresp) {
					$this->setSwitchportAuthNoRespVLAN($noresp);
					$logvals["norespvlan"]["dst"] = $noresp;
				}

				if($deadvlan != -1 && $deadvlan != $dead) {
					$this->setSwitchportAuthDeadVLAN($dead);
					$logvals["deadvlan"]["dst"] = $dead;
				}

				if($controlmode != -1 && $controlmode != 2) {
					// authentication port-control auto
					$this->setSwitchportControlMode(2);
					$logvals["controlmode"]["dst"] = 2;
				}

				// Host Mode for Authentication
				if($authhostmode != -1 && $authhostmode != $dot1xhostmode) {
					$this->setSwitchportAuthHostMode($dot1xhostmode);
					$logvals["authhostmode"]["dst"] = $dot1xhostmode;
				}
			}
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Update("device_port","vlan ='".$nvlan."'","ip='".$this->devip."' and port='".$port."'");
			FS::$dbMgr->Update("device_port_vlan","vlan ='".$nvlan."'","ip='".$this->devip."' and port='".$port."' and native='t'");
			FS::$dbMgr->Delete("device_port_vlan","ip = '".$this->devip."' AND port='".$port."'");
			$vllist = FS::$secMgr->checkAndSecurisePostData("vllist");
			if($trunk == 1) {
				if($vllist) {
					// Insert VLAN in database only if not in trunk All mode
					if(!in_array("all",$vllist)) {
						$count = count($vllist);
						for($i=0;$i<$count;$i++)
							FS::$dbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$this->devip."','".$port."','".$vllist[$i]."','f',NOW(),NOW()");
					}
				}
			}
			else if ($trunk == 2) {
				FS::$dbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$this->devip."','".$port."','".$nvlan."','t',NOW(),NOW()");
			}
			FS::$dbMgr->CommitTr();
		}

		public function showDHCPSnoopingOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_dhcpsnooping") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_dhcpsnooping")) {
				// DHCP snooping options
				$dhcpsntrust = $this->getPortDHCPSnoopingTrust();
				if($dhcpsntrust != NULL) {
					$output .= FS::$iMgr->idxLine("dhcp-snooping-trust-enable","dhcpsntrusten",
						array("value" => ($dhcpsntrust == 1),
							"type" => "chk", "tooltip" => "dhcp-snooping-trust-tooltip"))."</td></tr>";
				}

				$dhcpsnrate = $this->getPortDHCPSnoopingRate();
				if($dhcpsntrust != NULL) {
					$output .= FS::$iMgr->idxLine("dhcp-snooping-rate","dhcpsnrate",
						array("type" => "num", "value" => $dhcpsnrate, "size" => 4, "length" => 4,
							"tooltip" => "dhcp-snooping-rate-tooltip"));
				}
			}
			return $output;
		}

		public function handleDHCPSnooping($logvals) {
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_dhcpsnooping") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_dhcpsnooping")) {
				$dhcpsntrusten = FS::$secMgr->checkAndSecurisePostData("dhcpsntrusten");
        	                $dhcpsnrate = FS::$secMgr->checkAndSecurisePostData("dhcpsnrate");

				$dhcpsntruststate = $this->getPortDHCPSnoopingTrust();

				// Modify only if exists and different
				if($dhcpsntruststate != NULL && $dhcpsntruststate != $dhcpsntrusten) {
					$logvals["dhcpsntrusten"]["src"] = ($dhcpsntruststate == 1 ? true : false);
					$this->setPortDHCPSnoopingTrust($dhcpsntrusten == "on" ? 1 : 2);
					$logvals["dhcpsntrusten"]["dst"] = ($dhcpsntrusten == "on" ? true : false);
				}

				$dhcpsnrateorig = $this->getPortDHCPSnoopingRate();

				// Modify only if exists and different
				if($dhcpsnrateorig != NULL && $dhcpsnrateorig != $dhcpsnrate) {
					$logvals["dhcpsnrate"]["src"] = $dhcpsnrateorig;
					$this->setPortDHCPSnoopingRate($dhcpsnrate);
					$logvals["dhcpsnrate"]["dst"] = $dhcpsnrate;
				}
			}
		}

		public function showCDPOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_cdp") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_cdp")) {
				$cdp = $this->getPortCDPEnable();
				if($cdp != NULL) {
					$output .= FS::$iMgr->idxLine("cdp-enable","cdpen",array("value" => ($cdp == 1),"type" => "chk", "tooltip" => "cdp-tooltip"))."</td></tr>";
				}
			}
			return $output;
		}

		public function handleCDP($logvals) {
			if(FS::$sessMgr->hasRight("snmp_".$this->snmprw."_portmod_cdp") ||
				FS::$sessMgr->hasRight("ip_".$this->devip."_portmod_cdp")) {
				$cdpen = FS::$secMgr->checkAndSecurisePostData("cdpen");
				$cdpstate = $this->getPortCDPEnable();

				// Modify only if different
				if($cdpstate != NULL && $cdpstate != ($cdpen == "on" ? 1 : 2)) {
					$logvals["cdp"]["src"] = ($cdpstate == 1 ? true : false);
					$this->setPortCDPEnable($cdpen == "on" ? 1 : 2);
					$logvals["cdp"]["dst"] = ($cdpen == "on" ? true : false);
				}
			}
		}

		public function showSaveCfg() {
			return FS::$iMgr->idxLine("Save-switch","wr",
				array("value" => true, "type" => "chk",
					"tooltip" => "tooltip-saveone"));
		}

		public function handleSaveCfg() {
			$wr = FS::$secMgr->checkAndSecurisePostData("wr");
			if($wr == "on") {
				$this->writeMemory();
			}
		}

		public function checkFields() {
			if(!FS::$secMgr->checkAndSecurisePostData("trmode") ||
				!FS::$secMgr->checkAndSecurisePostData("nvlan"))
				return false;

			return true;
		}

		/*
		* Generic port management
		*/

		public function setPortDesc($value) {
			return $this->setFieldForPortWithPID("ifAlias","s",$value);
		}

		public function getPortDesc() {
			return $this->getFieldForPortWithPID("ifAlias");
		}

		public function setPortState($value) {
			if($value != 1 && $value != 2) {
				return NULL;
			}

			return $this->setFieldForPortWithPID("ifAdminStatus","i",$value);
		}

		public function getPortState() {
			$dup = $this->getFieldForPortWithPID("ifAdminStatus");
			$dup = preg_replace("#[a-zA-Z()]#","",$dup);
			return $dup;
		}

		/*
		* Link Management
		*/

		public function getPortMtu() {
			return $this->getFieldForPortWithPID("ifMtu");
		}

		public function setPortDuplex($value) {
			if($value < 1 || $value > 4)
				return NULL;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
		}

		public function getPortDuplex() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.522.3.15.5");
		}

		public function setPortSpeed($value) {
			if($value < 1)
				return NULL;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.5.1.4.1.1.9","i",$value);
		}

		public function getPortSpeed() {
			$idx = $this->getPortIndexes($this->device,$this->portid);
			if($idx == NULL)
				return -2;

			return $this->getFieldForPortWithPID($idx[0].".".$idx[1],"1.3.6.1.4.1.9.5.1.4.1.1.9");
		}
		/*
		* VLAN management
		*/

		public function setSwitchAccessVLAN($value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.68.1.2.2.1.2","i",$value);
		}

		public function getSwitchAccessVLAN() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.68.1.2.2.1.2");
                }

		public function setSwitchportMABEnable($value) {
			// 1: enable / 2: disable
			if($value != 1 && $value != 2)
				return 1;
			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.654.1.1.1.1.1","i",$value);
		}

		public function getSwitchportMABState() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.654.1.1.1.1.1");
		}

		public function setSwitchMABType($value) {
			// 1: normal / 2: EAP
			if($value != 1 && $value != 2)
                                return 1;
                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.654.1.1.1.1.2","i",$value);
		}

		public function getSwitchportMABType() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.654.1.1.1.1.2");
		}

		public function setSwitchportAuthFailVLAN($value) {
			// #todo disable feature
                        if(!FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID(($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.1.1.2" : "1.3.6.1.4.1.9.9.656.1.3.1.1.3"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthFailVLAN() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.3.1.1.3");
                }

		public function setSwitchportAuthNoRespVLAN($value) {
			// @todo disable feature
                        if(!FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID(($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.2.1.1" : "1.3.6.1.4.1.9.9.656.1.3.2.1.2"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthNoRespVLAN() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.3.2.1.2");
                }

		public function setSwitchportAuthDeadVLAN($value) {
                        // @todo disable feature
                        if(!FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID(($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.3.1.1" : "1.3.6.1.4.1.9.9.656.1.3.3.1.3"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthDeadVLAN() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.3.3.1.3");
                }

		// authentication port-control 1,2,3
		public function setSwitchportControlMode($value) {
			// 1: unauthorized / 2: auto / 3: authorized / 3: disable feature
                        if($value != 1 && $value != 2 && $value != 3)
                                return 1;
                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.2.1.1.5","i",$value);
                }

                public function getSwitchportControlMode() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.2.1.1.5");
                }

		// authentication host-mode
		public function setSwitchportAuthHostMode($value) {
			// 1: single-host (default) / 2: multi-host / 3: multi-auth / 4: multi-domain
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 4)
					return 1;
			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.2.1.1.3","i",$value);
		}

		public function getSwitchportAuthHostMode() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.656.1.2.1.1.3");
		}

		public function setSwitchTrunkNativeVlan($value) {
			if(!FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;

            		return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}

		public function getSwitchTrunkNativeVlan() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.5");
		}

		public function setSwitchTrunkVlan($values) {
			if(!is_array($values) && !preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;
			/*
			* For each VLAN from 1 to 4096, set bit value to 1 if vlan is allowed, else set to 0
			* Each byte is converted to a hex string, and chained
			*/
			$str = "";
			$tmpstr="";
			$count=0;
			for($i=0;$i<1024;$i++) {
				if(in_array($i,$values))
					$tmpstr .= "1";
				else
					$tmpstr .= "0";
				$count++;
				if($count == 8) {
					$tmpchar = base_convert($tmpstr,2,16);
					if(strlen($tmpchar) == 1)
						$tmpchar = "0".$tmpchar;
					$str .= $tmpchar;
					$tmpstr = "";
					$count = 0;
			        }
			}

			$tmpstr = "";
			$str2 = "";
			$count=0;
			for($i=0;$i<1024;$i++) {
				$tmpstr .= "0";
				$count++;
				if($count == 8) {
					$tmpchar = base_convert($tmpstr,2,16);
					if(strlen($tmpchar) == 1)
						$tmpchar = "0".$tmpchar;
					$str2 .= $tmpchar;
					$tmpstr = "";
					$count = 0;
				}
			}

			/*
			* There is 4 mibs, each contains 1024 vlan id
			* For now, we don't use vlanid > 1024, only 1-1024
			*/
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str2);
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str2);
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str2);
			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str);
		}

		public function setSwitchNoTrunkVlan() {
			$tmpstr1 = "0";
			$tmpstr4 = "1";
		        $str1 = "";
			$str23 = "";
			$str4 = "";
			$count=1;
			/*
			* To unset allowed vlans, set all bits to 1 for vlan 2-4095. Vlan 1 and 4096 must be set to 0
			*/
			for($i=1;$i<1023;$i++) {
				$tmpstr1 .= "1";
				$tmpstr4 .= "1";
  			        $count++;
				if($i == 1022) {
					$tmpstr1 .= "1";
					$tmpstr4 .= "0";
					$count++;
				}
                		if($count == 8) {
			                $tmpchar1 = base_convert($tmpstr1,2,16);
					$tmpchar4 = base_convert($tmpstr4,2,16);
			                $str1 .= $tmpchar1;
					$str4 .= $tmpchar4;
			                $tmpstr1 = "";
					$tmpstr4 = "";
			                $count = 0;
		               }
		        }

			$tmpstr = "";
			$str23 = "";
			$count=0;
			for($i=0;$i<1024;$i++) {
				$tmpstr .= "1";
				$count++;
				if($count == 8) {
					$tmpchar = base_convert($tmpstr,2,16);
					if(strlen($tmpchar) == 1)
					$tmpchar = "0".$tmpchar;
					$str23 .= $tmpchar;

					$tmpstr = "";
					$count = 0;
				}
			}

			$ret = $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str1);
			if($ret == -1)
				return $ret;
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str23);
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str23);
			$this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str4);
			return $ret;
		}

		public function getSwitchportTrunkVlans() {
			$vlanlist = array();
			$trunkNoVlan = true;
			$vlid = 0;
			$hstr = $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.4",true);
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($hstr != "7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			$strlen = strlen($hstr);
			for($i=0;$i<$strlen;$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				$vlanbyteslen = strlen($vlanbytes);
				for($j=0;$j<$vlanbyteslen;$j++) {
					if($vlanbytes[$j] == "1")
						$vlanlist[] = $vlid;
					$vlid++;
				}
			}
			$hstr = $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.17",true);
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			$strlen = strlen($hstr);
			for($i=0;$i<$strlen;$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				$vlanbyteslen = strlen($vlanbytes);
				for($j=0;$j<$vlanbyteslen;$j++) {
					if($vlanbytes[$j] == "1")
						$vlanlist[] = $vlid;
					$vlid++;
				}
			}
			$hstr = $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.18",true);
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			$strlen = strlen($hstr);
			for($i=0;$i<$strlen;$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				$vlanbyteslen = strlen($vlanbytes);
				for($j=0;$j<$vlanbyteslen;$j++) {
					if($vlanbytes[$j] == "1")
						$vlanlist[] = $vlid;
					$vlid++;
				}
			}
			$hstr = $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.19",true);
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE")
				$trunkNoVlan = false;
			$strlen = strlen($hstr);
			for($i=0;$i<$strlen;$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				$vlanbyteslen = strlen($vlanbytes);
				for($j=0;$j<$vlanbyteslen;$j++) {
					if($vlanbytes[$j] == "1")
						$vlanlist[] = $vlid;
					$vlid++;
				}
			}

			if($trunkNoVlan == true)
				return array();

			return $vlanlist;
		}

		public function setSwitchTrunkEncap($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
				return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.3","i",$value);
		}

		public function getSwitchTrunkEncap() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.3");
		}

		public function setSwitchportMode($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
				return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.13","i",$value);
		}

		public function getSwitchportMode() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.46.1.6.1.1.13");
		}

		public function setSwitchportVoiceVlan($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 4096)
			   return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.68.1.5.1.1.1","i",$value);
		}

		public function getSwitchportVoiceVlan() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.68.1.5.1.1.1");
		}

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($field, $vtype, $value) {
			if($this->devip == "" || $this->snmprw == "" || $field == "" || $this->portid == "" || $this->portid < 1 || $vtype == "")
				return -1;
			snmpset($this->devip,$this->snmprw,$field.".".$this->portid,$vtype,$value);
			return 0;
		}

		public function getFieldForPortWithPID($field, $raw = false) {
			if($this->devip == "" || $this->snmpro == "" || $field == "" || $this->portid == "" || $this->portid < 1)
				return -1;
			$out = "";
			exec("/usr/local/bin/snmpget -v 2c -c ".$this->snmpro." ".$this->devip." ".$field.".".$this->portid,$out);
			$outoid = "";
			for($i=0;$i<count($out);$i++) {
				$outoid .= $out[$i];
				if($i<count($out)-1) $outoid .= "";
			}
			$outoid = preg_split("# = #",$outoid);
			if(count($outoid) != 2)
				return -1;
			$outoid = $outoid[1];
			if($raw) return $outoid;

			// We cut the string type
                        $outoid = explode(" ",$outoid);
			// There are only two fields
                        if(count($outoid) != 2)
                                return -1;

                        $outoid = $outoid[1];
			return $outoid;
		}

		public function setField($field, $vtype, $value) {
			if($this->devip == "" || $this->snmprw == "" || $field == "" || $vtype == "")
				return NULL;
			snmpset($this->devip,$this->snmprw,$field,$vtype,$value);
			return 0;
		}

		public function getField($field) {
			if($this->devip == "" || $this->snmpro == "" || $field == "")
				return NULL;
			$out = "";
			exec("/usr/local/bin/snmpget -v 2c -c ".$this->snmpro." ".$this->devip." ".$field,$out);
			$outoid = "";
			for($i=0;$i<count($out);$i++) {
				$outoid .= $out[$i];
				if($i<count($out)-1) $outoid .= "";
			}
			$outoid = preg_split("# = #",$outoid);
			$outoid = $outoid[1];
			return $outoid;
		}

		public function getPortId($portname) {
			$pid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_id_cache","pid","device = '".$this->device."' AND portname = '".$portname."'");
			if($pid == NULL) $pid = -1;
			return $pid;
		}

		public function getPortIndexes() {
			if($this->device == "" || $this->portid == -1)
				return NULL;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."port_id_cache","switchid,switchportid","device = '".$this->device."' AND pid = '".$this->portid."'");
			if($data = FS::$dbMgr->Fetch($query))
				return array($data["switchid"],$data["switchportid"]);
			return NULL;
		}

		/*
		* get Port list from a device. If there is a filter, only port with specified vlan are returned
		*/

		public function getPortList($vlanFltr = NULL) {
			if($this->devip == "" || $this->snmpro == "")
				return -1;
			$out = "";
			exec("/usr/local/bin/snmpwalk -v 2c -c ".$this->snmpro." ".$this->devip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			$plist = array();
			$count = count($out);
			for($i=0;$i<$count;$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				if($vlanFltr == NULL || !FS::$secMgr->isNumeric($vlanFltr) || $vlanFltr < 1 || $vlanFltr > 4096)
					$plist[] = $pname;
				else {
					$this->setPortId($pid);
					$portmode = $this->getSwitchportMode();
					if($portmode == 1) {
						$nvlan = $this->getSwitchTrunkNativeVlan();
						if(!in_array($pname,$plist) && $vlanFltr == $nvlan)
							$plist[] = $pname;

						$vllist = $this->getSwitchportTrunkVlans();
						if(!in_array($pname,$plist) && in_array($vlanFltr,$vllist))
							$plist[] = $pname;
					}
					else if($portmode == 2) {
						$pvlan = $this->getSwitchAccessVLAN();
						if(!in_array($pname,$plist) && $vlanFltr == $pvlan)
							$plist[] = $pname;
					}
				}
			}
			return $plist;
		}

		public function replaceVlan($oldvlan,$newvlan) {
			if($this->devip == "" || $this->snmpro == "")
				return -1;
			$out = "";
			exec("snmpwalk -v 2c -c ".$this->snmpro." ".$this->devip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			$count = count($out);
			for($i=0;$i<$count;$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				$this->setPortId($pid);
				$portmode = $this->getSwitchportMode();
				if($portmode == 1) {
					$nvlan = $this->getSwitchTrunkNativeVlan();
					if($oldvlan == $nvlan)
						$this->setSwitchTrunkNativeVlan($newvlan);

					$vllist = $this->getSwitchportTrunkVlans();
					if(in_array($oldvlan,$vllist)) {
						$vllist2 = array();
						$countvl = count($vllist);
						for($j=0;$j<$countvl;$j++) {
							if($vllist[$j] != $oldvlan)
								$vllist2[] = $vllist[$j];
						}
						$vllist2[] = $newvlan;
						$this->setSwitchTrunkVlan($vllist2);
					}
				}
				else if($portmode == 2) {
					$pvlan = $this->getSwitchAccessVLAN();
					if($oldvlan == $pvlan)
						$this->setSwitchAccessVLAN($newvlan);
				}
			}
		}

		// Saving running-config => startup-config
		public function writeMemory() {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","4");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}

		public function restoreStartupConfig() {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","4");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}

		// Save startup-config to TFTP Server
		public function exportConfigToTFTP($server,$path) {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}

		// Restore startup-config to TFTP Server
		public function importConfigFromTFTP($server,$path) {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}

		// Save startup-config to FTP/SCP/SFTP Server
		public function exportConfigToAuthServer($server,$type,$path,$user,$pwd) {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i",$type);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.".$rand,"s",$user);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.".$rand,"s",$pwd);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}

		// Restore startup-config to FTP/SCP/SFTP Server
		public function importConfigFromAuthServer($server,$type,$path,$user,$pwd) {
			if($this->devip == "" || $this->snmprw == "")
				return -1;
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i",$type);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","1");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.".$rand,"s",$user);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.".$rand,"s",$pwd);
			snmpset($this->devip,$this->snmprw,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}

		// Get Copy state from switch, using previous randomized id
		public function getCopyState($copyId) {
			if($this->devip == "" || $this->snmpro == "")
				return -1;
			$res = snmpget($this->devip,$this->snmpro,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$copyId);
			$res = preg_split("# #",$res);
			if(count($res) > 1)
				return $res[1];
			else
				return NULL;
		}

		public function getCopyError($copyId) {
			if($this->devip == "" || $this->snmpro == "")
				return -1;
			$res = snmpget($this->devip,$this->snmpro,"1.3.6.1.4.1.9.9.96.1.1.1.1.13.".$copyId);
			$res = preg_split("# #",$res);
			return $res[1];
		}

		/*
		* Port Security
		*/

		public function getPortSecStatus() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.2");
                }

		public function getPortSecEnable() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.1");
                }

		public function setPortSecEnable($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                                return -1;

                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.1","i",$value);
                }

		public function getPortSecViolAct() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.8");
                }

		public function setPortSecViolAct($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 3)
				return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.8","i",$value);
		}

		public function getPortSecMaxMAC() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.3");
		}

		public function setPortSecMaxMAC($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 6144)
				return -1;

			return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.315.1.2.1.1.3","i",$value);
		}

		/*
		* special
		*/

		public function getPortCDPEnable() {
			return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.23.1.1.1.1.2");
		}

		public function setPortCDPEnable($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.23.1.1.1.1.2","i",$value);
                }

		/*
		* DHCP Snooping
		*/

		public function getPortDHCPSnoopingTrust() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.380.1.3.1.1.1");
		}

		public function setPortDHCPSnoopingTrust($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.380.1.3.1.1.1","i",$value);
		}

		public function getPortDHCPSnoopingRate() {
                        return $this->getFieldForPortWithPID("1.3.6.1.4.1.9.9.380.1.3.2.1.1");
		}

		public function setPortDHCPSnoopingRate($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 0 || $value > 2048)
                        	return -1;

                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.380.1.3.2.1.1","u",$value);
		}

		public function getDHCPSnoopingStatus() {
                        $state = $this->getField("1.3.6.1.4.1.9.9.380.1.1.1.0");
                        $state = explode(" ",$state);
                        if(count($state) != 2)
                                return NULL;

                        $state = $state[1];
                        return $state;
                }

                public function setDHCPSnoopingStatus($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

                        return $this->setField("1.3.6.1.4.1.9.9.380.1.1.1.0","i",$value);
                }

                public function getDHCPSnoopingOpt82() {
                        $state = $this->getField("1.3.6.1.4.1.9.9.380.1.1.4.0");
                        $state = explode(" ",$state);
                        if(count($state) != 2)
                                return NULL;

                        $state = $state[1];
                        return $state;
                }

                public function setDHCPSnoopingOpt82($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;
			// TO TEST
                        return $this->setField("1.3.6.1.4.1.9.9.380.1.1.4.0","i",$value);
                }

		public function getDHCPSnoopingMatchMAC() {
			$state = $this->getField("1.3.6.1.4.1.9.9.380.1.1.6.0");
			$state = explode(" ",$state);
			if(count($state) != 2) {
				return NULL;
			}

			$state = $state[1];
			return $state;
		}

		public function setDHCPSnoopingMatchMAC($value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2) {
				return -1;
			}

			return $this->setField("1.3.6.1.4.1.9.9.380.1.1.6.0","i",$value);
		}

		public function getDHCPSnoopingVlans() {
			if($this->devip == "" || $this->snmpro == "")
				return -1;
			$vlanlist = array();
			exec("/usr/local/bin/snmpwalk -v 2c -c ".$this->snmpro." ".$this->devip." 1.3.6.1.4.1.9.9.380.1.2.1.1.2",$out);
			$count = count($out);
			for($i=0;$i<count($out);$i++) {
				$tmpout = preg_split("# #",$out[$i]);
				$enable = $tmpout[3];

				$vlanid = preg_split("#[\.]#",$tmpout[0]);
				$vlanid = $vlanid[count($vlanid)-1];

				$vlanlist[$vlanid] = $enable;
			}
			return $vlanlist;
		}

		public function setDHCPSnoopingVlans($vlans) {
			if($vlans == NULL || !is_array($vlans))
				return NULL;

			foreach($vlans as $vlan => $value)
				$this->setDHCPSnoopingOnVlan($vlan,$value);

			return 0;
		}

		public function setDHCPSnoopingOnVlan($vlan,$value) {
                        if(!FS::$secMgr->isNumeric($vlan) || $vlan == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

                        return $this->setFieldForPortWithPID("1.3.6.1.4.1.9.9.380.1.2.1.1.2.".$vlan,"i",$value);
		}

		/*
		* SSH commands
		*/

		public function showSSHRunCfg() {
			return $this->ssh->sendCmd("show running-config");
		}

		public function showSSHStartCfg() {
			return $this->ssh->sendCmd("show startup-config");
		}
		
		public function showSSHInterfaceCfg($iface) {
			return $this->ssh->sendCmd("show running-config interface ".$iface);
		}
		
		public function showSSHInterfaceStatus($iface) {
			return $this->ssh->sendCmd("show interface ".$iface);
		}

		public function connectToDevice($device,$sshuser,$sshpwd,$enablepwd) {
			$this->ssh = new SSH($device);
			if(!$this->ssh->Connect())
				return 1;
			if(!$this->ssh->Authenticate($sshuser, $sshpwd))
				return 2;

			$this->ssh->OpenShell();
			if(!$this->ssh->tryPrivileged("enable",$enablepwd,"% Access denied\r\n"))
				return 3;
			return 0;
		}

		private $ssh;
	}
?>
