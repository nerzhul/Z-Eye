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
	
	def __init__(self):
		self.vendor = ""
		self.portId = ""
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""

	#
	# Interface & handler functions, herited and modified by each vendor
	#

	def showStateOpts():
		return ""
		
	def handleState(logvals, port = "", shut = -1):
		return

	def showSpeedOpts():
		return ""
	
	def handleSpeed(logvals):
		return

	def showDuplexOpts():
		return ""
		
	def handleDuplex(logvals):
		return

	def showVlanOpts():
		return ""
		
	def handleVlan(logvals):
		return

	def showVoiceVlanOpts(voicevlanoutput):
		return ""
		
	def handleVoiceVlan(logvals):
		return

	def showPortSecurityOpts():
		return ""
		
	def handlePortSecurity(logvals):
		return

	def showCDPOpts():
		return ""
		
	def handleCDP(logvals):
		return

	def showDHCPSnoopingOpts():
		return ""
		
	def handleDHCPSnooping(logvals):
		return

	def showSaveCfg():
		return ""
		
	def handleSaveCfg():
		return

	def checkFields():
		return True
	
	
	#Generic port management

	def setPortDesc(value):
		return None

	def getPortDesc():
		return None

	def setPortState(value):
		return None

	def getPortState():
		return None

	#
	# Link Management
	#

	def getPortMtu():
		return None

	def setPortDuplex(value):
		return None

	def getPortDuplex():
		return None

	def setPortSpeed(value):
		return None

	def getPortSpeed():
		return None

	#
	# VLAN management
	#

	def setSwitchAccessVLAN(value):
		return None

	def getSwitchAccessVLAN():
		return None
		
	def setSwitchportMABEnable(value):
		return None

	def getSwitchportMABState():
		return None

	def setSwitchMABType(value):
		return None

	def getSwitchportMABType():
		return None

	def setSwitchportAuthFailVLAN(value):
		return None
		
	def getSwitchportAuthFailVLAN():
		return None
		
	def setSwitchportAuthNoRespVLAN(value):
		return None
		
	def getSwitchportAuthNoRespVLAN():
		return None
		
	def setSwitchportAuthDeadVLAN(value):
		return None
		
	def getSwitchportAuthDeadVLAN():
		return None
		
	# authentication port-control 1,2,3
	def setSwitchportControlMode(value):
		return None
		
	def getSwitchportControlMode():
		return None
		
	# authentication host-mode
	def setSwitchportAuthHostMode(value):
		return None

	def getSwitchportAuthHostMode():
		return None

	def setSwitchTrunkNativeVlan(value):
		return None

	def getSwitchTrunkNativeVlan():
		return None

	def setSwitchTrunkVlan(values):
		return None

	def setSwitchNoTrunkVlan():
		return None

	def getSwitchportTrunkVlans():
		return None

	def setSwitchTrunkEncap(value):
		return None

	def getSwitchTrunkEncap():
		return None

	def setSwitchportMode(value):
		return None

	def getSwitchportMode():
		return None

	def setSwitchportVoiceVlan(value):
		return None

	def getSwitchportVoiceVlan():
		return None

	#
	# Generic defs
	#

	def setFieldForPortWithPID(field, vtype, value):
		return None

	def getFieldForPortWithPID(field, raw = False):
		return None

	def getPortId(portname):
		return None

	def getPortIndexes():
		return None

	#
	# get Port list from a device. If there is a filter, only port with specified vlan are returned
	#

	def getPortList(vlanFltr = None):
		return None

	def replaceVlan(oldvlan, newvlan):
		return None

	# Saving running-config => startup-config
	def writeMemory():
		return None

	def restoreStartupConfig():
		return None

	# Save startup-config to TFTP Server
	def exportConfigToTFTP(server,path):
		return None

	# Restore startup-config to TFTP Server
	def importConfigFromTFTP(server,path):
		return None

	# Save startup-config to FTP/SCP/SFTP Server
	def exportConfigToAuthServer(server,type,path,user,pwd):
		return None

	# Restore startup-config to FTP/SCP/SFTP Server
	def importConfigFromAuthServer(server,type,path,user,pwd):
		return None

	# Get Copy state from switch, using previous randomized id
	def getCopyState(copyId):
		return None

	def getCopyError(copyId):
		return None

	#
	# Port Security
	#

	def getPortSecStatus():
		return None
		
	def getPortSecEnable():
		return -1
		
	def setPortSecEnable(value):
		return None
		
	def getPortSecViolAct():
		return None
		
	def setPortSecViolAct(value):
		return None
		
	def getPortSecMaxMAC():
		return None
		
	def setPortSecMaxMAC(value):
		return None
		
	#
	# special
	#

	def getPortCDPEnable():
		return None
		
	def setPortCDPEnable(value):
		return None
		
	def getPortDHCPSnoopingTrust():
		return None

	def setPortDHCPSnoopingTrust(value):
		return None

	def getPortDHCPSnoopingRate():
		return None

	def setPortDHCPSnoopingRate(value):
		return None

	def getDHCPSnoopingStatus():
		return None

	def setDHCPSnoopingStatus(value):
		return None

	def getDHCPSnoopingOpt82():
		return None

	def setDHCPSnoopingOpt82(value):
		return None

	def getDHCPSnoopingMatchMAC():
		return None

	def setDHCPSnoopingMatchMAC(value):
		return None

	def getDHCPSnoopingVlans():
		return None

	def setDHCPSnoopingVlans(vlans):
		return None

	def setDHCPSnoopingOnVlan(vlan,value):
		return None

	def connectToDevice(server,sshuser,sshpwd,enablepwd):
		return None

	def sendSSHCmd(stdio, cmd):
		return ""

	def showSSHRunCfg():
		return ""
		
	def showSSHStartCfg():
		return ""
		
	def showSSHInterfaceCfg(iface):
		return ""
		
	def showSSHInterfaceStatus(iface):
		return ""

	def setPortId(pid):
		if FS::secMgr->isNumeric(pid):
			this->portid = pid

	def setDevice(dev):
		self.device = dev
		self.deviceIP = FS::dbMgr->GetOneData("device","ip","name = '".dev."'")
		# @TODO self.snmp_ro = FS::dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".this->device."'")
		
		if len(self.snmp_ro) == 0:
			self.snmp_ro = "public"
		
		# @ TODO self.snmp_rw = FS::dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".this->device."'")
		if len(self.snmp_rw) == 0:
			self.snmp_rw = "private"

	def getDeviceIP():
		return self.deviceIP

	def unsetPortId():
		self.portId = -1
		
	def unsetDevice():
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""
