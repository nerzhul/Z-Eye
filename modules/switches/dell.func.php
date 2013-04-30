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
		* Interface
		*/

		public function showStateOpts() {
                        $state = $this->getPortState();
                        return FS::$iMgr->idxLine($this->loc->s("Shutdown"),"shut",$state == 2 ? true : false, array("type" => "chk", "tooltip" => "tooltip-shut"));
                }

                public function handleState($logvals) {
                        $port = FS::$secMgr->checkAndSecurisePostData("port");
                        $shut = FS::$secMgr->checkAndSecurisePostData("shut");

                        $portstate = $this->getPortState();

                        // If it's same state do nothing
                        if($portstate == ($shut == "on" ? 2 : 1))
                                return;

                        $logvals["hostmode"]["src"] = $portstate;
                        if($this->setPortState($shut == "on" ? 2 : 1) != 0) {
                                echo "Fail to set switchport shut/no shut state";
                                return;
                        }
                        $logvals["hostmode"]["dst"] = ($shut == "on" ? 2 : 1);
                        FS::$dbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$this->devip."' AND port = '".$port."'");
                }

		/*
		* Generic port management
		*/

		public function setPortDesc($value) {
			if(!FS::$secMgr->isNumeric($this->portid) || $this->portid == -1)
				return -1;

			return $this->setFieldForPortWithPID("ifAlias","s",$value);
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
                        $dup = $this->getFieldForPortWithPID("ifMtu");
                        $dup = explode(" ",$dup);
                        if(count($dup) != 2)
                        	return -1;

                        $dup = $dup[1];
			return $dup;
                }

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($field, $vtype, $value) {
			if($this->devip == "" || $this->snmprw == "" || $field == "" || $this->portid < 1 || $vtype == "" || !FS::$secMgr->isNumeric($this->portid))
				return -1;
			snmpset($this->devip,$this->snmprw,$field.".".$this->portid,$vtype,$value);
			return 0;
		}

		public function getFieldForPortWithPID($field, $raw = false) {
			if($this->devip == "" || $this->snmpro == "" || $field == "" || $this->portid < 1)
				return -1;
			$out = snmpget($this->devip,$this->snmpro,$field.".".$this->portid);
                        return $out;
		}

		public function getPortId($portname) {
			$pid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_id_cache","pid","device = '".$this->device."' AND portname = '".$portname."'");
			if($pid == NULL) $pid = -1;
			return $pid;
		}

		public function getPortIndexes() {
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
			exec("snmpwalk -v 2c -c ".$this->snmpro." ".$this->devip." ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null",$out);
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
	}
?>
