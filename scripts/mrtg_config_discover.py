#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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

import MySQLdb as mdb
from pyPgSQL import PgSQL
import datetime
import sys
import thread
import commands
import os
import re
import time
import netdiscoCfg

threadCounter = 0

def zeye_log(text):
	logfile = open("/var/www/datas/logs/z_eye_collector.log","a")
	logfile.writelines("%s\n"  % text)
	logfile.close()

def fetchMRTGInfos(ip,devname,devcom):
	global threadCounter
	try:
		threadCounter = threadCounter + 1
		cmd = "cfgmaker %s@%s" % (devcom,ip)
                pipe = os.popen('{ ' + cmd + '; }', 'r')
                text = pipe.read()
                pipe.close()
                text = re.sub("\/var\/www\/mrtg","/var/www/datas/rrd",text)
                cfgfile = open("/var/www/datas/mrtg-config/mrtg-%s.cfg" % devname,"w")
                cfgfile.writelines(text)
                cfgfile.close()
		threadCounter = threadCounter - 1
	except Exception, e:
		print "[FATAL] %s" % e
		threadCounter = threadCounter - 1

pgsqlCon = None

defaultSNMPRO = 'IOTA'

now = datetime.datetime.now()
print "[Z-Eye][mrtg-config-discover] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
zeye_log("[Z-Eye][mrtg-config-discover] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))
try:
	pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT ip,name FROM device ORDER BY ip")
	try:
		pgres = pgcursor.fetchall()
		for idx in pgres:
			pgcursor2 = pgsqlCon.cursor()
			pgcursor2.execute("SELECT snmpro FROM z_eye_snmp_cache where device = '%s'" % idx[1])
			pgres2 = pgcursor2.fetchone()
	
			devip = idx[0]
			devname = idx[1]
			if pgres2:
				devcom = pgres2[0]
			else:
				devcom = defaultSNMPRO
			thread.start_new_thread(fetchMRTGInfos,(devip,devname,devcom))
	except StandardError, e:
		print "[Z-Eye][mrtg-config-discover] Fatal Error: %s" % e
		zeye_log("[Z-Eye][mrtg-config-discover] Fatal Error: %s" % e)
		
except PgSQL.Error, e:
	print "[Z-Eye][mrtg-config-discover] Pgsql Error %s" % e
	zeye_log("[Z-Eye][mrtg-config-discover] Pgsql Error %s" % e)
	sys.exit(1);	

finally:

	if pgsqlCon:
		pgsqlCon.close()

	while threadCounter > 0:
		print "Thread Number %d" % threadCounter
		time.sleep(1)

	totaltime = datetime.datetime.now() - now
	now = datetime.datetime.now()
	print "[Z-Eye][mrtg-config-discover] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime)
	zeye_log("[Z-Eye][mrtg-config-discover] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))
