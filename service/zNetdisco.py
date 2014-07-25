# -*- Coding: utf-8 -*-
"""
* Copyright (C) 2010-2014 Loic BLOT, CNRS <http://www.unix-experience.fr/>
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

import sys, re, time, thread, subprocess

from pyPgSQL import PgSQL

import ZEyeUtil, zConfig
from DatabaseManager import ZEyeSQLMgr

class NetdiscoDataRefresher(ZEyeUtil.Thread):
	def __init__(self):
		""" 15 min between two netdisco updates """
		self.sleepingTimer = 900
		self.myName = "Netdisco Data Refresher"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			
			starttime = datetime.datetime.now()
			
			self.launchRefresh()
			
			"""
			Because netdisco can be slow, modify the sleeping timer to 
			refresh datas faster
			"""
			
			totaltime = datetime.datetime.now() - starttime
			"""
			If runtime exceed 10 mins, sleeping timer is 15 min - totaltime
			But if there is less than 1 minute interval, let 1 min interval
			"""
			
			if totaltime > 600:
				self.sleepingTimer = 900 - totaltime
				if self.sleepingTimer < 60:
					self.sleepingTimer = 60
				
			self.setRunning(False)

	def launchRefresh(self):
		try:
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -R"
			subprocess.check_output(cmd,shell=True)
			
			self.logInfo("Refresh OK, now nbtwalk")
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -w" % device
			subprocess.check_output(cmd,shell=True)
			
			self.logInfo("nbtwalk OK, now macwalk")
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -m" % device
			subprocess.check_output(cmd,shell=True)
			
			self.logInfo("macwalk OK, now arpwalk")			
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -a" % device
			subprocess.check_output(cmd,shell=True)
			
		except Exception, e:
			self.logCritical(e)
			sys.exit(1);

class NetdiscoDataCleanup(ZEyeUtil.Thread):
	def __init__(self):
		""" 1 day between two netdisco updates """
		self.sleepingTimer = 86400
		self.myName = "Netdisco Data Cleanup"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchCleanup()
			self.setRunning(False)

	def launchCleanup(self):
		try:
			self.pgcon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			self.pgcursor = self.pgcon.cursor()
			self.pgcursor.execute("DELETE FROM z_eye_switch_port_prises WHERE (ip,port) NOT IN (select host(ip),port from device_port)")
			self.pgcon.commit()
			
		except Exception, e:
			self.logCritical(e)
			sys.exit(1);
