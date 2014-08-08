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

from pyPgSQL import PgSQL

from Common import zConfig
from Common.SNMP.Communicator import SNMPCommunicator

class GenericSwitch:
	vendor = ""
	portId = -1
	device = ""
	deviceIP = ""
	snmp_ro = ""
	snmp_rw = ""
	snmpCommunicator = None
	mibs = None
	
	# TEMP: db related
	dbConn = None
	cursor = None
	
	def __init__(self):
		self.vendor = ""
		self.portId = -1
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""
		self.snmpCommunicator = None
		self.mibs = None
		
		# TEMP: db connection
		self.dbConn = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
		self.cursor = self.dbConn.cursor()

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

	#
	# Link Management
	#
	
	def setPortState(self, value):
		return None

	def getPortState(self):
		return None

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
	# Authentication
	#
		
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
	# Others
	#
		
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
		if pid.isdigit() == False:
			return False
			
		self.portId = pid
		return True

	def setDevice(self, dev):
		# We get the device IP
		self.cursor.execute("SELECT ip FROM device WHERE name = '%s'" % dev)
		pgres = self.cursor.fetchone()
		
		if pgres == None:
			return False
			
		self.device = dev
		self.deviceIP = pgres[0]
		
		# And next, the SNMP communities
		self.cursor.execute("SELECT snmpro, snmprw FROM z_eye_snmp_cache WHERE device = '%s'" % self.device)
		pgres = self.cursor.fetchone()
		
		if pgres != None:
			self.snmp_ro = pgres[0]
			self.snmp_rw = pgres[1]
		else:
			self.snmp_ro = "public"
			self.snmp_rw = "private"

		self.snmpCommunicator = SNMPCommunicator(self.deviceIP)
		
		return True

	def unsetPortId(self):
		self.portId = -1
		
	def unsetDevice(self):
		self.device = ""
		self.deviceIP = ""
		self.snmp_ro = ""
		self.snmp_rw = ""
	
	def snmpget(self, mib):
		if self.snmpCommunicator == None:
			return -1
		
		# Mib[0] is the SNMP path
		# If there is a port ID, add it to MIB
		if self.portId != -1:
			return self.snmpCommunicator.snmpget(self.snmp_ro, "%s.%s" % (mib[0], self.portId))
		else:
			return self.snmpCommunicator.snmpget(self.snmp_ro, mib[0])
	
	def snmpset(self, mib):
		if self.snmpCommunicator == None:
			return -1
		
		# Mib[0] is the SNMP path
		# If there is a port ID, add it to MIB
		if self.portId != -1:
			return self.snmpCommunicator.snmpget(self.snmp_rw, "%s.%s" % (mib[0], self.portId), mib[1])
		else:
			return self.snmpCommunicator.snmpget(self.snmp_rw, mib[0], mib[1])
