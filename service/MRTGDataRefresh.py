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

import datetime, time, os,re, subprocess, thread, threading
from threading import Lock

import Logger, ZEyeUtil

class ZEyeMRTGDataRefresher(ZEyeUtil.Thread):
	sleepingTimer = 0
        startTime = 0
	tc_mutex = Lock()
	threadCounter = 0
	max_threads = 20

        def __init__(self):
                """ 5 mins between two refresh """
                self.sleepingTimer = 5*60
                ZEyeUtil.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("MRTG Data Refresher launched")
		while True:
			self.launchRefreshProcess()
			time.sleep(self.sleepingTimer)

	def refreshMRTG(self,filename,blackhole):
		self.incrThreadNb()
		try:
			subprocess.check_output(["/usr/local/bin/perl", "/usr/local/bin/mrtg", "%s" % filename])
		except Exception, e:
			Logger.ZEyeLogger().write("MRTG Data Refresher: FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def launchRefreshProcess(self):
		while self.SNMPcc.isRunning == True:
                        Logger.ZEyeLogger().write("MRTG-Datas-Refresh: SNMP community caching is running, waiting 10 seconds")
                        time.sleep(10)	
		try:
			starttime = datetime.datetime.now()
			Logger.ZEyeLogger().write("MRTG datas refresh started, searching config into dir: %s" % os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/")

			_dir = os.listdir(os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/");
			for file in _dir:
				filename = os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/"+file
				# Launch only if it's a .cfg, recent MRTG create .ok files
				if(os.path.isfile(filename) and re.search("cfg$",filename) != None):
					while self.getThreadNb() >= self.max_threads:
						time.sleep(1)
					thread.start_new_thread(self.refreshMRTG,(filename,0))

			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				time.sleep(1)
			totaltime = datetime.datetime.now() - starttime 
			Logger.ZEyeLogger().write("MRTG datas refresh done (time: %s)" % totaltime)
		except Exception, e:
			Logger.ZEyeLogger().write("MRTG Data Refresher: FATAL %s" % e)

