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
	
	class CiscoAPI extends DeviceAPI {
		function CiscoAPI() { $this->vendor = "cisco"; }

		/*
		* Generic port management
		*/

		public function setPortDescWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"ifAlias","s",$value);
		}

		public function setPortStateWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || ($value != 1 && $value != 2))
				return NULL;

			return $this->setFieldForPortWithPID($device,$pid,"ifAdminStatus","i",$value);
		}

		public function getPortStateWithPID($device,$pid) {
			$dup = $this->getFieldForPortWithPID($device,$pid,"ifAdminStatus");
			$dup = explode(" ",$dup);
			if(count($dup) != 2)
					return -1;

			$dup = $dup[1];
			$dup = preg_replace("#[a-zA-Z()]#","",$dup);
			return $dup;
		}

		/*
		* Link Management
		*/

		public function getPortMtuWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"ifMtu");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                        	return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function setPortDuplexWithPID($device,$pid,$value) {
			if($value < 1 || $value > 4)
				return NULL;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
		}

		public function getPortDuplexWithPID($device,$pid) {
			$dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.522.3.15.5");
			$dup = explode(" ",$dup);
			if(count($dup) != 2)
					return -1;

			$dup = $dup[1];
			return $dup;
		}

		public function setPortSpeedWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value < 1)
					return NULL;
			
			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.9","i",$value);
		}
		
		public function getPortSpeedWithPID($device,$pid) {
			$idx = $this->getPortIndexes($device,$pid);
			if($idx == NULL)
				return -2;

			$dup = $this->getFieldForPortWithPID($device,$idx[0].".".$idx[1],"1.3.6.1.4.1.9.5.1.4.1.1.9");
			$dup = explode(" ",$dup);
			if(count($dup) != 2)
				return -1;
			
			$dup = $dup[1];
			return $dup;
		}
		/*
		* VLAN management
		*/

		public function setSwitchAccessVLANWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value))
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2","i",$value);
		}

		public function getSwitchAccessVLANWithPID($device,$pid) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

                        $ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2");
                        $vlan = explode(" ",$ret);
                        if(count($vlan) != 2)
                                return -1;

                        $vlan = $vlan[1];
                        return $vlan;
                }

		public function setSwitchportMABEnableWithPID($device,$pid,$value) {
			// 1: enable / 2: disable
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value != 1 && $value != 2)
				return 1;
			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.654.1.1.1.1.1","i",$value);
		}

		public function getSwitchportMABState($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.654.1.1.1.1.1");
			$state = explode(" ",$ret);
			if(count($state) != 2)
				return -1;

			$state = $state[1];
			return $state;
		}

		public function setSwitchMABTypeWithPID($device,$pid,$value) {
			// 1: normal / 2: EAP
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value != 1 && $value != 2)
                                return 1;
                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.654.1.1.1.1.2","i",$value);
		}

		public function getSwitchportMABType($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.654.1.1.1.1.2");
			$type = explode(" ",$ret);
			if(count($type) != 2)
				return -1;

			$type = $type[1];
			return $type;
		}

		public function setSwitchportAuthFailVLAN($device,$pid,$value) {
			// #todo disable feature
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID($device,$pid,($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.1.1.2" : "1.3.6.1.4.1.9.9.656.1.3.1.1.3"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthFailVLAN($device,$pid) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

                        $ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.3.1.1.3");
                        $vlan = explode(" ",$ret);
                        if(count($vlan) != 2)
                                return -1;

                        $vlan = $vlan[1];
                        return $vlan;
                }

		public function setSwitchportAuthNoRespVLAN($device,$pid,$value) {
			// @todo disable feature
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID($device,$pid,($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.2.1.1" : "1.3.6.1.4.1.9.9.656.1.3.2.1.2"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthNoRespVLAN($device,$pid) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

                        $ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.3.2.1.2");
                        $vlan = explode(" ",$ret);
                        if(count($vlan) != 2)
                                return -1;

                        $vlan = $vlan[1];
                        return $vlan;
                }

		public function setSwitchportAuthDeadVLAN($device,$pid,$value) {
                        // @todo disable feature
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 4096)
                                return 1;
                        return $this->setFieldForPortWithPID($device,$pid,($value == 0 ? "1.3.6.1.4.1.9.9.656.1.3.3.1.1" : "1.3.6.1.4.1.9.9.656.1.3.3.1.3"),"i",($value == 0 ? 1 : $value));
                }

                public function getSwitchportAuthDeadVLAN($device,$pid) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

                        $ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.3.3.1.3");
                        $vlan = explode(" ",$ret);
                        if(count($vlan) != 2)
                                return -1;

                        $vlan = $vlan[1];
                        return $vlan;
                }

		// authentication port-control 1,2,3
		public function setSwitchportControlMode($device,$pid,$value) {
			// 1: unauthorized / 2: auto / 3: authorized / 3: disable feature
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value != 1 && $value != 2 && $value != 3)
                                return 1;
                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.2.1.1.5","i",$value);
                }

                public function getSwitchportControlMode($device,$pid) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                                return -1;

                        $ret = getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.2.1.1.5");
                        $val = explode(" ",$ret);
                        if(count($val) != 2)
                                return -1;

                        $val = $val[1];
                        return $val;
                }

		// authentication host-mode
		public function setSwitchportAuthHostMode($device,$pid,$value) {
			// 1: single-host (default) / 2: multi-host / 3: multi-auth / 4: multi-domain
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 4)
					return 1;
			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.2.1.1.3","i",$value);
		}
				
		public function getSwitchportAuthHostMode($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
					return -1;   
					
			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.656.1.2.1.1.3");
			$val = explode(" ",$ret);
			if(count($val) != 2)
					return -1;

			$val = $val[1];
			return $val;
		}

		public function setSwitchTrunkNativeVlanWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;

            		return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}
		
		public function getSwitchTrunkNativeVlanWithPID($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5");
			$vlan = explode(" ",$ret);
            		if(count($vlan) != 2)
				return -1;

			$vlan = $vlan[1];
			return $vlan; 
		}
		
		public function setSwitchTrunkVlanWithPID($device,$pid,$values) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || (!is_array($values) && !preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values)))
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
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str2);
			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str);
		}

		public function setSwitchNoTrunkVlanWithPID($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

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

			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str23);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str23);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str4);
			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str1);
		}

		public function getSwitchportTrunkVlansWithPid($device,$pid) {
			$vlanlist = array();
			$trunkNoVlan = true;
			$vlid = 0;
			$hstr = $this->getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4");
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
			$hstr = $this->getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17");
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
			$hstr = $this->getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18");
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
			$hstr = $this->getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19");
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

		public function setSwitchTrunkEncapWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
					return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.3","i",$value);
		}
		
		public function getSwitchTrunkEncapWithPID($device, $pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
		                  return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.3");
			$state = explode(" ",$ret);
			if(count($state) != 2)
					return -1;

			$state = $state[1];
			return $state;
		}

		public function setSwitchportModeWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13","i",$value);
		}

		public function getSwitchportModeWithPID($device, $pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
		                  return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13");
			$state = explode(" ",$ret);
			if(count($state) != 2)
					return -1;

			$state = $state[1];
			return $state;
		}

		public function setSwitchportVoiceVlanWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 4096)
			   return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.5.1.1.1","i",$value);
		}

		public function getSwitchportVoiceVlanWithPID($device, $pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$ret = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.5.1.1.1");
			$value = explode(" ",$ret);
			if(count($value) != 2)
				return -1;

			$value = $value[1];
			return $value;
		}

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($device, $pid, $field, $vtype, $value) {
			if($device == "" || $field == "" || $pid == "" || $vtype == "" || !FS::$secMgr->isNumeric($pid))
				return -1;
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
                        if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,$field.".".$pid,$vtype,$value);
			return 0;
		}

		public function getFieldForPortWithPID($device, $pid, $field) {
			if($device == "" || $field == "" || $pid == "" /*|| !FS::$secMgr->isNumeric($pid)*/)
				return -1;
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			$out = "";
			exec("/usr/local/bin/snmpget -v 2c -c ".$community." ".$dip." ".$field.".".$pid,$out);
			$outoid = "";
			for($i=0;$i<count($out);$i++) {
				$outoid .= $out[$i];
				if($i<count($out)-1) $outoid .= "";
			}
			$outoid = preg_split("# = #",$outoid);
			$outoid = $outoid[1];
			return $outoid;
		}

		public function getPortId($device,$portname) {
			$pid = FS::$dbMgr->GetOneData("z_eye_port_id_cache","pid","device = '".$device."' AND portname = '".$portname."'");
			if($pid == NULL) $pid = -1;
			return $pid;
		}

		public function getPortIndexes($device,$pid) {
			$query = FS::$dbMgr->Select("z_eye_port_id_cache","switchid,switchportid","device = '".$device."' AND pid = '".$pid."'");
			if($data = FS::$dbMgr->Fetch($query))
				return array($data["switchid"],$data["switchportid"]);
			return NULL;
		}

		/*
		* get Port list from a device. If there is a filter, only port with specified vlan are returned
		*/

		public function getPortList($device,$vlanFltr = NULL) {
			$out = "";
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("/usr/local/bin/snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
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
					$portmode = $this->getSwitchportModeWithPID($device,$pid);
					if($portmode == 1) {
						$nvlan = $this->getSwitchTrunkNativeVlanWithPID($device,$pid);
						if(!in_array($pname,$plist) && $vlanFltr == $nvlan)
							array_push($plist,$pname);

						$vllist = $this->getSwitchportTrunkVlansWithPid($device,$pid);
						if(!in_array($pname,$plist) && in_array($vlanFltr,$vllist))
							array_push($plist,$pname);
					}
					else if($portmode == 2) {
						$pvlan = $this->getSwitchAccessVLANWithPID($device,$pid);
						if(!in_array($pname,$plist) && $vlanFltr == $pvlan)
							array_push($plist,$pname);
					}
				}
			}
			return $plist;
		}

		public function replaceVlan($device,$oldvlan,$newvlan) {
			$out = "";
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			$count = count($out);
			for($i=0;$i<$count;$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				$portmode = $this->getSwitchportModeWithPID($device,$pid);
				if($portmode == 1) {
					$nvlan = $this->getSwitchTrunkNativeVlanWithPID($device,$pid);
					if($oldvlan == $nvlan)
						$this->setSwitchTrunkNativeVlanWithPID($device,$pid,$newvlan);

					$vllist = $this->getSwitchportTrunkVlansWithPid($device,$pid);
					if(in_array($oldvlan,$vllist)) {
						$vllist2 = array();
						$countvl = count($vllist);
						for($j=0;$j<$countvl;$j++) {
							if($vllist[$j] != $oldvlan)
								array_push($vllist2,$vllist[$j]);
						}
						array_push($vllist2,$newvlan);
						$this->setSwitchTrunkVlanWithPID($device,$pid,$vllist2);
					}
				}
				else if($portmode == 2) {
					$pvlan = $this->getSwitchAccessVLANWithPID($device,$pid);
					if($oldvlan == $pvlan)
						$this->setSwitchAccessVLANWithPID($device,$pid,$newvlan);
				}
			}
		}
		
		// Saving running-config => startup-config
		public function writeMemory($device) {
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","4");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}

		public function restoreStartupConfig($device) {
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","4");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}

		// Save startup-config to TFTP Server
		public function exportConfigToTFTP($device,$server,$path) {
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}
		
		// Restore startup-config to TFTP Server
		public function importConfigFromTFTP($device,$server,$path) {
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;
		}
		
		// Save startup-config to FTP/SCP/SFTP Server
		public function exportConfigToAuthServer($device,$server,$type,$path,$user,$pwd) {
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i",$type);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.".$rand,"s",$user);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.".$rand,"s",$pwd);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;	
		}
		
		// Restore startup-config to FTP/SCP/SFTP Server
		public function importConfigFromAuthServer($device,$server,$type,$path,$user,$pwd) {
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i",$type);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.".$rand,"a",$server);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.".$rand,"s",$path);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.".$rand,"s",$user);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.".$rand,"s",$pwd);
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			return $rand;	
		}
		
		// Get Copy state from switch, using previous randomized id
		public function getCopyState($device,$copyId) {
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			$res = snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$copyId);
			$res = preg_split("# #",$res);
			return $res[1];
		}
		
		public function getCopyError($device,$copyId) {
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			$res = snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.13.".$copyId);
			$res = preg_split("# #",$res);
			return $res[1];
		}
		
		/*
		* helpers
		*/
		
		public function setPortState($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setPortStateWithPID($device,$pid,"1.3.6.1.2.1.2.2.1.7","i",$value);
		}
		
		public function setPortDesc($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setPortDescWithPID($device,$pid,$value);
		}
		
		public function getPortDesc($device,$portname) {
			return $this->getFieldForPort($device, $portname, "ifAlias");
		}
		
		public function setSwitchportMode($device, $portname, $value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
			return -1;

	        	return $this->setSwitchportModeWithPID($device,$pid,$value);
		}
		
		public function getSwitchportMode($device, $portname, $value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

	            return $this->getSwitchportModeWithPID($device,$pid,$value);
		}
		
		public function setSwitchNoTrunkVlan($device,$portname) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setSwitchNoTrunkVlanWithPID($device,$pid);
		}
		
		public function setSwitchTrunkNativeVlan($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;
			
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setSwitchTrunkNativeVlanWithPID($device,$pid,$value);
		}
		
		public function setSwitchTrunkVlan($device,$portname,$values) {
			if(!preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setSwitchTrunkVlanWithPID($device,$pid,$values);
		}
		
		public function setSwitchAccessVLAN($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setSwitchAccessVLANWithPID($device,$pid,$value);
		}
		
		public function getSwitchAccessVLAN($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->getSwitchAccessVLANWithPID($device,$pid);
		}
		
		public function getSwitchportTrunkVlans($device,$portname) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;
			return $this->getSwitchportTrunkVlansWithPid($device,$pid);
		}
		
		public function setSwitchTrunkEncap($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setSwitchTrunkEncapWithPID($device,$pid,$value);
		}

		/*
		* Port Security
		*/

		public function getPortSecStatusWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.2");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                                return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function getPortSecEnableWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.1");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                                return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function setPortSecEnableWithPID($device,$pid,$value) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                                return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.1","i",$value);
                }

		public function getPortSecViolActWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.8");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                                return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function setPortSecViolActWithPID($device,$pid,$value) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 3)
                                return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.8","i",$value);
                }

		public function getPortSecMaxMACWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.3");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                                return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function setPortSecMaxMACWithPID($device,$pid,$value) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 6144)
                                return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.315.1.2.1.1.3","i",$value);
                }

		/*
		* special
		*/

		public function getPortCDPEnableWithPID($device,$pid) {
                        $dup = $this->getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.23.1.1.1.1.2");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                                return -1;

                        $dup = $dup[1];
                        return $dup;
                }

		public function setPortCDPEnableWithPID($device,$pid,$value) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 2)
                        	return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.23.1.1.1.1.2","i",$value);
                }

		public function setFieldForPort($device, $portname, $field, $vtype, $value) {
			if($device == "" || $portname == "" || $field == "" || $vtype == "")
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,$field,$vtype,$value);
		}

		public function getFieldForPort($device, $portname, $field) {
			if($device == "" || $portname == "" || $field == "")
				return NULL;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return NULL;

			return $this->getFieldForPortWithPid($device,$pid,$field);
		}

		/*
		* SSH commands
		*/

		public function showSSHRunCfg($stdio) {
			return $this->sendSSHCmd($stdio,"show running-config");
		}

		public function showSSHStartCfg($stdio) {
			return $this->sendSSHCmd($stdio,"show startup-config");
		}

		public function connectToDevice($device,$sshuser,$sshpwd,$enablepwd) {
			$conn = ssh2_connect($device,22);
			if(!$conn)
				return 1;

			if(!ssh2_auth_password($conn, $sshuser, $sshpwd))
				return 2;

			$stdio = @ssh2_shell($conn,"xterm");
			fwrite($stdio,"enable\n");
			usleep(500000);
			while($line = fgets($stdio)) {}

			fwrite($stdio,$enablepwd."\n");
			usleep(500000);
			while($line = fgets($stdio)) {
				if($line == "% Access denied\r\n")
        	                	return 3;
        		}
			return $stdio;
		}

		public function sendSSHCmd($stdio, $cmd) {
			$output = "";
			$output_arr = array();
			$firstline = true;

			fwrite($stdio,$cmd."\n");
			usleep(500000);

			while($line = fgets($stdio)) {
				if($firstline) $firstline = false;
				else if(preg_match("# --More-- #",$line)) {
					fwrite($stdio," ");
					usleep(50000);
				}
				else array_push($output_arr,$line);
        		}

			for($i=0;$i<count($output_arr)-2;$i++)
				$output .= $output_arr[$i];
			return $output;
		}		
	}
?>
