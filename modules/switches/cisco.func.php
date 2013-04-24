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

	require_once(dirname(__FILE__)."/device.api.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/SSH.FS.class.php");
	
	class CiscoAPI extends DeviceAPI {
		function CiscoAPI() { $this->vendor = "cisco"; }

		/*
		* Interface functions
		*/
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
					FS::$log->i(FS::$sessMgr->getUserName(),"switches",2,"Some fields are wrong: duplex (plug edit)");
					FS::$iMgr->ajaxEcho("Duplex field is wrong (".$duplex.")");
					return;
				}

				if($idx != NULL) {
					$logvals["duplex"]["src"] = $this->devapi->getPortDuplex();
					$this->devapi->setPortDuplex($duplex);
					$logvals["duplex"]["dst"] = $duplex;
				}
			}
		}

		public function showPortSecurityOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_portsec") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_portsec")) {
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
					$output .= "<tr><td>".$this->loc->s("portsec-violmode")."</td><td>".FS::$iMgr->select("psviolact","",NULL,false,array("tooltip" => "portsec-viol-tooltip"));
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
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_portsec") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_portsec")) {
				$portsecen = $this->getPortSecEnable();
				if($portsecen != -1) {
					$psen = FS::$secMgr->checkAndSecurisePostData("psen");
					$logvals["psen"]["src"] = ($portsecen == 1 ? true : false);
					$this->setPortSecEnable($psen == "on" ? 1 : 2);
					$logvals["psen"]["dst"] = ($psen == "on" ? true : false);

					$portsecvact = $this->getPortSecViolAct();
					$psviolact = FS::$secMgr->checkAndSecurisePostData("psviolact");
					$logvals["psviolact"]["src"] = $portsecvact;
					$this->setPortSecViolAct($psviolact);
					$logvals["psviolact"]["dst"] = $psviolact;

					$psecmaxmac = $this->getPortSecMaxMAC();
					$psmaxmac = FS::$secMgr->checkAndSecurisePostData("psmaxmac");
					$logvals["psmaxmac"]["src"] = $psecmaxmac;
					$this->setPortSecMaxMAC($psmaxmac);
					$logvals["psmaxmac"]["dst"] = $psmaxmac;
				}
			}
		}

		public function showVoiceVlanOpts($voicevlanoutput) {
			$output = "";
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_voicevlan") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_voicevlan")) {
				$output .= "<tr><td>".$this->loc->s("voice-vlan")."</td><td>";
				$output .= FS::$iMgr->select("voicevlan","",null,false,array("tooltip" => "tooltip-voicevlan"));
				$output .= $voicevlanoutput;
				$output .= "</select></td></tr>";
			}
			return $output;
		}

		public function handleVoiceVlan($logvals,$voicevlan) {
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_voicevlan") ||
                        	FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_voicevlan")) {
				$logvals["voicevlan"]["src"] = $this->devapi->getSwitchportVoiceVlan();
				if($this->setSwitchportVoiceVlan($voicevlan) != 0) {
					FS::$iMgr->ajaxEcho("Fail to set switchport voice vlan");
					return 1;
				}
				$logvals["voicevlan"]["dst"] = $voicevlan;
			}
			return 0;
		}

		public function showDHCPSnoopingOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_dhcpsnooping") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_dhcpsnooping")) {
				// DHCP snooping options
				$dhcpsntrust = $this->getPortDHCPSnoopingTrust();
				if($dhcpsntrust != NULL) {
					$output .= FS::$iMgr->idxLine($this->loc->s("dhcp-snooping-trust-enable"),"dhcpsntrusten",
						$dhcpsntrust == 1 ? true : false,
						array("type" => "chk", "tooltip" => "dhcp-snooping-trust-tooltip"))."</td></tr>";
				}

				$dhcpsnrate = $this->getPortDHCPSnoopingRate();
				if($dhcpsntrust != NULL) {
					$output .= FS::$iMgr->idxLine($this->loc->s("dhcp-snooping-rate"),"dhcpsnrate","",
						array("type" => "num", "value" => $dhcpsnrate, "size" => 4, "length" => 4, 
							tooltip" => "dhcp-snooping-rate-tooltip"))."</td></tr>";
				}
			}
			return $output;
		}

		public function handleDHCPSnooping($logvals,$dhcpsntrusten,dhcpsnrate) {
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_dhcpsnooping") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_dhcpsnooping")) {
				$dhcpsntruststate = $this->getPortDHCPSnoopingTrust();
				if($dhcpsntruststate != NULL) {
					$logvals["dhcpsntrusten"]["src"] = ($dhcpsntruststate == 1 ? true : false);
					$this->setPortDHCPSnoopingTrust($dhcpsntrusten == "on" ? 1 : 2);
					$logvals["dhcpsntrusten"]["dst"] = ($dhcpsntrusten == "on" ? true : false);
				}

				$dhcpsnrateorig = $this->getPortDHCPSnoopingRate();
				if($dhcpsnrateorig != NULL) {
					$logvals["dhcpsnrate"]["src"] = $dhcpsnrateorig;
					$this->setPortDHCPSnoopingRate($dhcpsnrate);
					$logvals["dhcpsnrate"]["dst"] = $dhcpsnrate;
				}
			}
		}

		public function showCDPOpts() {
			$output = "";
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_cdp") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_cdp")) {
				$cdp = $this->getPortCDPEnable();
				if($cdp != NULL) {
					$output .= FS::$iMgr->idxLine($this->loc->s("cdp-enable"),"cdpen",$cdp == 1 ? true : false,array("type" => "chk", "tooltip" => "cdp-tooltip"))."</td></tr>";
				}
			}
			return $output;
		}

		public function handleCDP($logvals) {
			if(FS::$sessMgr->hasRight("mrule_switchmgmt_snmp_".$snmprw."_portmod_cdp") ||
				FS::$sessMgr->hasRight("mrule_switchmgmt_ip_".$dip."_portmod_cdp")) {
				$cdpen = FS::$secMgr->checkAndSecurisePostData("cdpen");
				$cdpstate = $this->getPortCDPEnable();
				if($cdpstate != NULL) {
					$logvals["cdp"]["src"] = ($cdpstate == 1 ? true : false);
					$this->setPortCDPEnable($cdpen == "on" ? 1 : 2);
					$logvals["cdp"]["dst"] = ($cdpen == "on" ? true : false);
				}
			}
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
			if($value != 1 && $value != 2)
				return NULL;

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
						array_push($vlanlist,$vlid);
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
						array_push($vlanlist,$vlid);
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
						array_push($vlanlist,$vlid);
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
						array_push($vlanlist,$vlid);
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
					array_push($plist,$pname);
				else {
					$this->setPortId($pid);
					$portmode = $this->getSwitchportMode();
					if($portmode == 1) {
						$nvlan = $this->getSwitchTrunkNativeVlan();
						if(!in_array($pname,$plist) && $vlanFltr == $nvlan)
							array_push($plist,$pname);

						$vllist = $this->getSwitchportTrunkVlans();
						if(!in_array($pname,$plist) && in_array($vlanFltr,$vllist))
							array_push($plist,$pname);
					}
					else if($portmode == 2) {
						$pvlan = $this->getSwitchAccessVLAN();
						if(!in_array($pname,$plist) && $vlanFltr == $pvlan)
							array_push($plist,$pname);
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
								array_push($vllist2,$vllist[$j]);
						}
						array_push($vllist2,$newvlan);
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
                        if(count($state) != 2)
                                return NULL;

                        $state = $state[1];
                        return $state;
                }

                public function setDHCPSnoopingMatchMAC($value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

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
