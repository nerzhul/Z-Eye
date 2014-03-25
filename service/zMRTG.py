#! python
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
import datetime, os, re, sys, time, thread, threading, subprocess, logging
from threading import Lock

import ZEyeUtil
import zConfig

class MRTGDiscoverer(ZEyeUtil.Thread):
	SNMPcc = None

	def __init__(self,SNMPcc):
		""" 30 mins between two discover """
		self.sleepingTimer = 30*60
		self.SNMPcc = SNMPcc
		self.myName = "MRTG Config Discoverer"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchCfgGenerator()
			self.setRunning(False)

	def launchCfgGenerator(self):
		while self.SNMPcc.isRunning():
			self.logger.debug("MRTG-Config-Discovery: SNMP community caching is running, waiting 10 seconds")
			time.sleep(10)

		self.logger.info("MRTG configuration discovery started")
		starttime = datetime.datetime.now()
		try:
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT ip,name FROM device ORDER BY ip")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					devip = idx[0]
					devname = idx[1]
					devcom = self.SNMPcc.getReadCommunity(devname)

					# Only launch process if SNMP cache is ok
					if devcom == None:
						self.logger.error("MRTG-Config-Discovery: No read community found for %s" % devname)
					else:
						thread.start_new_thread(self.fetchMRTGInfos,(devip,devname,devcom))
			except StandardError, e:
				self.logger.critical("MRTG-Config-Discovery: %s" % e)
				return
				
		except PgSQL.Error, e:
			self.logger.critical("MRTG-Config-Discovery: FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			self.logger.debug("MRTG configuration discovery waiting %d threads" % self.getThreadNb())
			time.sleep(1)

	def fetchMRTGInfos(self,ip,devname,devcom):
		self.incrThreadNb()

		try:
			text = subprocess.check_output(["/usr/local/bin/perl","/usr/local/bin/cfgmaker", "%s@%s" % (devcom,ip)])
			text += "\nWorkDir: /usr/local/www/z-eye/datas/rrd"
			cfgfile = open("/usr/local/www/z-eye/datas/mrtg-config/mrtg-%s.cfg" % devname,"w")
			cfgfile.writelines(text)
			cfgfile.close()
		except Exception, e:
			self.logger.debug("MRTG-Config-Discovery: %s" % e)
		finally:
			self.decrThreadNb()

class MRTGDataRefresher(ZEyeUtil.Thread):
	sleepingTimer = 0
	startTime = 0
	threadCounter = 0
	max_threads = 20
	MRTGcd = None

	def __init__(self,MRTGcd):
		""" 5 mins between two refresh """
		self.sleepingTimer = 5*60
		self.MRTGcd = MRTGcd
		self.myName = "MRTG Data Refresher"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchRefreshProcess()
			self.setRunning(False)

	def refreshMRTG(self,filename,blackhole):
		self.incrThreadNb()
		try:
			subprocess.check_output(["/usr/local/bin/perl", "/usr/local/bin/mrtg", "%s" % filename])
		except Exception, e:
			self.logger.critical("MRTG Data Refresher: FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def launchRefreshProcess(self):
		while self.MRTGcd.isRunning():
			self.logger.debug("MRTG-Datas-Refresh: SNMP community caching is running, waiting 10 seconds")
			time.sleep(10)
		
		while self.MRTGcd.isRunning():
			self.logger.debug("MRTG-Datas-Refresh: MRTG Config discovery is running, waiting 10 seconds")
			time.sleep(10)
		
		try:
			self.logger.info("MRTG datas refresh started, searching config into dir: %s" % os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/")

			_dir = os.listdir(os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/");
			for _file in _dir:
				filename = os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/"+_file
				# Launch only if it's a .cfg, recent MRTG create .ok files
				if(os.path.isfile(filename) and re.search("cfg$",filename) != None):
					while self.getThreadNb() >= self.max_threads:
						time.sleep(1)
					thread.start_new_thread(self.refreshMRTG,(filename,0))

			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				time.sleep(1)
			
		except Exception, e:
			self.logger.critical("MRTG Data Refresher: %s" % e)
