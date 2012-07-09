#! python
# -*- coding: utf-8 -*-

import MySQLdb as mdb
from pyPgSQL import PgSQL
import datetime
import sys
import thread
import commands
import os
import re
import time

threadCounter = 0

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

pgsqlHost = '127.0.0.1'
pgsqlUser = 'netdisco'
pgsqlPwd = 'dbpassword'
pgsqlDb = 'netdisco'

now = datetime.datetime.now()
print "[Z-Eye][mrtg-config-discover] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
try:
	pgsqlCon = PgSQL.connect(host=pgsqlHost,user=pgsqlUser,password=pgsqlPwd,database=pgsqlDb)
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
		
except PgSQL.Error, e:
	print "[Z-Eye][mrtg-config-discover] Pgsql Error %s" % e
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
