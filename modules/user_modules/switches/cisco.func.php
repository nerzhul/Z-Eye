<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	
		/*
		* Generic port management
		*/

		function setPortDescWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			return setFieldForPortWithPID($device,$pid,"ifAlias","s",$value);
		}

		function setPortStateWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || ($value != 1 && $value != 2))
				return NULL;

			return setFieldForPortWithPID($device,$pid,"1.3.6.1.2.1.2.2.1.7","i",$value);
		}

		/*
		* Link Management
		*/
		
		function setPortDuplexWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value < 1 || $value > 4)
				return NULL;

			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
		}
		
		/*
		* VLAN management
		*/
		
		function setSwitchAccessVLANWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value))
				return -1;

			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2","i",$value);
		}
		
		function getSwitchAccessVLANWithPID($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$ret = getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2");
			$vlan = explode(" ",$ret);
			if(count($vlan) != 2)
				return -1;

			$vlan = $vlan[1];
			return $vlan;
		}
		
		function setSwitchTrunkNativeVlanWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;

            		return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}
		
		function getSwitchTrunkNativeVlanWithPID($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$ret = getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5");
			$vlan = explode(" ",$ret);
            		if(count($vlan) != 2)
				return -1;

			$vlan = $vlan[1];
			return $vlan; 
		}
		
		function setSwitchTrunkVlanWithPID($device,$pid,$values) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || (!is_array($values) && !preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values)))
				return -1;
			if(!is_array($values))
				$res = preg_split("/,/",$values);
			else
				$res = $values;
			/* 
			* For each VLAN from 1 to 4096, set bit value to 1 if vlan is allowed, else set to 0
			* Each byte is converted to a hex string, and chained
			*/
			$str = "";
			$tmpstr="";
			$count=0;
			for($i=0;$i<1024;$i++) {
				if(in_array($i,$res))
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
			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str2);
			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str2);
			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str2);
			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str);
		}
		
		function setSwitchNoTrunkVlanWithPID($device,$pid) {
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

			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str23);
			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str23);
			setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str4);
			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str1);
		}
		
		function getSwitchportTrunkVlansWithPid($device,$pid) {
			$vlanlist = array();
			$trunkNoVlan = true;
			$vlid = 0;
			$hstr = getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4");
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($hstr != "7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				for($j=0;$j<strlen($vlanbytes);$j++) {
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
					$vlid++;
				}
			}
			$hstr = getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17");
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				for($j=0;$j<strlen($vlanbytes);$j++) {
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
					$vlid++;
				}
			}
			$hstr = getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18");
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
				$trunkNoVlan = false;
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				for($j=0;$j<strlen($vlanbytes);$j++) {
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
					$vlid++;
				}
			}
			$hstr = getFieldForPortWithPid($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19");
			$hstr = preg_replace("#Hex-STRING\: #","",$hstr);
			$hstr = preg_replace("#[ \n]#","",$hstr);
			if($trunkNoVlan && $hstr != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE")
				$trunkNoVlan = false;
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				$vlanbyteslen = strlen($vlanbytes);
				// add initial zero to get 4 chars
				for($j=$vlanbyteslen;$j<4;$j++)
					$vlanbytes = "0".$vlanbytes;
				for($j=0;$j<strlen($vlanbytes);$j++) {
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
					$vlid++;
				}
			}

			if($trunkNoVlan == true)
				return array();

			return $vlanlist;
		}

		function setSwitchTrunkEncapWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
					return -1;

			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.3","i",$value);
		}

		function setSwitchportModeWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
                   return -1;

			return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13","i",$value);
		}
		
		function getSwitchportModeWithPID($device, $pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
                  return -1;

			return getFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13");
		}
		
		/*
		* Generic functions
		*/
		
		function setFieldForPortWithPID($device, $pid, $field, $vtype, $value) {
			if($device == "" || $field == "" || $pid == "" || $vtype == "" || !FS::$secMgr->isNumeric($pid))
				return -1;
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			FS::$snmpMgr->set($dip,$field.".".$pid,$vtype,$value);
			return 0;
		}
		
		function getFieldForPortWithPID($device, $pid, $field) {
			if($device == "" || $field == "" || $pid == "" || !FS::$secMgr->isNumeric($pid))
				return -1;
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			return snmpget($dip,$community,$field.".".$pid);
		}
		
		function getPortId($device,$portname) {
			$pid = FS::$dbMgr->GetOneData("fss_port_id_cache","pid","device = '".$device."' AND portname = '".$portname."'");
			if($pid == NULL) $pid = -1;
			return $pid;
		}
		
		/*
		* get Port list from a device. If there is a filter, only port with specified vlan are returned
		*/
		
		function getPortList($device,$vlanFltr = NULL) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			$plist = array();
			for($i=0;$i<count($out);$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				if($vlanFltr == NULL || !FS::$secMgr->isNumeric($vlanFltr) || $vlanFltr < 1 || $vlanFltr > 4096)
					array_push($plist,$pname);
				else {
					$portmode = getSwitchportModeWithPID($device,$pid);
					$portmode = explode(" ",$portmode);
					$portmode = $portmode[1];
					if($portmode == 1) {
						$nvlan = getSwitchTrunkNativeVlanWithPID($device,$pid);
						if(!in_array($pname,$plist) && $vlanFltr == $nvlan)
							array_push($plist,$pname);

						$vllist = getSwitchportTrunkVlansWithPid($device,$pid);
						if(!in_array($pname,$plist) && in_array($vlanFltr,$vllist))
							array_push($plist,$pname);
					}
					else if($portmode == 2) {
						$pvlan = getSwitchAccessVLANWithPID($device,$pid);
						if(!in_array($pname,$plist) && $vlanFltr == $pvlan)
							array_push($plist,$pname);
					}
				}
			}
			return $plist;
		}
		
		function replaceVlan($device,$oldvlan,$newvlan) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
			for($i=0;$i<count($out);$i++) {
				$pdata = explode(" ",$out[$i]);
				$pname = $pdata[3];
				$pid = explode(".",$pdata[0]);
				if(!FS::$secMgr->isNumeric($pid[1]))
					continue;
				$pid = $pid[1];
				$portmode = getSwitchportModeWithPID($device,$pid);
				$portmode = explode(" ",$portmode);
				$portmode = $portmode[1];
				if($portmode == 1) {
					$nvlan = getSwitchTrunkNativeVlanWithPID($device,$pid);
					if($oldvlan == $nvlan)
						setSwitchTrunkNativeVlanWithPID($device,$pid,$newvlan);
						
					$vllist = getSwitchportTrunkVlansWithPid($device,$pid);
					if(in_array($oldvlan,$vllist)) {
						$vllist2 = array();
						for($j=0;$j<count($vllist);$j++) {
							if($vllist[$j] != $oldvlan)
								array_push($vllist2,$vllist[$j]);
						}
						array_push($vllist2,$newvlan);
						setSwitchTrunkVlanWithPID($device,$pid,$vllist2);
					}
				}
				else if($portmode == 2) {
					$pvlan = getSwitchAccessVLANWithPID($device,$pid);
					if($oldvlan == $pvlan)
						setSwitchAccessVLANWithPID($device,$pid,$newvlan);
				}
			}
		}
		
		// Saving running-config => startup-config
		function writeMemory($device) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","4");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}
		
		function restoreStartupConfig($device) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"i","1");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"i","3");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"i","4");
			snmpset($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"i","1");
			snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);
			return $rand;
		}
		
		// Save startup-config to TFTP Server
		function exportConfigToTFTP($device,$server,$path) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
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
		function importConfigFromTFTP($device,$server,$path) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
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
		function exportConfigToAuthServer($device,$server,$type,$path,$user,$pwd) {
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
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
		function importConfigFromAuthServer($device,$server,$type,$path,$user,$pwd) {
			if($type != 2 && $type != 4 && $type != 5)
				return -1;
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmprw","device = '".$device."'");
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
		function getCopyState($device,$copyId) {
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			$res = snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$copyId);
			$res = preg_split("# #",$res);
			return $res[1];
		}
		
		function getCopyError($device,$copyId) {
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			$res = snmpget($dip,$community,"1.3.6.1.4.1.9.9.96.1.1.1.1.13.".$copyId);
			$res = preg_split("# #",$res);
			return $res[1];
		}
		
		/*
		* helpers
		*/
		
		function setPortState($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setPortStateWithPID($device,$pid,"1.3.6.1.2.1.2.2.1.7","i",$value);
		}
		
		function setPortDesc($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setPortDescWithPID($device,$pid,$value);
		}
		
		function getPortDesc($device,$portname) {
			return $this->getFieldForPort($device, $portname, "ifAlias");
		}
		
		function setSwitchportMode($device, $portname, $value) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

            return setSwitchportModeWithPID($device,$pid,$value);
		}
		
		function getSwitchportMode($device, $portname, $value) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

            return getSwitchportModeWithPID($device,$pid,$value);
		}
		
		function setSwitchNoTrunkVlan($device,$portname) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setSwitchNoTrunkVlanWithPID($device,$pid);
		}
		
		function setSwitchTrunkNativeVlan($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;
			
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

            return setSwitchTrunkNativeVlanWithPID($device,$pid,$value);
		}
		
		function setSwitchTrunkVlan($device,$portname,$values) {
			if(!preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;

			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setSwitchTrunkVlanWithPID($device,$pid,$values);
		}
		
		function setSwitchAccessVLAN($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setSwitchAccessVLANWithPID($device,$pid,$value);
		}
		
		function getSwitchAccessVLAN($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return getSwitchAccessVLANWithPID($device,$pid);
		}
		
		function getSwitchportTrunkVlans($device,$portname) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;
			return getSwitchportTrunkVlansWithPid($device,$pid);
		}
		
		function setSwitchTrunkEncap($device,$portname,$value) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setSwitchTrunkEncapWithPID($device,$pid,$value);
		}
		
		function setPortDuplex($device,$portname,$value) {
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setPortDuplexWithPID($device,$pid,$value);
		}
		
		function setFieldForPort($device, $portname, $field, $vtype, $value) {
			if($device == "" || $portname == "" || $field == "" || $vtype == "")
				return -1;

			$pid = getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return setFieldForPortWithPID($device,$pid,$field,$vtype,$value);
		}
		
		function getFieldForPort($device, $portname, $field) {
			if($device == "" || $portname == "" || $field == "")
				return NULL;
			
			$pid = getPortId($device,$portname);
			if($pid == -1)
				return NULL;

			return getFieldForPortWithPid($device,$pid,$field);
		}

/*
		@ TODO
		ATTENTION le port ID n'est pas celui de getPortId
		public function setPortSpeed($device, $portname, $value) {
			if(!FS::$secMgr->isNumeric($value))
                                return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

            return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.9.SWITCHID","i",$value);
		}

		public function setPortSpeedWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value))
                                return -1;

            return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10.SWITCHID","i",$value);
		}
		
		public function setPortDuplex($device, $portname, $value) {
			if(!FS::$secMgr->isNumeric($value) || $value == 3 || $value < 1 || $value > 4)
                                return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

            return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
		}

		public function setPortDuplexWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value == 3 || $value < 1 || $value > 4)
                                return -1;

            return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
		}*/
?>
