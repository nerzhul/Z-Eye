#! python
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
import sys, thread, subprocess, string, time, commands, re

import dns.query
import dns.resolver
import dns.zone
from dns.exception import DNSException
import dns.tsigkeyring

import logging
import zConfig
import ZEyeUtil
from SSHBroker import ZEyeSSHBroker

class Manager(ZEyeUtil.Thread):
	serverList = {}
	clusterList = {}
	tsigList = {}
	aclList = {}
	zoneList = {}

	def __init__(self):
		""" 1 min between two DNS updates """
		self.sleepingTimer = 60
		self.myName = "DNS Manager"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchDNSManagement()
			self.setRunning(False)

	def launchDNSManagement(self):
		try:
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			self.loadServerList(pgcursor)
			# Only load servers in clusters
			if len(self.serverList) > 0:
				for server in self.serverList:
					# Buffer for better performances
					self.loadTSIGList(pgcursor)
					self.loadACLList(pgcursor)
					self.loadClusterList(pgcursor)
					self.loadZoneList(pgcursor)
					thread.start_new_thread(self.doConfigDNS,(server,self.serverList[server][0],self.serverList[server][1],self.serverList[server][2],self.serverList[server][3],self.serverList[server][4],self.serverList[server][5],self.serverList[server][6],self.serverList[server][7],self.serverList[server][8],self.serverList[server][9]))
		except Exception, e:
			self.logCritical(e)
			sys.exit(1);
		finally:
			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logDebug("waiting %d threads" % self.getThreadNb())
				time.sleep(1)

	def doConfigDNS(self,addr,user,pwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn,tsigtransfer,tsigupdate):
		self.incrThreadNb()

		cfgbuffer = ""
		try:
			# No zone or cluster, stop it
			if len(self.zoneList) == 0 or len(self.clusterList) == 0:
				self.decrThreadNb()
				return

			ssh = ZEyeSSHBroker(addr,user,pwd)
			if ssh.connect() == False:
				self.decrThreadNb()
				return

			# We get the remote OS for some commands
			remoteOs = ssh.getRemoteOS()
			if remoteOs != "Linux" and remoteOs != "FreeBSD" and remoteOs != "OpenBSD":
				self.logError("%s OS (on %s) is not supported" % (remoteOs,addr))
				self.decrThreadNb()
				return

			hashCmd = ""
			if remoteOs == "Linux":
				hashCmd = "md5sum"
			elif remoteOs == "FreeBSD" or remoteOs == "OpenBSD":
				hashCmd = "md5 -q"

			# We test file existence. If they doesn't exist, we create it. If creation failed, the DNS manager cannot use this server
			if ssh.isRemoteExists(zeyenamedpath) == False:
				ssh.sendCmd("touch %s" % zeyenamedpath)

			if ssh.isRemoteWritable(zeyenamedpath) == False:
				self.logError("%s (on %s) is not writable, no DNS configuration will be done on this server" % (zeyenamedpath,addr))
				self.decrThreadNb()
				return
				
			if ssh.isRemoteExists("/tmp/dnsrestart") == False:
				ssh.sendCmd("touch /tmp/dnsrestart")

			if ssh.isRemoteWritable("/tmp/dnsrestart") == False:
				self.logError("/tmp/dnsrestart (on %s) is not writable, no DNS configuration will be done on this server" % addr)
				self.decrThreadNb()
				return
				
			# Write options
			for cluster in self.clusterList:
				if addr in self.clusterList[cluster][0] or addr in self.clusterList[cluster][1] or addr in self.clusterList[cluster][2]:
					tmpcfgbuffer = ""
					if len(self.clusterList[cluster][3]) > 0:
						tmpcfgbuffer += "\tallow-recursion {\n"
						for acl in self.clusterList[cluster][3]:
							if acl == "none" or acl == "any":
								tmpcfgbuffer += "\t\t%s;\n" % acl
							else:
								tmpcfgbuffer += "\t\t\"%s\";\n" % acl
						tmpcfgbuffer += "\t};\n"

					if len(self.clusterList[cluster][4]) > 0:
						tmpcfgbuffer += "\tallow-transfer {\n"
						for acl in self.clusterList[cluster][4]:
							# Only write non if no tsig transfer key
							if acl == "none":
								if tsigtransfer == "":
									tmpcfgbuffer += "\t\t%s;\n" % acl
							elif acl == "any":
								tmpcfgbuffer += "\t\t%s;\n" % acl
							else:
								tmpcfgbuffer += "\t\t\"%s\";\n" % acl
						if tsigtransfer != "":
							tmpcfgbuffer += "\t\tkey \"%s\";\n" % self.tsigList[tsigtransfer][0]
						tmpcfgbuffer += "\t};\n"

					if len(self.clusterList[cluster][5]) > 0:
						tmpcfgbuffer += "\tallow-update {\n"
						for acl in self.clusterList[cluster][5]:
							# Only write non if no tsig update key
							if acl == "none":
								if tsigupdate == "":
									tmpcfgbuffer += "\t\t%s;\n" % acl
							elif acl == "any":
								tmpcfgbuffer += "\t\t%s;\n" % acl
							else:
								tmpcfgbuffer += "\t\t\"%s\";\n" % acl
						if tsigupdate != "":
							tmpcfgbuffer += "\t\tkey \"%s\";\n" % self.tsigList[tsigupdate][0]
						tmpcfgbuffer += "\t};\n"

					if len(self.clusterList[cluster][6]) > 0:
						tmpcfgbuffer += "\tallow-notify {\n"
						for acl in self.clusterList[cluster][6]:
							if acl == "none" or acl == "any":
								tmpcfgbuffer += "\t\t%s;\n" % acl
							else:
								tmpcfgbuffer += "\t\t\"%s\";\n" % acl
						tmpcfgbuffer += "\t};\n"

					if len(self.clusterList[cluster][7]) > 0:
						tmpcfgbuffer += "\tallow-query {\n\t\t127.0.0.1;\n\t\t::1;\n"
						for acl in self.clusterList[cluster][7]:
							# If none ACL is found, don't write it because we must allow localhost
							if acl == "any":
								tmpcfgbuffer += "\t\t%s;\n" % acl
							elif acl != "none":
								tmpcfgbuffer += "\t\t\"%s\";\n" % acl
						tmpcfgbuffer += "\t};\n"
						
					if self.clusterList[cluster][8] == True:
						tmpcfgbuffer += "\tdnssec-enable yes;\n"
					else:
						tmpcfgbuffer += "\tdnssec-enable no;\n"
						
					if self.clusterList[cluster][9] == True:
						tmpcfgbuffer += "\tdnssec-validation yes;\n"
					else:
						tmpcfgbuffer += "\tdnssec-validation no;\n"

					if len(tmpcfgbuffer) > 0:
						cfgbuffer += "options {\n\tversion\"\";\n\tlisten-on { any; };\n\tlisten-on-v6 { any; };\n%s};\n" % tmpcfgbuffer
					
			# Write TSIG keys
			for tsig in self.tsigList:
				algo = ""
				if self.tsigList[tsig][1] == 1:
					algo = "hmac-md5"
				elif self.tsigList[tsig][1] == 2:
					algo = "hmac-sha1"
				elif self.tsigList[tsig][2] == 3:
					algo = "hmac-sha256"
				if algo != "":
					cfgbuffer += "key \"%s\" {\n\talgorithm %s;\n\tsecret \"%s\";\n};\n" % (self.tsigList[tsig][0],algo,self.tsigList[tsig][2])	
					
			# Write ACLS
			for acl in self.aclList:
				tmpcfgbuffer = ""
				for ip in self.aclList[acl][0]:
					tmpcfgbuffer += "\t%s;\n" % ip
				for network in self.aclList[acl][1]:
					tmpcfgbuffer += "\t%s;\n" % network
				for childacl in self.aclList[acl][2]:
					tmpcfgbuffer += "\t\"%s\";\n" % childacl
				for tsig in self.aclList[acl][3]:
					tmpcfgbuffer += "\tkey \"%s\";\n" % tsig
				for dnsname in self.aclList[acl][4]:
					# We need to catch exception. If exception, name not resolvable
					try:
						dnsanswer = dns.resolver.query(dnsname,'A')
						for rdata in dnsanswer:
							tmpcfgbuffer += "\t%s;\n" % rdata.address
					except DNSException, e:
						self.logError("unable to resolve name %s" % dnsname)

				if len(tmpcfgbuffer) > 0:
					cfgbuffer += "acl \"%s\" {\n%s};\n" % (acl,tmpcfgbuffer)

			for zone in self.zoneList:
				srvType = 0 
				clusterCaches = False
				for cluster in self.zoneList[zone][1]:
					# This server must be in the zone clusters
					if addr in self.clusterList[cluster][0]:
						srvType = 1
					elif addr in self.clusterList[cluster][1]:
						srvType = 2
					elif addr in self.clusterList[cluster][2]:
						srvType = 3 
					if len(self.clusterList[cluster][2]) > 0:
						clusterCaches = True
				
				if srvType > 0:
					validZone = False

					masterList = None
					slaveList = None
					cacheList = None

					tmpcfgbuffer = "zone \"%s.\" {\n" % zone
					# Zone in classic mode
					if self.zoneList[zone][0] == 1:
						# Configuration for caches
						if clusterCaches:
							if srvType == 3:
								tmpcfgbuffer += "\ttype forward;\n\tforwarders {\n"
								# Forwarders are master and then slaves
								for cluster in self.zoneList[zone][1]:
									for master in self.clusterList[cluster][0]:
										tmpcfgbuffer += "\t\t%s;\n" % master
									for slave in self.clusterList[cluster][1]:
										tmpcfgbuffer += "\t\t%s;\n" % slave 

								validZone = True
						else:
							# Configuration for masters
							if srvType == 1:
								tmpcfgbuffer += "\ttype master;\n"
								if len(chrootpath) > 0:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (re.sub(ZEyeUtil.addslashes(chrootpath),"",mzonepath),zone)
								else:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (mzonepath,zone)

								transferBuf = ""
								updateBuf = ""
								queryBuf = ""

								"""
								If there is slaves, we must allow transfer and queries
								If there is caches, we must allow queries
								We also load herited rules if herited is selected
								"""
								for cluster in self.zoneList[zone][1]:
									if len(self.clusterList[cluster][1]) > 0:
										slaveList = self.clusterList[cluster][1]
									if len(self.clusterList[cluster][2]) > 0:
										cacheList = self.clusterList[cluster][2]

									if "herited" in self.zoneList[zone][4]:
										for acl in self.clusterList[cluster][4]:
											if acl != "none":
												transferBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][6]:
										for acl in self.clusterList[cluster][5]:
											if acl != "none":
												updateBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][7]:
										for acl in self.clusterList[cluster][7]:
											if acl != "none":
												queryBuf += "\t\t\"%s\";\n" % acl


								if slaveList != None:
									for slave in slaveList:
										transferBuf += "\t\t%s;\n" % slave
										queryBuf += "\t\t%s;\n" % slave 

								if cacheList != None:
									for cache in cacheList:
										queryBuf += "\t\t%s;\n" % cache
										
								"""
								We add Z-Eye TSIG key, if present
								"""
								if tsigtransfer != None and tsigtransfer != "" and tsigtransfer in self.tsigList:
									transferBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0]
									queryBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0]
									updateBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0]

								"""
								Now we load real ACLs
								If any, allow all and simplify output.
								If none or herited, only allow cluster members (needed to have a working cluster)
								Else, allow cluster members + ACLS
								"""
								if "any" in self.zoneList[zone][4]:
									transferBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][4] and "herited" not in self.zoneList[zone][4]:
									for acl in self.zoneList[zone][4]:
										transferBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][6]:
									updateBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][6] and "herited" not in self.zoneList[zone][6]:
									for acl in self.zoneList[zone][6]: 
										updateBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][7]:
									queryBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][7] and "herited" not in self.zoneList[zone][7]:
									for acl in self.zoneList[zone][7]: 
										queryBuf += "\t\t\"%s\";\n" % acl
								
								# And we write ACLs
								if len(transferBuf) > 0:
									tmpcfgbuffer += "\tallow-transfer {\n%s\t\t127.0.0.1;\n\t\t::1;\n\t};\n" % transferBuf
								if len(updateBuf) > 0:
									tmpcfgbuffer += "\tallow-update {\n%s\t};\n" % updateBuf 
								if len(queryBuf) > 0:
									tmpcfgbuffer += "\tallow-query {\n%s\t\t127.0.0.1;\n\t\t::1;\n\t};\n" % queryBuf

								tmpcfgbuffer += "\tnotify yes;\n"

								validZone = True
							# Configuration for slaves
							elif srvType == 2:
								tmpcfgbuffer += "\ttype slave;\n\tmasters {\n"
								# Now we configure masters
								for cluster in self.zoneList[zone][1]:
									for master in self.clusterList[cluster][0]:
										tmpcfgbuffer += "\t\t%s;\n" % master
								tmpcfgbuffer += "\t};\n"

								if len(chrootpath) > 0:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (re.sub(ZEyeUtil.addslashes(chrootpath),"",szonepath),zone)
								else:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (szonepath,zone)

								transferBuf = ""
								notifyBuf = ""
								queryBuf = ""

								"""
								If there is masters, we must allow transfer, queries and notify
								If there is caches, we must allow queries
								We also load herited rules if herited is selected
								"""
								for cluster in self.zoneList[zone][1]:
									if len(self.clusterList[cluster][0]) > 0:
										masterList = self.clusterList[cluster][0]
									if len(self.clusterList[cluster][2]) > 0:
										cacheList = self.clusterList[cluster][2]
										
									if "herited" in self.zoneList[zone][4]:
										for acl in self.clusterList[cluster][4]:
											if acl != "none":
												transferBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][5]:
										for acl in self.clusterList[cluster][6]:
											if acl != "none":
												notifyBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][7]:
										for acl in self.clusterList[cluster][7]:
											if acl != "none":
												queryBuf += "\t\t\"%s\";\n" % acl


								if masterList != None:
									for master in masterList:
										transferBuf += "\t\t%s;\n" % master 
										notifyBuf += "\t\t%s;\n" % master
										queryBuf += "\t\t%s;\n" % master

								if cacheList != None:
									for cache in cacheList:
										queryBuf += "\t\t%s;\n" % cache
								
								"""
								We add Z-Eye TSIG key, if present
								"""
								if tsigtransfer != None and tsigtransfer != "" and tsigtransfer in self.tsigList:
									transferBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0];
									queryBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0];

								"""
								Now we load real ACLs
								If any, allow all and simplify output.
								If none, only allow cluster members
								Else, allow cluster members + ACLS
								"""
								if "any" in self.zoneList[zone][4]:
									transferBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][4] and "herited" not in self.zoneList[zone][4]:
									for acl in self.zoneList[zone][4]:
										transferBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][5]:
									notifyBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][5] and "herited" not in self.zoneList[zone][5]:
									for acl in self.zoneList[zone][5]: 
										notifyBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][7]:
									queryBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][7] and "herited" not in self.zoneList[zone][7]:
									for acl in self.zoneList[zone][7]: 
										queryBuf += "\t\t\"%s\";\n" % acl
								
								# And we write ACLs
								if len(transferBuf) > 0:
									tmpcfgbuffer += "\tallow-transfer {\n%s\t\t127.0.0.1;\n\t\t::1;\n\t};\n" % transferBuf
								if len(notifyBuf) > 0:
									tmpcfgbuffer += "\tallow-notify {\n%s\t};\n" % notifyBuf
								if len(queryBuf) > 0:
									tmpcfgbuffer += "\tallow-query {\n%s\t\t127.0.0.1;\n\t\t::1;\n\t};\n" % queryBuf

								validZone = True
					# Zone in slave mode
					elif self.zoneList[zone][0] == 2:
						# Only if there is masters 
						if len(self.zoneList[zone][2]) > 0:
							# If there is caches, slave is on cache
							if clusterCaches:
								if srvType == 3:
									tmpcfgbuffer += "\ttype slave;\n\tmasters {\n"
									for fwd in self.zoneList[zone][2]:
										tmpcfgbuffer += "\t\t%s;\n" % fwd
									tmpcfgbuffer += "\t};\n"
									validZone = True
							else:
								tmpcfgbuffer += "\ttype slave;\n\tmasters {\n"
								for master in self.zoneList[zone][2]:
									tmpcfgbuffer += "\t\t%s;\n" % master
								tmpcfgbuffer += "\t};\n"

								if len(chrootpath) > 0:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (re.sub(ZEyeUtil.addslashes(chrootpath),"",szonepath),zone)
								else:
									tmpcfgbuffer += "\tfile \"%s/%s\";\n" % (szonepath,zone)

								transferBuf = ""
								notifyBuf = ""
								queryBuf = ""

								"""
								If there is masters, we must allow transfer and notify
								If there is caches, we must allow queries
								We also load herited rules if herited is selected
								"""
								for cluster in self.zoneList[zone][1]:
									if len(self.clusterList[cluster][2]) > 0:
										cacheList = self.clusterList[cluster][2]

									if "herited" in self.zoneList[zone][4]:
										for acl in self.clusterList[cluster][4]:
											transferBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][5]:
										for acl in self.clusterList[cluster][6]:
											notifyBuf += "\t\t\"%s\";\n" % acl
									if "herited" in self.zoneList[zone][7]:
										for acl in self.clusterList[cluster][7]:
											queryBuf += "\t\t\"%s\";\n" % acl

								for master in self.zoneList[zone][2]:
									notifyBuf += "\t\t%s;\n" % master 
									transferBuf += "\t\t%s;\n" % master 

								# If this is a cluster master of slave, we must allow cache to query
								if cacheList != None and srvType != 3:
									for cache in cacheList:
										queryBuf += "\t\t%s;\n" % cache
								
								"""
								We add Z-Eye TSIG key, if present
								"""
								if tsigtransfer != None and tsigtransfer != "" and tsigtransfer in self.tsigList:
									transferBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0];
									queryBuf += "\t\tkey %s;\n" % self.tsigList[tsigtransfer][0];

								"""
								Now we load real ACLs
								If any, allow all and simplify output.
								If none or herited, only allow cluster members
								Else, allow cluster members + ACLS
								"""
								if "any" in self.zoneList[zone][4]:
									transferBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][4] and "herited" not in self.zoneList[zone][4]:
									for acl in self.zoneList[zone][4]:
										transferBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][5]:
									notifyBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][5] and "herited" not in self.zoneList[zone][5]:
									for acl in self.zoneList[zone][5]: 
										notifyBuf += "\t\t\"%s\";\n" % acl

								if "any" in self.zoneList[zone][7]:
									queryBuf = "\t\tany;\n"
								elif "none" not in self.zoneList[zone][7] and "herited" not in self.zoneList[zone][7]:
									for acl in self.zoneList[zone][7]: 
										queryBuf += "\t\t\"%s\";\n" % acl
								
								# And we write ACLs
								if len(transferBuf) > 0:
									tmpcfgbuffer += "\tallow-transfer {\n%s\t};\n" % transferBuf
								if len(notifyBuf) > 0:
									tmpcfgbuffer += "\tallow-notify {\n%s\t};\n" % notifyBuf
								if len(queryBuf) > 0:
									tmpcfgbuffer += "\tallow-query {\n%s\t\t127.0.0.1;\n\t\t::1;\n\t};\n" % queryBuf
								validZone = True
					# Zone in forward mode
					elif self.zoneList[zone][0] == 3:
						# Only if there is forwarders
						if len(self.zoneList[zone][3]) > 0:
							# If there is caches, forward is on cache
							if clusterCaches:
								if srvType == 3:
									tmpcfgbuffer += "\ttype forward;\n\tforwarders {\n"
									for fwd in self.zoneList[zone][3]:
										tmpcfgbuffer += "\t\t%s;\n" % fwd
									tmpcfgbuffer += "\t};\n"

									# No ACL in a forward zone

									validZone = True
							else:
								tmpcfgbuffer += "\ttype forward;\n\tforwarders {\n"
								for fwd in self.zoneList[zone][3]:
									tmpcfgbuffer += "\t\t%s;\n" % fwd

								tmpcfgbuffer += "\t};\n"

								# No ACL in a forward zone

								validZone = True
					
					if validZone == True:
						cfgbuffer += "%s};\n" % tmpcfgbuffer
						# Classic zone
						if self.zoneList[zone][0] == 1 and srvType == 1:
							"""
							Verify if zone file exists on master servers. If not exists create a basic file
							Not needed for slaves. Zonefile is created when transfer if not exists on slave servers.
							"""
							if ssh.isRemoteExists("%s/%s/%s" % (chrootpath,mzonepath,zone)) == False:
								# SOA record
								ttlRefresh = self.zoneList[zone][8]
								if ttlRefresh == 0:
									ttlRefresh = 3600
								ttlRetry = self.zoneList[zone][9]
								if ttlRetry == 0:
									ttlRetry = 180
								ttlExpire = self.zoneList[zone][10]
								if ttlExpire == 0:
									ttlExpire = 864000
								ttlMinimum = self.zoneList[zone][11]
								if ttlMinimum == 0:
									ttlMinimum = 3600

								zonefile = "$ORIGIN .\n$TTL 86400\n%s IN SOA %s. hostmaster.%s. (\n\t\t\t1\n\t\t\t%d\n\t\t\t%d\n\t\t\t%d\n\t\t\t%d )\n" % (zone,nsfqdn,zone,ttlRefresh,ttlRetry,ttlExpire,ttlMinimum)

								# If caches, NS are on caches
								if cacheList != None:
									for cache in cacheList:
										zonefile += "\t\t\tNS\t%s.\n" % self.serverList[cache][7]
								else:
									zonefile += "\t\t\tNS\t%s.\n" % nsfqdn
									if slaveList != None:
										for slave in slaveList:
											zonefile += "\t\t\tNS\t%s.\n" % self.serverList[slave][7]
								zonefile += "\n$ORIGIN %s.\n" % zone
								
								
								if ssh.isRemoteWritable("%s/%s/%s" % (chrootpath,mzonepath,zone)) == False:
									self.logError("Unable to write zonefile on server %s. Please ensure %s/%s/%s is writable !" % (addr,chrootpath,mzonepath,zone))
								else:
									ssh.sendCmd("echo '%s' > %s/%s/%s" % (zonefile,chrootpath,mzonepath,zone))
									ssh.sendCmd("echo 1 > /tmp/dnsrestart")
									self.logInfo("file for zone %s created on %s" % (zone,addr))
						elif self.zoneList[zone][0] == 1 and srvType == 2:
							"""
							Verify if zone file exists on slave servers.
							If not exists ask a service restart to load zone
							"""
							if ssh.isRemoteExists("%s/%s/%s" % (chrootpath,szonepath,zone)) == False:
								ssh.sendCmd("echo 1 > /tmp/dnsrestart")
								self.logInfo("file for zone %s inexistant on %s (slave), asking named restart" % (zone,addr))
								
			# check md5 trace to see if subnet file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (zeyenamedpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % cfgbuffer])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (cfgbuffer,zeyenamedpath))
				ssh.sendCmd("echo 1 > /tmp/dnsrestart")
				self.logInfo("configuration modified on %s" % addr)
			
			ssh.close()
		except Exception, e:
			self.logCritical("doConfigDNS: %s" % e)
		finally:
			self.decrThreadNb()

	def loadServerList(self,pgcursor):
		self.serverList = {}

		# Only load servers in clusters
		pgcursor.execute("SELECT addr,sshuser,sshpwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn,tsigtransfer,tsigupdate FROM z_eye_dns_servers WHERE addr IN (SELECT server FROM z_eye_dns_cluster_masters) OR addr IN (SELECT server FROM z_eye_dns_cluster_slaves) OR addr IN (SELECT server FROM z_eye_dns_cluster_caches)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# Only load if all required fields are populated
			if idx[1] != None and idx[2] != None and idx[4] != None and idx[5] != None and idx[6] != None and idx[7] != None and idx[8] != None:
				self.serverList[idx[0]] = (idx[1],idx[2],idx[3],idx[4],idx[5],idx[6],idx[7],idx[8],idx[9],idx[10])

	def loadClusterList(self,pgcursor):
		self.clusterList = {}

		# We only load DNS cluster attached to a zone
		pgcursor.execute("SELECT clustername,dnssec_enable,dnssec_validation FROM z_eye_dns_clusters WHERE clustername IN (SELECT clustername FROM z_eye_dns_zone_clusters)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			tmpMasters = []
			tmpSlaves = []
			tmpCaches = []
			tmpACLRecurse = []
			tmpACLTransfer = []
			tmpACLUpdate = []
			tmpACLNotify = []
			tmpACLQuery = []

			"""
			We load servers & ACL and verify if they exist in each cache variable
			"""

			pgcursor.execute("SELECT server FROM z_eye_dns_cluster_masters WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.serverList.keys():
					tmpMasters.append(idx2[0])

			pgcursor.execute("SELECT server FROM z_eye_dns_cluster_slaves WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.serverList.keys():
					tmpSlaves.append(idx2[0])

			pgcursor.execute("SELECT server FROM z_eye_dns_cluster_caches WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.serverList.keys():
					tmpCaches.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_recurse WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys() or idx2[0] == "none" or idx2[0] == "any":
					tmpACLRecurse.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_transfer WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys() or idx2[0] == "none" or idx2[0] == "any":
					tmpACLTransfer.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_update WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys() or idx2[0] == "none" or idx2[0] == "any":
					tmpACLUpdate.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_notify WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys() or idx2[0] == "none" or idx2[0] == "any":
					tmpACLNotify.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_query WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys() or idx2[0] == "none" or idx2[0] == "any":
					tmpACLQuery.append(idx2[0])

			self.clusterList[idx[0]] = (tmpMasters,tmpSlaves,tmpCaches,tmpACLRecurse,tmpACLTransfer,tmpACLUpdate,tmpACLNotify,tmpACLQuery,idx[1],idx[2])

	def loadACLList(self,pgcursor):
		self.aclList = {}

		pgcursor.execute("SELECT aclname FROM z_eye_dns_acls")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			tmpIPs = []
			tmpNetworks = []
			tmpACLs = []
			tmpTSIGs = []
			tmpDNSNames = []

			# Load ACL ip list
			pgcursor.execute("SELECT ip FROM z_eye_dns_acl_ip WHERE aclname = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpIPs.append(idx2[0])

			# Load ACL network list
			pgcursor.execute("SELECT z_eye_dns_acl_network.netid,z_eye_dhcp_subnet_v4_declared.netmask FROM z_eye_dns_acl_network,z_eye_dhcp_subnet_v4_declared WHERE z_eye_dns_acl_network.aclname = '%s' AND z_eye_dns_acl_network.netid = z_eye_dhcp_subnet_v4_declared.netid" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpNetworks.append("%s/%s" % (idx2[0],ZEyeUtil.getCIDR(idx2[1])))

			# Load ACL ACL list and verify if child ACL exists
			pgcursor.execute("SELECT aclchild FROM z_eye_dns_acl_acl WHERE aclname = '%s' AND aclchild IN (SELECT aclname FROM z_eye_dns_acls)" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpACLs.append(idx2[0])

			# Load ACL TSIG list and verify if TSIG key exists
			pgcursor.execute("SELECT tsig FROM z_eye_dns_acl_tsig WHERE aclname = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.tsigList.keys():
					tmpTSIGs.append(idx2[0])

			# Load ACL DNS list
			pgcursor.execute("SELECT dnsname FROM z_eye_dns_acl_dnsname WHERE aclname = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpDNSNames.append(idx2[0])

			self.aclList[idx[0]] = (tmpIPs,tmpNetworks,tmpACLs,tmpTSIGs,tmpDNSNames)

	def loadTSIGList(self,pgcursor):
		self.tsigList = {}

		pgcursor.execute("SELECT keyalias,keyid,keyalgo,keyvalue FROM z_eye_dns_tsig")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			self.tsigList[idx[0]] = (idx[1],idx[2],idx[3])

	def loadZoneList(self,pgcursor):
		self.zoneList = {}

		# We only load zones attached to clusters
		pgcursor.execute("SELECT zonename,zonetype,ttlrefresh,ttlretry,ttlexpire,ttlminimum FROM z_eye_dns_zones WHERE zonename IN (SELECT zonename FROM z_eye_dns_zone_clusters) AND zonetype IN (1,2,3)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			tmpForwarders = []
			tmpMasters = []
			tmpACLTransfer = []
			tmpACLUpdate = []
			tmpACLNotify = []
			tmpACLQuery = []
			tmpClusters = []

			# Loading clusters
			pgcursor.execute("SELECT clustername FROM z_eye_dns_zone_clusters WHERE zonename = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.clusterList.keys():
					tmpClusters.append(idx2[0])
			# Zonetype 1: classic, 2: Slave Only, 3: Forward Only
			if idx[1] == 2:
				# Loading master servers
				pgcursor.execute("SELECT zonemaster FROM z_eye_dns_zone_masters WHERE zonename = '%s'" % idx[0])
				pgres2 = pgcursor.fetchall()
				for idx2 in pgres2:
					tmpMasters.append(idx2[0])
			if idx[1] == 3:
				# Loading forward servers
				pgcursor.execute("SELECT zoneforwarder FROM z_eye_dns_zone_forwarders WHERE zonename = '%s'" % idx[0])
				pgres2 = pgcursor.fetchall()
				for idx2 in pgres2:
					tmpForwarders.append(idx2[0])
			
			if idx[1] == 1:
				# Loading transfer servers only in a master configuration
				pgcursor.execute("SELECT aclname FROM z_eye_dns_zone_allow_transfer WHERE zonename = '%s'" % idx[0])
				pgres2 = pgcursor.fetchall()
				for idx2 in pgres2:
					tmpACLTransfer.append(idx2[0])

			if idx[1] != 3:
				# Loading notify servers (only when server could be slave or master)
				pgcursor.execute("SELECT aclname FROM z_eye_dns_zone_allow_notify WHERE zonename = '%s'" % idx[0])
				pgres2 = pgcursor.fetchall()
				for idx2 in pgres2:
					tmpACLNotify.append(idx2[0])

			# Loading update servers
			pgcursor.execute("SELECT aclname FROM z_eye_dns_zone_allow_update WHERE zonename = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpACLUpdate.append(idx2[0])

			# Loading query servers
			pgcursor.execute("SELECT aclname FROM z_eye_dns_zone_allow_query WHERE zonename = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				tmpACLQuery.append(idx2[0])

			self.zoneList[idx[0]] = (idx[1],tmpClusters,tmpMasters,tmpForwarders,tmpACLTransfer,tmpACLNotify,tmpACLUpdate,tmpACLQuery,idx[2],idx[3],idx[4],idx[5])

class RecordCollector(ZEyeUtil.Thread):
	pgcursor = None
	serversZones = {}
	tsigList = {}
	serverList = {}

	def __init__(self):
		""" 5 min between two refresh """
		self.sleepingTimer = 5*60
		self.myName = "DNS Record Collector"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchCachingProcess()
			self.setRunning(False)

	def collectRecords(self,server,zone):
		self.incrThreadNb()

		try:
			pgsqlCon2 = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor2 = pgsqlCon2.cursor()

			pgcursor2.execute("DELETE FROM z_eye_dns_zone_record_cache WHERE zonename = '%s' AND server = '%s'" % (zone,server))

			kn = self.tsigList[self.serverList[server]][0]
			kr = dns.tsigkeyring.from_text({kn : self.tsigList[self.serverList[server]][2]})

			# Transfer zone
			qzone = dns.zone.from_xfr(dns.query.xfr(server,zone,keyring=kr))
			for rectype in ["A","AAAA","CNAME","TXT","SRV","PTR","NS","SOA","MX"]:
				for (name, ttl, rdata) in qzone.iterate_rdatas(rectype):
					pgcursor2.execute("INSERT INTO z_eye_dns_zone_record_cache (zonename,record,rectype,recval,ttl,server) VALUES ('%s','%s','%s','%s','%s','%s')" % (zone,name,rectype,rdata,ttl,server))

			pgsqlCon2.commit()
		except PgSQL.Error, e:
			self.logCritical(e)
		except DNSException, e:
			# If an exption occurs, it's possible it's a not allowed transfer
			self.logError("DNSException on zone '%s' on server '%s'. Please check DNS server logs, transfer seems to be forbidden or server is not accessible" % (zone,server))
		except Exception, e:
			self.logCritical("FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def launchCachingProcess(self):
		try:
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			self.pgcursor = pgsqlCon.cursor()
			self.loadServerList()
			self.loadServerZones()
			self.loadTSIGList()
			try:
				for server in self.serversZones:
					for zone in self.serversZones[server]:
						thread.start_new_thread(self.collectRecords,(server,zone))

				""" Wait 1 second to lock program, else if script is too fast,it exists without discovering"""
				time.sleep(1)
			except StandardError, e:
				self.logCritical(e)
				
		except PgSQL.Error, e:
			self.logCritical("Pgsql Error %s" % e)
			return
		except Exception, e:
			self.logCritical("FATAL %s" % e)
		finally:
			if pgsqlCon:
				pgsqlCon.close()
			# We must wait 1 sec, else threadCounter == 0 because of fast algo
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logDebug("waiting %d threads" % self.getThreadNb())
				time.sleep(1)

	def loadServerList(self):
		self.serverList = {}

		# Only load servers in clusters
		self.pgcursor.execute("SELECT addr,tsigtransfer FROM z_eye_dns_servers WHERE addr IN (SELECT server FROM z_eye_dns_cluster_masters) OR addr IN (SELECT server FROM z_eye_dns_cluster_slaves) OR addr IN (SELECT server FROM z_eye_dns_cluster_caches)")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			# Only load if all required fields are populated
			if idx[1] != None and idx[1] != "":
				self.serverList[idx[0]] = (idx[1])

	def loadServerZones(self):
		self.serversZones = {}
		# Load zones but not some special system zones
		self.pgcursor.execute("SELECT server,zonename FROM z_eye_dns_zone_cache WHERE zonename NOT IN ('.','localhost','127.in-addr.arpa','1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.ip6.arpa')")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.serversZones:
				self.serversZones[idx[0]] = []
			self.serversZones[idx[0]].append(idx[1])
			
	def loadTSIGList(self):
		self.tsigList = {}

		self.pgcursor.execute("SELECT keyalias,keyid,keyalgo,keyvalue FROM z_eye_dns_tsig")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			self.tsigList[idx[0]] = (idx[1],idx[2],idx[3])
			
