#! python
# -*- coding: utf-8 -*-

"""
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
"""

from pyPgSQL import PgSQL
import datetime, sys, thread, subprocess, string, time, commands, threading, logging
from threading import Lock

import netdiscoCfg
import ZEyeUtil

class ZEyeSwitchesPortIDCacher(ZEyeUtil.Thread):
	SNMPcc = None

	def __init__(self,SNMPcc):
		""" 1 hour between two refresh """
		self.sleepingTimer = 60*60
		self.SNMPcc = SNMPcc

		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.logger.info("Switches Port ID caching launched")
		while True:
			self.launchCachingProcess()
			time.sleep(self.sleepingTimer)

	def fetchSNMPInfos(self,ip,devname,devcom,vendor):
		self.incrThreadNb()

		try:
			text = ""
			if vendor == "cisco":
				text = subprocess.check_output(["/usr/local/bin/snmpwalk","-v","2c","-c","%s" % devcom,"%s" % ip,"ifDescr"])
			elif vendor == "dell":
				text = subprocess.check_output(["/usr/local/bin/snmpwalk","-v","2c","-c","%s" % devcom,"%s" % ip,"ifName"])
			
			pgsqlCon2 = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor2 = pgsqlCon2.cursor()
			stopSwIDSearch = 0
			pgcursor2.execute("DELETE FROM z_eye_port_id_cache WHERE device = '%s'" % devname)
			for line in string.split(text, "\n"):
				pdata = string.split(line, " ")
				if len(pdata) >= 4:
					""" get full name, with spaces """
					pname = ""
					for i in xrange(3,len(pdata)):
						pname += pdata[i]
						if i != len(pdata)-1:
							pname += " "
					""" get port id """
					pdata2 = string.split(pdata[0], ".")
					if len(pdata2) >= 2:
						pid = pdata2[1]
						swid = 0
						swpid = 0
						""" it's a cisco specific mib. We must found another mean for other constructors """
						if stopSwIDSearch == 0 and vendor == "cisco":
							text2 = subprocess.check_output(["/usr/local/bin/snmpwalk","-v","2c","-c","%s" % devcom,"%s" % ip, "1.3.6.1.4.1.9.5.1.4.1.1.11","|","grep","%s" % pid])
							piddata = string.split(text2, " ")
							if len(piddata) == 4:
								piddata = string.split(piddata[0], ".")
								if len(piddata) > 1:
									swid = piddata[len(piddata)-2]
									swpid = piddata[len(piddata)-1]
							elif len(piddata) >= 3 and piddata[2] == "No":
								stopSwIDSearch = 1
						""" must be there for no switch/switchport id """
						pgcursor2.execute("INSERT INTO z_eye_port_id_cache (device,portname,pid,switchid,switchportid) VALUES ('%s','%s','%s','%s','%s')" % (devname,pname,pid,swid,swpid))
			pgsqlCon2.commit()
			pgcursor2.close()
			pgsqlCon2.close()
		except Exception, e:
			self.logger.critical("Port ID Caching: %s" % e)

		self.decrThreadNb()

	def launchCachingProcess(self):
		while self.SNMPcc.isRunning == True:
			self.logger.debug("Port-ID-Caching: SNMP community caching is running, waiting 10 seconds")
			time.sleep(10)

		starttime = datetime.datetime.now()
		self.logger.info("Port ID caching started")
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost, user=netdiscoCfg.pgUser, password=netdiscoCfg.pgPwd, database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT ip,name,vendor FROM device ORDER BY ip")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					while self.getThreadNb() >= self.max_threads:
						time.sleep(1)

					devip = idx[0]
					devname = idx[1]
					vendor = idx[2]

					devcom = self.SNMPcc.getReadCommunity(devname)
					if devcom == None:
						self.logger.error("Port-ID-Caching: No read community found for %s" % devname)
					else:
						thread.start_new_thread(self.fetchSNMPInfos,(devip,devname,devcom,vendor))
				""" Wait 1 second to lock program, else if script is too fast,it exists without discovering"""
				time.sleep(1)
			except StandardError, e:
				self.logger.critical("Port-ID-Caching: %s" % e)
				
		except PgSQL.Error, e:
			self.logger.critical("Port-ID-Caching: Pgsql Error %s" % e)
			return
		finally:
			if pgsqlCon:
				pgsqlCon.close()

			# We must wait 1 sec, else threadCounter == 0 because of fast algo
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logger.debug("Port-ID-Caching: waiting %d threads" % self.getThreadNb())
				time.sleep(1)

			totaltime = datetime.datetime.now() - starttime 
			self.logger.info("Port ID caching done (time: %s)" % totaltime)

