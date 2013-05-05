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
import datetime, sys, thread, os, string, time, commands, threading
from threading import Lock

import Logger
import netdiscoCfg

class ZEyeSwitchesPortIDCacher(threading.Thread):
	tc_mutex = Lock()
	threadCounter = 0
	defaultSNMPRO = "public"
	max_threads = 30

	def __init__(self):
                """ 1 hour between two refresh """
                self.sleepingTimer = 60*60
                threading.Thread.__init__(self)


	def run(self):
		Logger.ZEyeLogger().write("Switches Port ID caching launched")
		while True:
			self.launchCachingProcess()
			time.sleep(self.sleepingTimer)

	def incrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter += 1
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

	def fetchSNMPInfos(self,ip,devname,devcom,vendor):
		self.incrThreadNb()

		try:
			if vendor == "cisco":
				cmd = "snmpwalk -v 2c -c %s %s ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null | grep -ve unrouted" % (devcom,ip)
			elif vendor == "dell":
				cmd = "snmpwalk -v 2c -c %s %s ifName | grep -ve Stack | grep -ve Vlan | grep -ve Null | grep -ve unrouted" % (devcom,ip)
			pipe = os.popen('{ ' + cmd + '; }', 'r')
			text = pipe.read()
			pipe.close()
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
							cmd = "snmpwalk -v 2c -c %s %s 1.3.6.1.4.1.9.5.1.4.1.1.11 | grep %s" % (devcom,ip,pid)
							pipe2 = os.popen('{ ' + cmd + '; }', 'r')
							text2 = pipe2.read()
							pipe2.close()
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
			Logger.ZEyeLogger().write("Port ID Caching: FATAL %s" % e)

		self.decrThreadNb()

	def launchCachingProcess(self):
		starttime = datetime.datetime.now()
		Logger.ZEyeLogger().write("Port ID caching started")
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT ip,name,vendor FROM device ORDER BY ip")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					while self.getThreadNb() >= self.max_threads:
						time.sleep(1)

					pgcursor.execute("SELECT snmpro FROM z_eye_snmp_cache where device = '%s'" % idx[1])
					pgres2 = pgcursor.fetchone()
			
					devip = idx[0]
					devname = idx[1]
					vendor = idx[2]
					if pgres2:
						devcom = pgres2[0]
					else:
						devcom = self.defaultSNMPRO
					thread.start_new_thread(self.fetchSNMPInfos,(devip,devname,devcom,vendor))
				""" Wait 1 second to lock program, else if script is too fast,it exists without discovering"""
				time.sleep(1)
			except StandardError, e:
				Logger.ZEyeLogger().write("Port ID Caching: FATAL %s" % e)
				
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("Port ID Caching: Pgsql Error %s" % e)
			sys.exit(1);	

		finally:

			if pgsqlCon:
				pgsqlCon.close()

			# We must wait 1 sec, else threadCounter == 0 because of fast algo
			time.sleep(1)
			while self.getThreadNb() > 0:
				time.sleep(1)

			totaltime = datetime.datetime.now() - starttime 
			Logger.ZEyeLogger().write("Port ID caching done (time: %s)" % totaltime)

