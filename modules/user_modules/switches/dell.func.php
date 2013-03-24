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

	class DellAPI extends DeviceAPI {
		function DellAPI() { $this->vendor = "dell"; }

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

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($device, $pid, $field, $vtype, $value) {
			if($device == "" || $field == "" || $pid == "" || $vtype == "" || !FS::$secMgr->isNumeric($pid))
				return -1;
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
                        if(!$community) $community = SNMPConfig::$SNMPWriteCommunity;
			snmpset($dip,$community,$field.".".$pid,$vtype,$value);
			return 0;
		}

		public function getFieldForPortWithPID($device, $pid, $field, $raw = false) {
			if($device == "" || $field == "" || $pid == "" /*|| !FS::$secMgr->isNumeric($pid)*/)
				return -1;
			$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
			$community = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			$out = snmpget($dip,$community,$field.".".$pid);
                        return $out;
		}

		public function getPortId($device,$portname) {
			$pid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_id_cache","pid","device = '".$device."' AND portname = '".$portname."'");
			if($pid == NULL) $pid = -1;
			return $pid;
		}

		public function getPortIndexes($device,$pid) {
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."port_id_cache","switchid,switchportid","device = '".$device."' AND pid = '".$pid."'");
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
			$community = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$device."'");
			if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
			exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
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
		* special
		*/

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
	}
?>
