# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

from pyPgSQL import PgSQL
import datetime,re,sys,time,thread,threading,subprocess
from threading import Lock
import pysnmp
import random
from pysnmp.entity.rfc3413.oneliner import cmdgen
from pysnmp.proto import rfc1902

import Logger
import netdiscoCfg

class ZEyeSwitchesBackup(threading.Thread):
	sleepingTimer = 0
	defaultSNMPRW = "private"
	startTime = 0
	threadCounter = 0
	tc_mutex = Lock()

	def __init__(self):
		""" 24 hours between two backups """
		self.sleepingTimer = 24*60*60
		threading.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("Z-Eye Switch backup process launched")
		while True:
			self.launchBackup()
			time.sleep(self.sleepingTimer)

	def incrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter = self.threadCounter + 1
		self.tc_mutex.release()

	def decrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter = self.threadCounter - 1
		self.tc_mutex.release()

	def getThreadNb(self):
		val = 0
		self.tc_mutex.acquire()
		val = self.threadCounter
		self.tc_mutex.release()
		return val

	def launchBackup(self):
		Logger.ZEyeLogger().write("Switches backup started")
		starttime = datetime.datetime.now()
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT type,addr,path,login,pwd FROM z_eye_save_device_servers")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					pgcursor2 = pgsqlCon.cursor()
					pgcursor2.execute("SELECT ip,name FROM device ORDER BY ip")
					pgres2 = pgcursor2.fetchall()
					for idx2 in pgres2:
						pgcursor3 = pgsqlCon.cursor()
						pgcursor3.execute("SELECT snmprw FROM z_eye_snmp_cache where device = '%s'" % idx2[1])
						pgres3 = pgcursor3.fetchone()
				
						devip = idx2[0]
						devname = idx2[1]
						if pgres3:
							devcom = pgres3[0]
						else:
							devcom = self.defaultSNMPRW
						# save type = 1 (TFTP)
						if idx[0] == 1:
							thread.start_new_thread(self.doBackup,(devip,devname,devcom,idx[1],"%sconf-%s" % (idx[2], devname)))
						elif idx[0] == 2 or idx[0] == 4 or idx[0] == 5:
							thread.start_new_thread(self.doAuthBackup,(devip,devname,devcom,idx[1],"%sconf-%s" % (idx[2], devname),idx[3],idx[4]))
			except StandardError, e:
				Logger.ZEyeLogger().write("Switches-backup: FATAL %s" % e)
				return
				
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("Switches-backup: FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			Logger.ZEyeLogger().write("Switches backup waiting %d threads" % self.getThreadNb())
			time.sleep(1)

		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("Switches backup done (time: %s)" % totaltime)

	def snmpset(self,devname,ip,devcom,mib,value):
		cmdGen = cmdgen.CommandGenerator()
		errorIndication, errorStatus, errorIndex, varBinds = cmdGen.setCmd(
			cmdgen.CommunityData(devcom),
			cmdgen.UdpTransportTarget((ip, 161)),
			(mib, value)
		)
		if errorIndication:
			Logger.ZEyeLogger().write("Switches-backup: errorIndication on %s: %s" % (devname,errorIndication))
			return -1
		elif errorStatus:
			if errorStatus.prettyPrint() == "'noAccess'":
				Logger.ZEyeLogger().write("Switches-backup: community %s cannot write on %s@%s" % (devcom,mib,devname))
			else:
				Logger.ZEyeLogger().write("Switches-backup: errorStatus on %s: %s at %s" % (devname,errorStatus.prettyPrint(), errorIndex and varBinds[int(errorIndex)-1] or '?'))
			return -1
		return 0

	def snmpget(self,devname,ip,devcom,mib):
		cmdGen = cmdgen.CommandGenerator()
		errorIndication, errorStatus, errorIndex, varBinds = cmdGen.getCmd(
			cmdgen.CommunityData(devcom),
			cmdgen.UdpTransportTarget((ip, 161)),
			mib
		)
		if errorIndication:
			Logger.ZEyeLogger().write("Switches-backup: errorIndication on %s: %s" % (devname,errorIndication))
			return -1
		elif errorStatus:
			if errorStatus.prettyPrint() == "'noAccess'":
				Logger.ZEyeLogger().write("Switches-backup: community %s cannot read on %s@%s" % (devcom,mib,devname))
			else:
				Logger.ZEyeLogger().write("Switches-backup: errorStatus on %s: %s at %s" % (devname, errorStatus.prettyPrint(), errorIndex and varBinds[int(errorIndex)-1] or '?'))
			return -1
		else:
			for name, val in varBinds:
				return val.prettyPrint()
		return 0

	def doBackup(self,ip,devname,devcom,addr,path):
		self.incrThreadNb()

		try:
			rand = random.randint(1,100)
			if self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.%d" % rand,rfc1902.Integer(1)) < 0:
				self.decrThreadNb()
				return
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.%d" % rand,rfc1902.Integer(3))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.%d" % rand,rfc1902.Integer(1))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.%d" % rand,rfc1902.IpAddress(addr))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.%d" % rand,rfc1902.OctetString(path))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.%d" % rand,rfc1902.Integer(1))

			time.sleep(1)
			copyState = self.snmpget(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.%d" % rand)
			while copyState == 2:
				time.sleep(1)
				copyState = self.snmpget(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.%d" % rand)
		except Exception, e:
			Logger.ZEyeLogger().write("Switches-backup: FATAL %s" % e)

		self.decrThreadNb()

	def doAuthBackup(self,ip,devname,devcom,addr,path,login,pwd):
		self.incrThreadNb()

		try:
			rand = random.randint(1,100)
			if self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.%d" % rand,rfc1902.Integer(1)) != 0:
				self.decrThreadNb()
				return
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.%d" % rand,rfc1902.Integer(3))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.%d" % rand,rfc1902.Integer(1))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.%d" % rand,rfc1902.IpAddress(addr))
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.%d" % rand,path)
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.%d" % rand,login)
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.%d" % rand,pwd)
			self.snmpset(devname,ip,devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.%d" % rand,rfc1902.Integer(1))
		except Exception, e:
			Logger.ZEyeLogger().write("Switches-backup: FATAL %s" % e)

		self.decrThreadNb()
