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
			$this->snmpro = "";
			$this->snmprw = "";
		}

		/*
		* Interface & handler functions, herited and modified by each vendor
		*/

		public function showVoiceVlanOpts($voicevlanoutput) { return ""; }
		public function handleVoiceVlan($logvals,$voicevlan) {}

		public function showPortSecurityOpts() { return ""; }
		public function handlePortSecurity($logvals) {}

		public function showDHCPSnoopingOpts() { return ""; }
		public function handleDHCPSnooping($logvals,$dhcpsntrusten,dhcpsnrate) {}

		/*
		* Generic port management
		*/

		public function setPortDesc($value) {
			return NULL;
		}

		public function getPortDesc() {
			return NULL;
		}

		public function setPortState($value) {
			return NULL;
		}

		public function getPortState() {
			return NULL;
		}

		/*
		* Link Management
		*/

		public function getPortMtu() {
			return NULL;
                }

		public function setPortDuplex($value) {
			return NULL;
		}

		public function getPortDuplex() {
			return NULL;
		}

		public function setPortSpeed($value) {
			return NULL;
		}

		public function getPortSpeed() {
			return NULL;
		}

		/*
		* VLAN management
		*/

		public function setSwitchAccessVLAN($value) {
			return NULL;
		}	

		public function getSwitchAccessVLAN() {
			return NULL;
                }

		public function setSwitchportMABEnable($value) {
			return NULL;
		}

		public function getSwitchportMABState() {
			return NULL;
		}

		public function setSwitchMABType($value) {
			return NULL;
		}

		public function getSwitchportMABType() {
			return NULL;
		}

		public function setSwitchportAuthFailVLAN($value) {
			return NULL;
                }

                public function getSwitchportAuthFailVLAN() {
			return NULL;
                }

		public function setSwitchportAuthNoRespVLAN($value) {
			return NULL;
                }

                public function getSwitchportAuthNoRespVLAN() {
			return NULL;
                }

		public function setSwitchportAuthDeadVLAN($value) {
			return NULL;
                }

                public function getSwitchportAuthDeadVLAN() {
			return NULL;
                }

		// authentication port-control 1,2,3
		public function setSwitchportControlMode($value) {
			return NULL;
                }

                public function getSwitchportControlMode() {
			return NULL;
                }

		// authentication host-mode
		public function setSwitchportAuthHostMode($value) {
			return NULL;
		}

		public function getSwitchportAuthHostMode() {
			return NULL;
		}

		public function setSwitchTrunkNativeVlan($value) {
			return NULL;
		}

		public function getSwitchTrunkNativeVlan() {
			return NULL;
		}

		public function setSwitchTrunkVlan($values) {
			return NULL;
		}

		public function setSwitchNoTrunkVlan() {
			return NULL;
		}

		public function getSwitchportTrunkVlans() {
			return NULL;
		}

		public function setSwitchTrunkEncap($value) {
			return NULL;
		}

		public function getSwitchTrunkEncap() {
			return NULL;
		}

		public function setSwitchportMode($value) {
			return NULL;
		}

		public function getSwitchportMode() {
			return NULL;
		}

		public function setSwitchportVoiceVlan($value) {
			return NULL;
		}

		public function getSwitchportVoiceVlan() {
			return NULL;
		}

		/*
		* Generic public functions
		*/

		public function setFieldForPortWithPID($field, $vtype, $value) {
			return NULL;
		}

		public function getFieldForPortWithPID($field, $raw = false) {
			return NULL;
		}

		public function getPortId($portname) {
			return NULL;
		}

		public function getPortIndexes() {
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
		public function exportConfigToTFTP($server,$path) {
			return NULL;
		}

		// Restore startup-config to TFTP Server
		public function importConfigFromTFTP($server,$path) {
			return NULL;
		}

		// Save startup-config to FTP/SCP/SFTP Server
		public function exportConfigToAuthServer($server,$type,$path,$user,$pwd) {
			return NULL;
		}

		// Restore startup-config to FTP/SCP/SFTP Server
		public function importConfigFromAuthServer($server,$type,$path,$user,$pwd) {
			return NULL;
		}

		// Get Copy state from switch, using previous randomized id
		public function getCopyState($copyId) {
			return NULL;
		}

		public function getCopyError($copyId) {
			return NULL;
		}

		/*
		* Port Security
		*/

		public function getPortSecStatus() {
			return NULL;
                }

		public function getPortSecEnable() {
			return -1;
                }

		public function setPortSecEnable($value) {
			return NULL;
                }

		public function getPortSecViolAct() {
			return NULL;
                }

		public function setPortSecViolAct($value) {
			return NULL;
                }

		public function getPortSecMaxMAC() {
			return NULL;
                }

		public function setPortSecMaxMAC($value) {
			return NULL;
                }

		/*
		* special
		*/

		public function getPortCDPEnable() {
			return NULL;
                }

		public function setPortCDPEnable($value) {
			return NULL;
                }

		public function getPortDHCPSnoopingTrust() {
			return NULL;
		}

		public function setPortDHCPSnoopingTrust($value) {
			return NULL;
		}

		public function getPortDHCPSnoopingRate() {
			return NULL;
		}

		public function setPortDHCPSnoopingRate($value) {
			return NULL;
		}

		public function getDHCPSnoopingStatus() {
			return NULL;
		}

		public function setDHCPSnoopingStatus($value) {
			return NULL;
		}

		public function getDHCPSnoopingOpt82() {
			return NULL;
		}

		public function setDHCPSnoopingOpt82($value) {
			return NULL;
		}

		public function getDHCPSnoopingMatchMAC() {
			return NULL;
		}

		public function setDHCPSnoopingMatchMAC() {
			return NULL;
		}

		public function getDHCPSnoopingVlans($dip,$community) {
			return NULL;
		}

		public function setDHCPSnoopingVlans($vlans) {
			return NULL;
		}

		public function setDHCPSnoopingOnVlan($vlan,$value) {
			return NULL;
		}

		public function connectToDevice($server,$sshuser,$sshpwd,$enablepwd) {
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
			$this->snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$this->device."'");
			if(!$this->snmpro) $this->snmpro = SNMPConfig::$SNMPReadCommunity;
			$this->snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$this->device."'");
			if(!$this->snmprw) $this->snmprw = SNMPConfig::$SNMPWriteCommunity;
		}

		public function getDeviceIP() { return $this->devip; }

		public function unsetPortId() { $this->portid = -1; }
		public function unsetDevice() { $this->device = ""; $this->devip = ""; $this->snmpro = ""; $this->snmprw = ""; }
		public $vendor;
		protected $portid;
		protected $device, $devip;
		protected $snmpro, $snmprw;
	}
?>
