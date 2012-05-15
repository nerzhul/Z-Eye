<?php
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
		
		function setSwitchTrunkNativeVlanWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;

            return setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}
		
		function setSwitchTrunkVlanWithPID($device,$pid,$values) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;

			$res = preg_split("/,/",$values);

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
			$vlid = 1;
			$hstr = getFieldForPort($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4.".$pid);
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				for($j=0;$j<strlen($vlanbytes);$j++) {
					$vlid++;
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
				}
			}
			$hstr = getFieldForPort($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17.".$pid);
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				for($j=0;$j<strlen($vlanbytes);$j++) {
					$vlid++;
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
				}
			}
			$hstr = getFieldForPort($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18.".$pid);
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				for($j=0;$j<strlen($vlanbytes);$j++) {
					$vlid++;
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
				}
			}
			$hstr = getFieldForPort($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19.".$pid);
			for($i=0;$i<strlen($hstr);$i++) {
				$vlanbytes = base_convert($hstr[$i],16,2);
				for($j=0;$j<strlen($vlanbytes);$j++) {
					$vlid++;
					if($vlanbytes[$j] == "1")
						array_push($vlanlist,$vlid);
				}
			}
			
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
			return FS::$snmpMgr->get($dip,$field.".".$pid);
		}
		
		function getPortId($device,$portname) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
				
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep ".$portname,$out);
			if(strlen($out[0]) < 5)
				return -1;
			$out = explode(" ",$out[0]);
			$out = explode(".",$out[0]);
			if(!FS::$secMgr->isNumeric($out[1]))
				return -1;
			return $out[1];
		}
		
		function getPortList($device) {
			$out = "";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($dip == NULL)
				return -1;
			$community = FS::$dbMgr->GetOneData("fss_snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr",$out);
			return $out;
		}
		
		// Saving running-config => startup-config
		function writeMemory($device) {
			$rand = rand(1,100);
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"1");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"4");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"3");
			FS::$snmpMgr->setInt($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"1");
			
			FS::$snmpMgr->get($dip,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);

			return 0;
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
