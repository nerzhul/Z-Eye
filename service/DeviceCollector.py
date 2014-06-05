# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
import re, sys, time, thread, subprocess
import logging
import base64

import ZEyeUtil
import zConfig
from SSHBroker import ZEyeSSHBroker

class ZEyeSwitchesConfigCollector(ZEyeUtil.Thread):
	def __init__(self):
		""" 5 minutes between two collects"""
		self.sleepingTimer = 5*60
		self.myName = "Switch config collector"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.collectConfigurations()
			self.setRunning(False)

	def collectConfigurations(self):
		try:
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT device,sshuser,sshpwd,enablepwd FROM z_eye_switch_pwd")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					pgcursor2 = pgsqlCon.cursor()
					pgcursor2.execute("SELECT ip FROM device WHERE name = '%s'" % idx[0])
					pgres2 = pgcursor2.fetchone()
					if pgres2 != None:
						thread.start_new_thread(self.connectAndCollectConfig,(idx[0],pgres2[0],idx[1],idx[2],idx[3]))
			except StandardError, e:
				self.logCritical(e)
				return
				
		except PgSQL.Error, e:
			self.logCritical("FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()
			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logDebug("waiting %d threads" % self.getThreadNb())
				time.sleep(1)

	def connectAndCollectConfig(self,deviceName,deviceIP,sshUser,sshPwd,privilegedPwd):
		self.incrThreadNb()

		try:
			sshPwdClear = base64.b64decode(sshPwd)
			privilegedPwdClear = base64.b64decode(privilegedPwd)
			
			ssh = ZEyeSSHBroker(deviceIP,sshUser,sshPwdClear)
			if ssh.connect() == False:
				self.decrThreadNb()
				return
				
			if ssh.setupPrivileges(privilegedPwdClear) == False:
				self.decrThreadNb()
				return
			
			startupCfg = ""
			runningCfg = ""	
			#startupCfg = ssh.sendPrivilegedCmd("show startup-config")
			#runningCfg = ssh.sendPrivilegedCmd("show running-config")
			
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			
			pgcursor.execute("DELETE FROM z_eye_switch_configs WHERE device = '%s'" % deviceName)
			
			if len(startupCfg) > 0:
				pgcursor.execute("INSERT INTO z_eye_switch_configs (device,cfgtype,cfgoutput) VALUES ('%s','1','%s')" % (deviceName,startupCfg))
				
			if len(runningCfg) > 0:
				pgcursor.execute("INSERT INTO z_eye_switch_configs (device,cfgtype,cfgoutput) VALUES ('%s','2','%s')" % (deviceName,runningCfg))
				
			pgsqlCon.commit()
			pgcursor.close()
			pgsqlCon.close()
			
			ssh.disablePrivileges()
		except Exception, e:
			self.logCritical("connectAndCollectConfig: FATAL %s" % e)
		finally:
			self.decrThreadNb()
