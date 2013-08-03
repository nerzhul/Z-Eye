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

import Logger
import netdiscoCfg
from SNMPBroker import ZEyeSNMPBroker

class ZEyeSNMPCommCacher(threading.Thread):
	sleepingTimer = 0
	startTime = 0
	threadCounter = 0
	isRunning = False

	pgcon = None
	pgcursor = None
	tc_mutex = Lock()
	dev_mutex = Lock()

	snmpro = []
	snmprw = []
	deviceCommunities = {}

	def __init__(self):
		""" 1 hour between two discover """
		self.sleepingTimer = 60*60
		threading.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("SNMP communities caching launched")
		while True:
			self.launchSNMPCaching()
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

	def setDevCommunities(self,name,snmpro,snmprw):
		self.dev_mutex.acquire()
		self.deviceCommunities[name] = [snmpro,snmprw]
		self.dev_mutex.release()

	def launchSNMPCaching(self):
		Logger.ZEyeLogger().write("SNMP communities caching started")
		starttime = datetime.datetime.now()
		self.isRunning = True
		try:
			self.pgcon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			self.pgcursor = self.pgcon.cursor()
			self.pgcursor.execute("SELECT ip,name FROM device ORDER BY ip")
			try:
				pgres = self.pgcursor.fetchall()
				if self.pgcursor.rowcount > 0:
					self.loadSNMPCommunities()
					self.loadDevicesCommunities()
					for idx in pgres:
						thread.start_new_thread(self.searchCommunities,(idx[0],idx[1]))
			except StandardError, e:
				Logger.ZEyeLogger().write("SNMP-Communities-Caching: FATAL %s" % e)

			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				Logger.ZEyeLogger().write("SNMP communities caching waiting %d threads" % self.getThreadNb())
				time.sleep(1)

			# All threads have finished, now we can write cache to DB
			self.registerCommunities()
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("SNMP-Communities-Caching: FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if self.pgcon:
				self.pgcon.close()

		self.isRunning = False
		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("SNMP communities caching done (time: %s)" % totaltime)

	def testCommunity(self,SNMPB,comm):
		if SNMPB.snmpget(comm,"1.3.6.1.6.3.1.1.6.1.0") != -1:
			return True
		return False

	def searchCommunities(self,ip,name):
		self.incrThreadNb()
		try:
			foundro = ""
			foundrw = ""
			SNMPB = ZEyeSNMPBroker(ip)	

			# First we test the collected communities
			if self.testCommunity(SNMPB,self.deviceCommunities[name][0]) == True:
				foundro = self.deviceCommunities[name][0]

			if self.testCommunity(SNMPB,self.deviceCommunities[name][1]) == True:
				foundrw = self.deviceCommunities[name][1]

			if foundro == "":
				for comm in self.snmpro:
					if self.testCommunity(SNMPB,comm) == True:
						foundro = comm

			if foundrw == "":
				for comm in self.snmprw:
					if self.testCommunity(SNMPB,comm) == True:
						foundrw = comm

			self.setDevCommunities(name,foundro,foundrw)
					
		except Exception, e:
			Logger.ZEyeLogger().write("SNMP-Communities-Caching: FATAL %s" % e)
		self.decrThreadNb()

	def loadSNMPCommunities(self):
		self.snmpro = []
		self.snmprw = []
		self.pgcursor.execute("SELECT name,ro,rw FROM z_eye_snmp_communities")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			if idx[1] == True:
				self.snmpro.append(idx[1])
			if idx[2] == True:
				self.snmprw.append(idx[2])

	def loadDevicesCommunities(self):
		self.deviceCommunities = {}
		self.pgcursor.execute("SELECT device,snmpro,snmprw FROM z_eye_snmp_cache")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			self.setDevCommunities(idx[0],idx[1],idx[2])
		
	def registerCommunities(self):
		self.pgcursor.execute("DELETE FROM z_eye_snmp_cache")
		for dev in self.deviceCommunities:
			self.pgcursor.execute("INSERT INTO z_eye_snmp_cache(device,snmpro,snmprw) VALUES (%s,%s,%s)",(dev,self.deviceCommunities[dev][0],self.deviceCommunities[dev][1]))
		self.pgcon.commit()

	def getReadCommunity(self,name):
		if name in self.deviceCommunities:
			return self.deviceCommunities[name][0]
		else:
			return None

	def getWriteCommunity(self,name):
		if name in self.deviceCommunities:
			return self.deviceCommunities[name][1]
		else:
			return None
