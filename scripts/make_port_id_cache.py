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
import datetime
import sys
import thread
import commands
import os
import time
import string
from threading import Lock
import netdiscoCfg

tc_mutex = Lock()
threadCounter = 0

defaultSNMPRO = "public"

max_threads = 30

def zeye_log(text):
        logfile = open("/usr/local/www/z-eye/datas/logs/z_eye_collector.log","a")
        logfile.writelines("%s\n"  % text)
        logfile.close()

def fetchSNMPInfos(ip,devname,devcom,vendor):
	global threadCounter
	global defaultSNMPRO
	try:
		tc_mutex.acquire()
		threadCounter += 1
		tc_mutex.release()
		if vendor == "cisco":
			cmd = "snmpwalk -v 2c -c %s %s ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null | grep -ve unrouted" % (devcom,ip)
		elif vendor == "dell":
			cmd = "snmpwalk -v 2c -c %s %s ifName | grep -ve Stack | grep -ve Vlan | grep -ve Null | grep -ve unrouted" % (devcom,ip)
		pipe = os.popen('{ ' + cmd + '; }', 'r')
		text = pipe.read()
		pipe.close()
		pgsqlCon2 = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
		pgcursor2 = pgsqlCon2.cursor()
		stopSwIDSearch = 0
		pgcursor2.execute("DELETE FROM z_eye_port_id_cache WHERE device = '%s'" % devname)
		for line in string.split(text, "\n"):
			pdata = string.split(line, " ")
			if len(pdata) >= 4:
				""" get full name, with spaces """
				pname = ""
				for i in xrange(3,len(pdata)):
					pname += pdata[i]
					if i != len(pdata)-1:
						pname += " "
				""" get port id """
				pdata2 = string.split(pdata[0], ".")
				if len(pdata2) >= 2:
					pid = pdata2[1]
					swid = 0
					swpid = 0
					""" it's a cisco specific mib. We must found another mean for other constructors """
					if stopSwIDSearch == 0 and vendor == "cisco":
						cmd = "snmpwalk -v 2c -c %s %s 1.3.6.1.4.1.9.5.1.4.1.1.11 | grep %s" % (devcom,ip,pid)
						pipe2 = os.popen('{ ' + cmd + '; }', 'r')
						text2 = pipe2.read()
						pipe2.close()
						piddata = string.split(text2, " ")
						if len(piddata) == 4:
							piddata = string.split(piddata[0], ".")
							if len(piddata) > 1:
								swid = piddata[len(piddata)-2]
								swpid = piddata[len(piddata)-1]
						elif len(piddata) >= 3 and piddata[2] == "No":
							stopSwIDSearch = 1
					""" must be there for no switch/switchport id """
					pgcursor2.execute("INSERT INTO z_eye_port_id_cache (device,portname,pid,switchid,switchportid) VALUES ('%s','%s','%s','%s','%s')" % (devname,pname,pid,swid,swpid))
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()

		pgsqlCon2.commit()
		pgcursor2.close()
		pgsqlCon2.close()
	except Exception, e:
		print "[FATAL] %s" % e
		zeye_log("[FATAL] %s" % e)
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()

now = datetime.datetime.now()
print "[Z-Eye][PortId-Caching] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
zeye_log("[Z-Eye][PortId-Caching] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))
try:
	global threadCounter
	pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT ip,name,vendor FROM device ORDER BY ip")
	try:
		pgres = pgcursor.fetchall()
		for idx in pgres:
			while threadCounter >= max_threads:
                                print "Waiting for %d threads..." % threadCounter
				time.sleep(1)

			pgcursor.execute("SELECT snmpro FROM z_eye_snmp_cache where device = '%s'" % idx[1])
			pgres2 = pgcursor.fetchone()
	
			devip = idx[0]
			devname = idx[1]
			vendor = idx[2]
			if pgres2:
				devcom = pgres2[0]
			else:
				devcom = defaultSNMPRO
			thread.start_new_thread(fetchSNMPInfos,(devip,devname,devcom,vendor))
		""" Wait 1 second to lock program, else if script is too fast,it exists without discovering"""
		time.sleep(1)
	except StandardError, e:
		print "[Z-Eye][PortId-Caching] Fatal Error: %s" % e
		zeye_log("[Z-Eye][PortId-Caching] Fatal Error: %s" % e)
		
except PgSQL.Error, e:
	print "[Z-Eye][PortId-Caching] Pgsql Error %s" % e
	zeye_log("[Z-Eye][PortId-Caching] Pgsql Error %s" % e)
	sys.exit(1);	

finally:

	if pgsqlCon:
		pgsqlCon.close()

	while threadCounter > 0:
		print "Waiting for %d threads..." % threadCounter
		time.sleep(1)

	totaltime = datetime.datetime.now() - now
	now = datetime.datetime.now()
	print "[Z-Eye][PortId-Caching] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime)
	zeye_log("[Z-Eye][PortId-Caching] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))

