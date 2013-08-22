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
import datetime,re,time,threading,subprocess

import Logger,ZEyeUtil

class ZEyePeriodicCmd(ZEyeUtil.Thread):
	processName = ""
	processCmd = ""
	
	def __init__(self,sleep,start,pname,cmd):
		self.sleepingTimer = sleep
		self.startTimer = start
		self.processName = pname
		self.processCmd = cmd
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("%s (periodic cmd) process launched" % self.processName)
		time.sleep(self.startTimer)
		while True:
			self.launchCmd()
			time.sleep(self.sleepingTimer)

	def launchCmd(self):
		starttime = datetime.datetime.now()
		Logger.ZEyeLogger().write("%s (periodic cmd) started" % self.processName)
		
		subprocess.check_output(re.split(" ",self.processCmd))
		
		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("%s (periodic cmd) done (time: %s)" % (self.processName,totaltime))
