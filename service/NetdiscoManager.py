# -*- Coding: utf-8 -*-
"""
* Copyright (C) 2010-2012 Loic BLOT, CNRS <http://www.unix-experience.fr/>
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

import datetime, sys, re, time, thread, threading, subprocess, logging
from threading import Lock
import ZEyeUtil, netdiscoCfg
from DatabaseManager import ZEyeSQLMgr

class ZEyeNetdiscoDataRefresher(ZEyeUtil.Thread):
	zeyeDB = None
	
	def __init__(self):
		""" 15 min between two netdisco updates """
		self.sleepingTimer = 900
		self.myName = "Netdisco Data Refresher"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchRefresh()
			self.setRunning(False)

	def launchRefresh(self):
		try:		
			self.zeyeDB = ZEyeSQLMgr()	
			self.zeyeDB.initForZEye()
			
			pgres = self.zeyeDB.Select("device","ip")
			for idx in pgres:
				if len(idx[0]) > 0:
					thread.start_new_thread(self.doRefreshDevice,(idx[0],))
		except Exception, e:
			self.logger.critical("Netdisco Data Refresher: %s" % e)
			sys.exit(1);	
		finally:
			if self.zeyeDB != None:
				self.zeyeDB.close()
			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logger.debug("Netdisco Data Refresher: waiting %d threads" % self.getThreadNb())
			time.sleep(1)

	def doRefreshDevice(self,device):
		self.incrThreadNb()
		self.logger.debug("Netdisco Data Refresher: refresh device %s" % device)
		
		# We refresh all datas
		try:
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -d %s" % device
			subprocess.check_output(cmd,shell=True)
			
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -W %s" % device
			subprocess.check_output(cmd,shell=True)
			
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -M %s" % device
			subprocess.check_output(cmd,shell=True)
						
			cmd = "/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -A %s" % device
			subprocess.check_output(cmd,shell=True)
			
		except Exception, e:
			self.logger.critical("Netdisco Data Refresher: %s (device %s)" % (e,device))
			self.decrThreadNb()
			return
		
		self.logger.debug("Netdisco Data Refresher: device %s refreshed" % device)
		self.decrThreadNb()
