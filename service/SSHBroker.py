#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2011-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

import paramiko, re, logging

class ZEyeSSHBroker():
	sshConn = None
	sshUser = ""
	sshPwd = ""
	sshHost = ""
	logger = None

	def __init__(self,host,user,pwd):
		self.sshHost = host
		self.sshUser = user
		self.sshPwd = pwd
		self.logger = logging.getLogger("Z-Eye")

	def connect(self):
		if self.sshHost == "" or self.sshUser == "" or self.sshPwd == "":
			return False

		self.sshConn = paramiko.SSHClient()
		try:
			self.sshConn.set_missing_host_key_policy(paramiko.AutoAddPolicy())
			self.sshConn.connect(self.sshHost, username=self.sshUser, password=self.sshPwd)
			return True 
		except paramiko.AuthenticationException, e:
			self.logger.error("SSH Authentication failure (host %s, user %s, password %s)" % (self.sshHost,self.sshUser,self.sshPwd))
			return False
		except Exception, e:
			if str(e) == "[Errno 60] Operation timed out" or str(e) == "[Errno 64] Host is down" or str(e) == "[Errno 61] Connection refused":
				self.logger.error("SSH %s for host %s (user %s)" % (e,self.sshHost,self.sshUser))
			else:
				self.logger.critical("SSH Exception (host %s, user %s): %s" % (self.sshHost,self.sshUser,e))
			return False

	def sendCmd(self,cmd):
		if self.sshConn != None:
			stdin,stdout,stderr = self.sshConn.exec_command(cmd)
			return stdout.read()
		else:
			self.logger.critical("SSH Fatal error, ssh connection not opened !")
			return None

	def getRemoteOS(self):
		if self.sshConn == None:
			return None
		res = self.sendCmd("uname")
		res = re.sub("[\n\r]","",res)
		return res

	def isRemoteExists(self,path):
		if self.sshConn == None:
			return None
		res = self.sendCmd("if [ -e \"%s\" ]; then echo '0'; else echo '1'; fi;" % path)
		res = re.sub("[\n\r]","",res)
		if res == "0":
			return True
		return False

	def isRemoteReadable(self,path):
		if self.sshConn == None:
			return None
		res = self.sendCmd("if [ -r \"%s\" ]; then echo '0'; else echo '1'; fi;" % path)
		res = re.sub("[\n\r]","",res)
		if res == "0":
			return True
		return False

	def isRemoteExecutable(self,path):
		if self.sshConn == None:
			return None
		res = self.sendCmd("if [ -d \"%s\" ]; then echo '0'; else echo '1'; fi;" % path)
		res = re.sub("[\n\r]","",res)
		if res == "0":
			return True
		return False

	def isRemoteWritable(self,path):
		if self.sshConn == None:
			return None
		res = self.sendCmd("if [ -w \"%s\" ]; then echo '0'; else echo '1'; fi;" % path)
		res = re.sub("[\n\r]","",res)
		if res == "0":
			return True
		return False

	def close(self):
		if self.sshConn != "":
			self.sshConn.close()
