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

	class DeviceAPI {
		function DeviceAPI() {
			$this->vendor = "";
			$this->portid = -1;
			$this->device = "";
			$this->devip = "";
		}

		/*
		* Generic port management
		*/

		public function setPortDescWithPID($device,$pid,$value) {
			return NULL;
		}

		public function setPortStateWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getPortState($device) {
			return NULL;
		}

		/*
		* Link Management
		*/

		public function getPortMtu($device) {
			return NULL;
                }

		public function setPortDuplexWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getPortDuplex($device) {
			return NULL;
		}

		public function setPortSpeedWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getPortSpeed($device) {
			return NULL;
		}

		/*
		* VLAN management
		*/

		public function setSwitchAccessVLANWithPID($device,$pid,$value) {
			return NULL;
		}	

		public function getSwitchAccessVLAN($device) {
			return NULL;
                }

		public function setSwitchportMABEnableWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getSwitchportMABState($device) {
			return NULL;
		}

		public function setSwitchMABTypeWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getSwitchportMABType($device) {
			return NULL;
		}

		public function setSwitchportAuthFailVLAN($device,$pid,$value) {
			return NULL;
                }

                public function getSwitchportAuthFailVLAN($device) {
			return NULL;
                }

		public function setSwitchportAuthNoRespVLAN($device,$pid,$value) {
			return NULL;
                }

                public function getSwitchportAuthNoRespVLAN($device) {
			return NULL;
                }

		public function setSwitchportAuthDeadVLAN($device,$pid,$value) {
			return NULL;
                }

                public function getSwitchportAuthDeadVLAN($device) {
			return NULL;
                }

		// authentication port-control 1,2,3
		public function setSwitchportControlMode($device,$pid,$value) {
			return NULL;
                }

                public function getSwitchportControlMode($device) {
			return NULL;
                }

		// authentication host-mode
		public function setSwitchportAuthHostMode($device,$pid,$value) {
			return NULL;
		}

		public function getSwitchportAuthHostMode($device) {
			return NULL;
		}

		public function setSwitchTrunkNativeVlanWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getSwitchTrunkNativeVlan($device) {
			return NULL;
		}

		public function setSwitchTrunkVlanWithPID($device,$pid,$values) {
			return NULL;
		}

		public function setSwitchNoTrunkVlanWithPID($device,$pid) {
			return NULL;
		}

		public function getSwitchportTrunkVlans($device) {
			return NULL;
		}

		public function setSwitchTrunkEncapWithPID($device,$pid,$value) {
			return NULL;
		}

		public function getSwitchTrunkEncap($device) {
			return NULL;
		}

		public function setSwitchportModeWithPID($device, $pid, $value) {
			return NULL;
		}

		public function getSwitchportMode($device) {
			return NULL;
		}

		public function setSwitchportVoiceVlanWithPID($device, $pid, $value) {
			return NULL;
		}

		public function getSwitchportVoiceVlan($device) {
			return NULL;
		}

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($device, $pid, $field, $vtype, $value) {
			return NULL;
		}

		public function getFieldForPortWithPID($device, $field, $raw = false) {
			return NULL;
		}

		public function getPortId($device,$portname) {
			return NULL;
		}

		public function getPortIndexes($device,$pid) {
			return NULL;
		}

		/*
		* get Port list from a device. If there is a filter, only port with specified vlan are returned
		*/

		public function getPortList($vlanFltr = NULL) {
			return NULL;
		}

		public function replaceVlan($oldvlan,$newvlan) {
			return NULL;
		}

		// Saving running-config => startup-config
		public function writeMemory() {
			return NULL;
		}

		public function restoreStartupConfig() {
			return NULL;
		}

		// Save startup-config to TFTP Server
		public function exportConfigToTFTP($device,$server,$path) {
			return NULL;
		}

		// Restore startup-config to TFTP Server
		public function importConfigFromTFTP($device,$server,$path) {
			return NULL;
		}

		// Save startup-config to FTP/SCP/SFTP Server
		public function exportConfigToAuthServer($device,$server,$type,$path,$user,$pwd) {
			return NULL;
		}

		// Restore startup-config to FTP/SCP/SFTP Server
		public function importConfigFromAuthServer($device,$server,$type,$path,$user,$pwd) {
			return NULL;
		}

		// Get Copy state from switch, using previous randomized id
		public function getCopyState($device,$copyId) {
			return NULL;
		}

		public function getCopyError($device,$copyId) {
			return NULL;
		}

		/*
		* helpers
		*/

		public function setPortState($device,$portname,$value) {
			return NULL;
		}

		public function setPortDesc($device,$portname,$value) {
			return NULL;
		}

		public function getPortDesc($device,$portname) {
			return NULL;
		}

		public function setSwitchportMode($device, $portname, $value) {
			return NULL;
		}

		public function setSwitchNoTrunkVlan($device,$portname) {
			return NULL;
		}

		public function setSwitchTrunkNativeVlan($device,$portname,$value) {
			return NULL;
		}

		public function setSwitchTrunkVlan($device,$portname,$values) {
			return NULL;
		}

		public function setSwitchAccessVLAN($device,$portname,$value) {
			return NULL;
		}

		public function getSwitchAccessVLAN($device,$portname,$value) {
			return NULL;
		}

		public function setSwitchTrunkEncap($device,$portname,$value) {
			return NULL;
		}

		/*
		* Port Security
		*/

		public function getPortSecStatus($device) {
			return NULL;
                }

		public function getPortSecEnable($device) {
			return -1;
                }

		public function setPortSecEnableWithPID($device,$pid,$value) {
			return NULL;
                }

		public function getPortSecViolAct($device) {
			return NULL;
                }

		public function setPortSecViolActWithPID($device,$pid,$value) {
			return NULL;
                }

		public function getPortSecMaxMAC($device) {
			return NULL;
                }

		public function setPortSecMaxMACWithPID($device,$pid,$value) {
			return NULL;
                }

		/*
		* special
		*/

		public function getPortCDPEnable($device) {
			return NULL;
                }

		public function setPortCDPEnableWithPID($device,$pid,$value) {
			return NULL;
                }

		public function getPortDHCPSnoopingTrust($device) {
			return NULL;
		}

		public function setPortDHCPSnoopingTrust($device,$pid,$value) {
			return NULL;
		}

		public function getPortDHCPSnoopingRate($device) {
			return NULL;
		}

		public function setPortDHCPSnoopingRate($device,$pid,$value) {
			return NULL;
		}

		public function getDHCPSnoopingStatus($device) {
			return NULL;
		}

		public function setDHCPSnoopingStatus($device,$value) {
			return NULL;
		}

		public function getDHCPSnoopingOpt82($device) {
			return NULL;
		}

		public function setDHCPSnoopingOpt82($device,$value) {
			return NULL;
		}

		public function getDHCPSnoopingMatchMAC($device) {
			return NULL;
		}

		public function setDHCPSnoopingMatchMAC($device) {
			return NULL;
		}

		public function getDHCPSnoopingVlans($dip,$community) {
			return NULL;
		}

		public function setDHCPSnoopingVlans($device,$vlans) {
			return NULL;
		}

		public function setDHCPSnoopingOnVlan($device,$vlan,$value) {
			return NULL;
		}

		public function setFieldForPort($device, $portname, $field, $vtype, $value) {
			return NULL;
		}

		public function getFieldForPort($device, $portname, $field) {
			return NULL;
		}


		public function connectToDevice($device,$sshuser,$sshpwd,$enablepwd) {
			return NULL;
		}

		public function sendSSHCmd($stdio, $cmd) {
			return "";
		}		

		public function showSSHRunCfg($stdio) {
			return "";
		}

		public function setPortId($pid) {
			if(FS::$secMgr->isNumeric($pid))
				$this->portid = $pid;
		}

		public function setDevice($dev) {
			$this->device = $dev;
			$this->devip = FS::$dbMgr->GetOneData("device","ip","name = '".$dev."'");
		}

		public function getDeviceIP() { return $this->devip; }

		public function unsetPortId() { $this->portid = -1; }
		public function unsetDevice() { $this->device = ""; $this->devip = ""; }
		public $vendor;
		protected $portid;
		protected $device;
		protected $devip;
	}
?>
