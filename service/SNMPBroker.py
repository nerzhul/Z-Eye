#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2011-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
"""

import pysnmp
from pysnmp.entity.rfc3413.oneliner import cmdgen
from pysnmp.proto import rfc1902

import Logger

class ZEyeSNMPBroker():
	ip = ""

	def __init__(self,ip):
		self.ip = ip

	def snmpget(self,devcom,mib):
		if self.ip == "":
			Logger.ZEyeLogger().write("SNMPBroker: invalid IP '%s'" % (self.ip))
			return 0

		cmdGen = cmdgen.CommandGenerator()
		errorIndication, errorStatus, errorIndex, varBinds = cmdGen.getCmd(
			cmdgen.CommunityData(devcom),
			cmdgen.UdpTransportTarget((self.ip, 161)),
			mib
		)
		if errorIndication:
			Logger.ZEyeLogger().write("SNMPBroker: errorIndication on %s: %s" % (self.ip,errorIndication))
			return -1
		elif errorStatus:
			if errorStatus.prettyPrint() == "'noAccess'":
				Logger.ZEyeLogger().write("SNMPBroker: community %s cannot read on %s@%s" % (devcom,mib,self.ip))
			else:
				Logger.ZEyeLogger().write("SNMPBroker: errorStatus on %s: %s at %s" % (self.ip, errorStatus.prettyPrint(), errorIndex and varBinds[int(errorIndex)-1] or '?'))
			return -1
		else:
			for name, val in varBinds:
				return val.prettyPrint()
		return 0

	def snmpset(self,devcom,mib,value):
		if self.ip == "":
			Logger.ZEyeLogger().write("SNMPBroker: invalid IP '%s'" % (self.ip))
			return 0

		cmdGen = cmdgen.CommandGenerator()
		errorIndication, errorStatus, errorIndex, varBinds = cmdGen.setCmd(
			cmdgen.CommunityData(devcom),
			cmdgen.UdpTransportTarget((self.ip, 161)),
			(mib, value)
		)
		if errorIndication:
			Logger.ZEyeLogger().write("SNMPBroker: errorIndication on %s: %s" % (self.ip,errorIndication))
			return -1
		elif errorStatus:
			if errorStatus.prettyPrint() == "'noAccess'":
				Logger.ZEyeLogger().write("SNMPBroker: community %s cannot write on %s@%s" % (devcom,mib,self.ip))
			else:
				Logger.ZEyeLogger().write("SNMPBroker: errorStatus on %s: %s at %s" % (self.ip,errorStatus.prettyPrint(), errorIndex and varBinds[int(errorIndex)-1] or '?'))
			return -1
		return 0