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
		self.pgcursor.execute("SELECT server,zonename FROM z_eye_dns_zone_cache")
		pgres = self.pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.serversZones:
				self.serversZones[idx[0]] = []
			self.serversZones[idx[0]].append(idx[1])
