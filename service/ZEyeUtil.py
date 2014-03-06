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

import threading, logging, datetime, time
from threading import Lock

def getCIDR(netmask):
	netmask = netmask.split('.')
	binary_str = ''
	for octet in netmask:
		binary_str += bin(int(octet))[2:].zfill(8)
	return str(len(binary_str.rstrip('0')))

def addslashes(s):
	l = ["\\", '"', "'", "\0", ]
	for i in l:
		if i in s:
			s = s.replace(i, '\\'+i)
	return s

class Thread(threading.Thread):
	# sub thread counter
	threadCounterMutex = Lock()
	threadCounter = 0
	max_threads = 30
	
	# For threading sync
	runStatus = False
	runningMutex = Lock()
	runStartTime = 0

	# Timers
	sleepingTimer = 0
	startTimer = 0
	
	# Naming
	logger = None
	myName = "UNK-Thread-Name"

	def __init__(self):
		threading.Thread.__init__(self)
		self.logger = logging.getLogger("Z-Eye")

	def incrThreadNb(self):
		self.threadCounterMutex.acquire()
		self.threadCounter += 1
		self.threadCounterMutex.release()

	def decrThreadNb(self):
		self.threadCounterMutex.acquire()
		self.threadCounter = self.threadCounter - 1
		self.threadCounterMutex.release()

	def getThreadNb(self):
		val = 0
		self.threadCounterMutex.acquire()
		val = self.threadCounter
		self.threadCounterMutex.release()
		return val
	
	def setRunning(self,runStatus):
		self.runningMutex.acquire()
		self.runStatus = runStatus
		self.runningMutex.release()
		
		if runStatus == True:
			self.runStartTime = datetime.datetime.now()
			self.startMsg()
		else:
			self.endMsg()
			time.sleep(self.sleepingTimer)
		
	def isRunning(self):
		rs = True
		self.runningMutex.acquire()
		rs = self.runStatus
		self.runningMutex.release()
		return rs

	def launchMsg(self):
		self.logger.info("%s launched" % self.myName)
		
	def startMsg(self):
		self.logger.info("%s started" % self.myName)
	
	def endMsg(self):
		totaltime = datetime.datetime.now() - self.runStartTime 
		self.logger.info("%s done (time: %s)" % (self.myName,totaltime))
