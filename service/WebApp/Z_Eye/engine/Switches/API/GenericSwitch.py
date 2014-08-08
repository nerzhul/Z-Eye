# -*- coding: utf-8 -*-
"""
* Copyright (C) 2010-2014 Loic BLOT <http://www.unix-experience.fr/>
*
* This program is free software you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
"""

class GenericSwitch:
	vendor = ""
	portId = ""
	device = ""
	deviceIP = ""
	snmp_ro = ""
	snmp_rw = ""
	mibs = None
	
	def __init__(self):
		self.vendor = ""
		self.portId = ""
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""
		self.mibs = None

	#
	# Interface & handler functions, herited and modified by each vendor
	#

	def showStateOpts(self):
		return ""
		
	def handleState(self, logvals, port = "", shut = -1):
		return

	def showSpeedOpts(self):
		return ""
	
	def handleSpeed(self, logvals):
		return

	def showDuplexOpts(self):
		return ""
		
	def handleDuplex(self, logvals):
		return

	def showVlanOpts(self):
		return ""
		
	def handleVlan(self, logvals):
		return

	def showVoiceVlanOpts(self, voicevlanoutput):
		return ""
		
	def handleVoiceVlan(self, logvals):
		return

	def showPortSecurityOpts(self):
		return ""
		
	def handlePortSecurity(self, logvals):
		return

	def showDHCPSnoopingOpts(self):
		return ""
		
	def handleDHCPSnooping(self, logvals):
		return

	def showSaveCfg(self):
		return ""
		
	def handleSaveCfg(self):
		return

	def checkFields(self):
		return True
	
	
	#Generic port management

	def setPortDesc(self, value):
		return None

	def getPortDesc(self):
		return None

	def setPortState(self, value):
		return None

	def getPortState(self):
		return None

	#
	# Link Management
	#

	def getPortMtu(self):
		return None

	def setPortDuplex(self, value):
		return None

	def getPortDuplex(self):
		return None

	def setPortSpeed(self, value):
		return None

	def getPortSpeed(self):
		return None

	#
	# VLAN management
	#

	def setSwitchAccessVLAN(self, value):
		return None

	def getSwitchAccessVLAN(self):
		return None
		
	def setSwitchportMABEnable(self, value):
		return None

	def getSwitchportMABState(self):
		return None

	def setSwitchMABType(self, value):
		return None

	def getSwitchportMABType(self):
		return None

	def setSwitchportAuthFailVLAN(value):
		return None
		
	def getSwitchportAuthFailVLAN(self):
		return None
		
	def setSwitchportAuthNoRespVLAN(self, value):
		return None
		
	def getSwitchportAuthNoRespVLAN(self):
		return None
		
	def setSwitchportAuthDeadVLAN(self, value):
		return None
		
	def getSwitchportAuthDeadVLAN(self):
		return None
		
	# authentication port-control 1,2,3
	def setSwitchportControlMode(self, value):
		return None
		
	def getSwitchportControlMode(self):
		return None
		
	# authentication host-mode
	def setSwitchportAuthHostMode(self, value):
		return None

	def getSwitchportAuthHostMode(self):
		return None

	def setSwitchTrunkNativeVlan(self, value):
		return None

	def getSwitchTrunkNativeVlan(self):
		return None

	def setSwitchTrunkVlan(self, values):
		return None

	def setSwitchNoTrunkVlan(self):
		return None

	def getSwitchportTrunkVlans(self):
		return None

	def setSwitchTrunkEncap(self, value):
		return None

	def getSwitchTrunkEncap(self):
		return None

	def setSwitchportMode(self, value):
		return None

	def getSwitchportMode(self):
		return None

	def setSwitchportVoiceVlan(self, value):
		return None

	def getSwitchportVoiceVlan(self):
		return None

	#
	# Generic defs
	#

	def setFieldForPortWithPID(self, field, vtype, value):
		return None

	def getFieldForPortWithPID(self, field, raw = False):
		return None

	def getPortId(self, portname):
		return None

	def getPortIndexes(self):
		return None

	#
	# get Port list from a device. If there is a filter, only port with specified vlan are returned
	#

	def getPortList(self, vlanFltr = None):
		return None

	def replaceVlan(self, oldvlan, newvlan):
		return None

	# Saving running-config => startup-config
	def writeMemory(self):
		return None

	def restoreStartupConfig(self):
		return None

	# Save startup-config to TFTP Server
	def exportConfigToTFTP(self, server, path):
		return None

	# Restore startup-config to TFTP Server
	def importConfigFromTFTP(self, server, path):
		return None

	# Save startup-config to FTP/SCP/SFTP Server
	def exportConfigToAuthServer(self, server, _type, path, user, pwd):
		return None

	# Restore startup-config to FTP/SCP/SFTP Server
	def importConfigFromAuthServer(self, server, _type, path, user, pwd):
		return None

	# Get Copy state from switch, using previous randomized id
	def getCopyState(self, copyId):
		return None

	def getCopyError(self, copyId):
		return None

	#
	# Port Security
	#

	def getPortSecStatus(self):
		return None
		
	def getPortSecEnable(self):
		return -1
		
	def setPortSecEnable(self, value):
		return None
		
	def getPortSecViolAct(self):
		return None
		
	def setPortSecViolAct(self, value):
		return None
		
	def getPortSecMaxMAC(self):
		return None
		
	def setPortSecMaxMAC(self, value):
		return None
		
	#
	# special
	#

	def getPortCDPEnable(self):
		return None
		
	def setPortCDPEnable(self, value):
		return None
		
	def getPortDHCPSnoopingTrust(self):
		return None

	def setPortDHCPSnoopingTrust(self, value):
		return None

	def getPortDHCPSnoopingRate(self):
		return None

	def setPortDHCPSnoopingRate(self, value):
		return None

	def getDHCPSnoopingStatus(self):
		return None

	def setDHCPSnoopingStatus(self, value):
		return None

	def getDHCPSnoopingOpt82(self):
		return None

	def setDHCPSnoopingOpt82(self, value):
		return None

	def getDHCPSnoopingMatchMAC(self):
		return None

	def setDHCPSnoopingMatchMAC(self, value):
		return None

	def getDHCPSnoopingVlans(self):
		return None

	def setDHCPSnoopingVlans(self, vlans):
		return None

	def setDHCPSnoopingOnVlan(self, vlan,value):
		return None

	def connectToDevice(self, server,sshuser,sshpwd,enablepwd):
		return None

	def sendSSHCmd(self, stdio, cmd):
		return ""

	def showSSHRunCfg(self):
		return ""
		
	def showSSHStartCfg(self):
		return ""
		
	def showSSHInterfaceCfg(self, iface):
		return ""
		
	def showSSHInterfaceStatus(self, iface):
		return ""

	def setPortId(self, pid):
		# @TODO if FS::secMgr->isNumeric(pid):
		self.portId = pid
		

	def setDevice(self, dev):
		self.device = dev
		# self.deviceIP = FS::dbMgr->GetOneData("device","ip","name = '".dev."'")
		# @TODO self.snmp_ro = FS::dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".this->device."'")
		
		if len(self.snmp_ro) == 0:
			self.snmp_ro = "public"
		
		# @ TODO self.snmp_rw = FS::dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".this->device."'")
		if len(self.snmp_rw) == 0:
			self.snmp_rw = "private"

	def getDeviceIP(self):
		return self.deviceIP

	def unsetPortId(self):
		self.portId = -1
		
	def unsetDevice(self):
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""
