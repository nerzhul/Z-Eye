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
import datetime, re, sys, time, thread, threading, subprocess
from threading import Lock
import pysnmp, logging
import random
from pysnmp.entity.rfc3413.oneliner import cmdgen
from pysnmp.proto import rfc1902

import ZEyeUtil
import netdiscoCfg
from SNMPBroker import ZEyeSNMPBroker

class ZEyeSwitchesBackup(ZEyeUtil.Thread):
	SNMPcc = None

	def __init__(self,SNMPcc):
		""" 24 hours between two backups """
		self.sleepingTimer = 24*60*60
		self.SNMPcc = SNMPcc
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.logger.info("Switch backup process launched")
		while True:
			self.launchRegularBackup()
			time.sleep(self.sleepingTimer)

	def launchRegularBackup(self):
		self.setRunning(True)
		while self.SNMPcc.isRunning():
			self.logger.debug("Switches-backup: SNMP community caching is running, waiting 10 seconds")
			time.sleep(10)

		self.logger.info("Switches backup started")
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
						devip = idx2[0]
						devname = idx2[1]

						# Improve perfs, ask to community cacher
						devcom = self.SNMPcc.getWriteCommunity(devname)

						# If no community found in cache dont try to backup
						if devcom == None:
							self.logger.warn("Switches-backup: No write community found for %s" % devname)
						else:
							# save type = 1 (TFTP)
							if idx[0] == 1:
								thread.start_new_thread(self.doBackup,(devip,devname,devcom,idx[1],"%sconf-%s" % (idx[2], devname)))
							elif idx[0] == 2 or idx[0] == 4 or idx[0] == 5:
								thread.start_new_thread(self.doAuthBackup,(devip,devname,devcom,idx[1],"%sconf-%s" % (idx[2], devname),idx[3],idx[4]))
			except StandardError, e:
				self.logger.critical("Switches-backup: %s" % e)
				return
				
		except PgSQL.Error, e:
			self.logger.critical("Switches-backup: FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			self.logger.debug("Switches backup waiting %d threads" % self.getThreadNb())
			time.sleep(1)

		self.setRunning(False)
		totaltime = datetime.datetime.now() - starttime
		self.logger.info("Switches backup done (time: %s)" % totaltime)

	def doBackup(self,ip,devname,devcom,addr,path):
		self.incrThreadNb()

		try:
			SNMPB = ZEyeSNMPBroker(ip)
			rand = random.randint(1,100)
			if SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.%d" % rand,rfc1902.Integer(1)) < 0:
				self.decrThreadNb()
				return
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.%d" % rand,rfc1902.Integer(3))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.%d" % rand,rfc1902.Integer(1))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.%d" % rand,rfc1902.IpAddress(addr))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.%d" % rand,rfc1902.OctetString(path))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.%d" % rand,rfc1902.Integer(1))

			time.sleep(1)
			copyState = SNMPB.snmpget(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.%d" % rand)
			while copyState == 2:
				time.sleep(1)
				copyState = SNMPB.snmpget(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.%d" % rand)
		except Exception, e:
			self.logger.critical("Switches-backup: FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def doAuthBackup(self,ip,devname,devcom,addr,path,login,pwd):
		self.incrThreadNb()

		try:
			SNMPB = ZEyeSNMPBroker(ip)
			rand = random.randint(1,100)
			if SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.%d" % rand,rfc1902.Integer(1)) != 0:
				self.decrThreadNb()
				return
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.%d" % rand,rfc1902.Integer(3))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.%d" % rand,rfc1902.Integer(1))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.5.%d" % rand,rfc1902.IpAddress(addr))
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.6.%d" % rand,path)
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.7.%d" % rand,login)
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.8.%d" % rand,pwd)
			SNMPB.snmpset(devcom,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.%d" % rand,rfc1902.Integer(1))
		except Exception, e:
			self.logger.critical("Switches-backup: FATAL %s" % e)
		finally:
			self.decrThreadNb()
