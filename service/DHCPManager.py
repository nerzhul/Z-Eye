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

from pyPgSQL import PgSQL
import datetime,re,sys,time,thread,threading,subprocess
from threading import Lock
from ipcalc import Network

import Logger,netdiscoCfg
from SSHBroker import ZEyeSSHBroker

"""
* Please note this element support only ISC DHCP daemon at this time. BSD native daemon will be supported later
* Note: if you use IPManager and Z-Eye DHCP manager you need this line in your crontab:
*   if [ -r /tmp/dhcprestart ] && [ "$(/bin/cat /tmp/dhcprestart)" -eq "1" ]; then <servicecmd> restart && /bin/rm /tmp/dhcprestart; fi
* Replace <servicecmd> with your starting cmd
*   Linux Debian: service isc-dhcp-server restart
*   Other Linux: service dhcpd restart (to verify)
*   FreeBSD: service isc-dhcpd restart
*   OpenBSD: /etc/rc.d/dhcpd restart
"""

class ZEyeDHCPManager(threading.Thread):
	sleepingTimer = 0
	startTime = 0
	threadCounter = 0
	tc_mutex = Lock()
	ipList = {}
	subnetList = {}
	clusterList = {}

	def __init__(self):
		""" 1 min between two DHCP updates """
		self.sleepingTimer = 60
		threading.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("Z-Eye DHCP Manager launched")
		while True:
			self.launchDHCPManagement()
			time.sleep(self.sleepingTimer)

	def incrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter = self.threadCounter + 1
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

	def launchDHCPManagement(self):
		Logger.ZEyeLogger().write("DHCP Management task started")
		starttime = datetime.datetime.now()
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT addr,sshuser,sshpwd,reservconfpath,subnetconfpath FROM z_eye_dhcp_servers")
			pgres = pgcursor.fetchall()
			if pgcursor.rowcount > 0:
				# Buffer for better performances
				self.loadIPList(pgcursor)
				self.loadSubnetList(pgcursor)
				self.loadClusterList(pgcursor)
				for idx in pgres:
					if len(idx[1]) > 0 and len(idx[2]) > 0 and len(idx[3]) > 0 and len(idx[4]) > 0:
						thread.start_new_thread(self.doConfigDHCP,(idx[0],idx[1],idx[2],idx[3],idx[4]))
		except Exception, e:
			Logger.ZEyeLogger().write("DHCP Manager: FATAL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			time.sleep(1)

		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("DHCP Management task done (time: %s)" % totaltime)

	def doConfigDHCP(self,addr,user,pwd,reservpath,subnetpath):
		self.incrThreadNb()

		subnetBuf = ""
		reservBuf = ""
		try:
			# One pgsql connection per thread
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT clustername FROM z_eye_dhcp_cluster WHERE dhcpaddr = '%s'" % addr)
			pgres = pgcursor.fetchall()

			# No cluster, then no action to do
			if pgcursor.rowcount == 0:
				self.decrThreadNb()
				return

			ssh = ZEyeSSHBroker(addr,user,pwd)
			if ssh.connect() == False:
				self.decrThreadNb()
				return

			# We get the remote OS for some commands
			remoteOs = ssh.getRemoteOS()
			if remoteOs != "Linux" and remoteOs != "FreeBSD" and remoteOs != "OpenBSD":
				Logger.ZEyeLogger().write("DHCP Manager: %s OS (on %s) is not supported" % (remoteOs,addr))
				self.decrThreadNb()
				return

			hashCmd = ""
			if remoteOs == "Linux":
				hashCmd = "md5sum"
			elif remoteOs == "FreeBSD" or remoteOs == "OpenBSD":
				hashCmd = "md5 -q"
		
			# We test file existence. If they doesn't exist, we create it. If creation failed, the DHCP manager cannot use this server
			if ssh.isRemoteExists(reservpath) == False:
				ssh.sendCmd("touch %s" % reservpath)

			if ssh.isRemoteWritable(reservpath) == False:
				Logger.ZEyeLogger().write("DHCP Manager: %s (on %s) is not writable" % (reservpath,addr))
				self.decrThreadNb()
				return
		
			if ssh.isRemoteExists(subnetpath) == False:
				ssh.sendCmd("touch %s" % subnetpath)

			if ssh.isRemoteWritable(subnetpath) == False:
				Logger.ZEyeLogger().write("DHCP Manager: %s (on %s) is not writable" % (subnetpath,addr))
				self.decrThreadNb()
				return
		
			if ssh.isRemoteExists("/tmp/dhcprestart") == False:
				ssh.sendCmd("touch %s" % "/tmp/dhcprestart")

			if ssh.isRemoteWritable("/tmp/dhcprestart") == False:
				Logger.ZEyeLogger().write("DHCP Manager: %s (on %s) is not writable" % ("/tmp/dhcprestart",addr))
				self.decrThreadNb()
				return
			
			for idx in pgres:
				if idx[0] in self.clusterList:
					for subnet in self.clusterList[idx[0]]:
						netmask = self.subnetList[subnet][0]
						subnetIpList = self.subnetList[subnet][1]

						# Special case for DNS2
						dns2 = ""
						if len(self.subnetList[subnet][4]) > 0 and self.subnetList[subnet][4] != self.subnetList[subnet][3]:
							dns2 = " %s" % self.subnetList[subnet][4]

						subnetBuf += "subnet %s netmask %s {\n\toption routers %s;\n\toption domain-name \"%s\";\n" % (subnet,netmask,self.subnetList[subnet][2],self.subnetList[subnet][5])
						subnetBuf += "\toption domain-name-servers %s%s;\n}\n" % (self.subnetList[subnet][3],dns2)
						for ip in subnetIpList:
							reservBuf += "host %s {\n\thardware ethernet %s;\n\tfixed-address %s;\n}\n" % (self.ipList[ip][1],self.ipList[ip][0],ip)
	
			
			# check md5 trace to see if subnet file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (subnetpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % subnetBuf])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (subnetBuf,subnetpath))
				ssh.sendCmd("echo 1 > /tmp/dhcprestart")
				Logger.ZEyeLogger().write("DHCP Manager: subnets modified on %s" % addr)
			
			# check md5 trace to see if reserv file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (reservpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % reservBuf])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (reservBuf,reservpath))
				ssh.sendCmd("echo 1 > /tmp/dhcprestart")
				Logger.ZEyeLogger().write("DHCP Manager: reservations modified on %s" % addr)

			ssh.close()
		except Exception, e:
			Logger.ZEyeLogger().write("DHCP Manager: FATAL %s" % e)

		self.decrThreadNb()

	def loadIPList(self,pgcursor):
		self.ipList = {}
		pgcursor.execute("SELECT ip,macaddr,hostname FROM z_eye_dhcp_ip WHERE reserv = 't'")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# We need hostname and mac addr for a reservation
			if len(idx[1]) > 0 and len(idx[2]) > 0:	
				self.ipList[idx[0]] = (idx[1],idx[2])

	def loadClusterList(self,pgcursor):
		self.clusterList = {}
		pgcursor.execute("SELECT clustername,subnet FROM z_eye_dhcp_subnet_cluster")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.clusterList:
				self.clusterList[idx[0]] = []
			if idx[1] not in self.clusterList[idx[0]] and idx[1] in self.subnetList:
				self.clusterList[idx[0]].append(idx[1])

	def loadSubnetList(self,pgcursor):
		self.subnetList = {}
		# We only load netid attached to clusters
		pgcursor.execute("SELECT netid,netmask,router,dns1,dns2,domainname FROM z_eye_dhcp_subnet_v4_declared WHERE netid in (SELECT subnet FROM z_eye_dhcp_subnet_cluster)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# Those fields are required
			if len(idx[2]) > 0 and len(idx[3]) > 0 and len(idx[5]) > 0:
				ipList = []
				for ip in self.ipList:
					# If ip in subnet we add it to list
					if ip in Network("%s/%s" % (idx[0],idx[1])):
						ipList.append(ip)
				self.subnetList[idx[0]] = (idx[1],ipList,idx[2],idx[3],idx[4],idx[5])

