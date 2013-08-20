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

from pyPgSQL import PgSQL
import datetime, sys, thread, subprocess, string, time, commands, threading
from threading import Lock

import dns.query
import dns.zone
from dns.exception import DNSException

import Logger
import netdiscoCfg
import ZEyeUtil
from SSHBroker import ZEyeSSHBroker

class DNSManager(threading.Thread):
	sleepingTimer = 0
	startTime = 0
	threadCounter = 0
	tc_mutex = Lock()
	serverList = {}
	clusterList = {}
	tsigList = {}
	aclList = {}
	zoneList = {}

	def __init__(self):
		""" 1 min between two DNS updates """
		self.sleepingTimer = 60
		threading.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("DNS Manager launched")
		while True:
			self.launchDNSManagement()
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

	def launchDNSManagement(self):
		Logger.ZEyeLogger().write("DNS Management task started")
		starttime = datetime.datetime.now()
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
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

					thread.start_new_thread(self.doConfigDNS,(server,self.serverList[server][0],self.serverList[server][1],self.serverList[server][2],self.serverList[server][3],self.serverList[server][4],self.serverList[server][5],self.serverList[server][6],self.serverList[server][7]))
		except Exception, e:
			Logger.ZEyeLogger().write("DNS Manager: FATAL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			time.sleep(1)

		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("DNS Management task done (time: %s)" % totaltime)

	def doConfigDNS(self,addr,user,pwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn):
		Logger.ZEyeLogger().write("DNS Manager: server cfg %s" % addr)
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
				Logger.ZEyeLogger().write("DNS Manager: %s OS (on %s) is not supported" % (remoteOs,addr))
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
				Logger.ZEyeLogger().write("DNS Manager: %s (on %s) is not writable" % (zeyenamedpath,addr))
				self.decrThreadNb()
				return

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

			# check md5 trace to see if subnet file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (zeyenamedpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % cfgbuffer])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (cfgbuffer,zeyenamedpath))
				ssh.sendCmd("echo 1 > /tmp/dnsrestart")
				Logger.ZEyeLogger().write("DNSManager: configuration modified on %s" % addr)

			ssh.close()

		except Exception, e:
			Logger.ZEyeLogger().write("DNS Manager: FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def loadServerList(self,pgcursor):
		self.serverList = {}

		# Only load servers in clusters
		pgcursor.execute("SELECT addr,sshuser,sshpwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn FROM z_eye_dns_servers WHERE addr IN (SELECT server FROM z_eye_dns_cluster_masters) OR addr IN (SELECT server FROM z_eye_dns_cluster_slaves) OR addr IN (SELECT server FROM z_eye_dns_cluster_caches)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# Only load if all required fields are populated
			if idx[1] != None and idx[2] != None and idx[4] != None and idx[5] != None and idx[6] != None and idx[7] != None and idx[8] != None:
				self.serverList[idx[0]] = (idx[1],idx[2],idx[3],idx[4],idx[5],idx[6],idx[7],idx[8])

	def loadClusterList(self,pgcursor):
		self.clusterList = {}

		# We only load DNS cluster attached to a zone
		pgcursor.execute("SELECT clustername FROM z_eye_dns_clusters WHERE clustername IN (SELECT clustername FROM z_eye_dns_zone_clusters)")
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
				if idx2[0] in self.aclList.keys():
					tmpACLRecurse.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_transfer WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys():
					tmpACLTransfer.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_update WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys():
					tmpACLUpdate.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_notify WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys():
					tmpACLNotify.append(idx2[0])

			pgcursor.execute("SELECT aclname FROM z_eye_dns_cluster_allow_query WHERE clustername = '%s'" % idx[0])
			pgres2 = pgcursor.fetchall()
			for idx2 in pgres2:
				if idx2[0] in self.aclList.keys():
					tmpACLQuery.append(idx2[0])

			self.clusterList[idx[0]] = (tmpMasters,tmpSlaves,tmpCaches,tmpACLRecurse,tmpACLTransfer,tmpACLUpdate,tmpACLNotify,tmpACLQuery)

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
				tmpIPs.append(idx2[0])


	def loadTSIGList(self,pgcursor):
		self.tsigList = {}

		pgcursor.execute("SELECT keyalias,keyid,keyalgo,keyvalue FROM z_eye_dns_tsig")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			self.tsigList[idx[0]] = (idx[1],idx[2],idx[3])

	def loadZoneList(self,pgcursor):
		self.zoneList = {}

		# We only load zones attached to clusters
		pgcursor.execute("SELECT zonename,zonetype FROM z_eye_dns_zones WHERE zonename IN (SELECT zonename FROM z_eye_dns_zone_clusters) AND zonetype IN (1,2,3)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			tmpForwarders = []
			tmpMasters = []
			tmpACLTransfer = []
			tmpACLUpdate = []
			tmpACLNotify = []
			tmpACLQuery = []

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

			self.zoneList[idx[0]] = (idx[1],tmpMasters,tmpForwarders,tmpACLTransfer,tmpACLNotify,tmpACLUpdate,tmpACLQuery)



class RecordCollector(threading.Thread):
	tc_mutex = Lock()
	threadCounter = 0
	max_threads = 30
	pgcursor = None
	serversZones = {}

	def __init__(self):
                """ 5 min between two refresh """
                self.sleepingTimer = 5*60

                threading.Thread.__init__(self)


	def run(self):
		Logger.ZEyeLogger().write("DNS Record collector launched")
		while True:
			self.launchCachingProcess()
			time.sleep(self.sleepingTimer)

	def incrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter += 1
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

	def collectRecords(self,server,zone):
		self.incrThreadNb()

		try:
			pgsqlCon2 = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor2 = pgsqlCon2.cursor()

			pgcursor2.execute("DELETE FROM z_eye_dns_zone_record_cache WHERE zonename = '%s' AND server = '%s'" % (zone,server))

			# Transfer zone
			qzone = dns.zone.from_xfr(dns.query.xfr(server,zone))
			for rectype in ["A","AAAA","CNAME","TXT","SRV","PTR","NS","SOA","MX"]:
				for (name, ttl, rdata) in qzone.iterate_rdatas(rectype):
					pgcursor2.execute("INSERT INTO z_eye_dns_zone_record_cache (zonename,record,rectype,recval,ttl,server) VALUES ('%s','%s','%s','%s','%s','%s')" % (zone,name,rectype,rdata,ttl,server))

			pgsqlCon2.commit()
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("DNS-Record-Collector: Pgsql Error %s" % e)
		except DNSException, e:
			# If an exption occurs, it's possible it's a not allowed transfer
			Logger.ZEyeLogger().write("DNS-Record-Collector: DNSException on zone '%s' on server '%s'. Please check DNS server logs, transfer seems to be forbidden or server is not accessible" % (zone,server))
		except Exception, e:
			Logger.ZEyeLogger().write("DNS-Record-Collector: FATAL %s" % e)
		finally:
			self.decrThreadNb()

	def launchCachingProcess(self):
		starttime = datetime.datetime.now()
		Logger.ZEyeLogger().write("DNS Records collect started")
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			self.pgcursor = pgsqlCon.cursor()
			self.loadServersAndZones()
			try:
				for server in self.serversZones:
					for zone in self.serversZones[server]:
						thread.start_new_thread(self.collectRecords,(server,zone))

				""" Wait 1 second to lock program, else if script is too fast,it exists without discovering"""
				time.sleep(1)
			except StandardError, e:
				Logger.ZEyeLogger().write("DNS-Record-Collector: FATAL %s" % e)
				
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("DNS-Record-Collector: Pgsql Error %s" % e)
			return
		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, else threadCounter == 0 because of fast algo
		time.sleep(1)
		while self.getThreadNb() > 0:
			time.sleep(1)

		totaltime = datetime.datetime.now() - starttime 
		Logger.ZEyeLogger().write("DNS Records collect done (time: %s)" % totaltime)

	def loadServersAndZones(self):
		self.serversZones = {}
		# Load zones but not some special system zones
		self.pgcursor.execute("SELECT server,zonename FROM z_eye_dns_zone_cache WHERE zonename NOT IN ('.','localhost','127.in-addr.arpa','1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.ip6.arpa')")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.serversZones:
				self.serversZones[idx[0]] = []
			self.serversZones[idx[0]].append(idx[1])
