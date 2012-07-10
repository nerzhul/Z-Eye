#! python
# -*- coding: utf-8 -*-

from pyPgSQL import PgSQL
import datetime
import sys
import thread
import commands
import os
import time
import string
from threading import Lock

tc_mutex = Lock()
threadCounter = 0

defaultSNMPRO = "public"

pgsqlHost = '127.0.0.1'
pgsqlUser = 'netdisco'
pgsqlPwd = 'netdisco'
pgsqlDb = 'netdisco'

max_threads = 30

def fetchSNMPInfos(ip,devname,devcom):
	global threadCounter
	global defaultSNMPRO
	try:
		tc_mutex.acquire()
		threadCounter += 1
		tc_mutex.release()
		cmd = "snmpwalk -v 2c -c %s %s ifDescr | grep -ve Stack | grep -ve Vlan | grep -ve Null" % (devcom,ip)
		pipe = os.popen('{ ' + cmd + '; }', 'r')
		text = pipe.read()
		pipe.close()
		pgsqlCon = PgSQL.connect(host=pgsqlHost,user=pgsqlUser,password=pgsqlPwd,database=pgsqlDb)
		pgcursor = pgsqlCon.cursor()
		pgcursor.execute("DELETE FROM z_eye_port_id_cache WHERE device = '%s'" % devname)
		for line in string.split(text, "\n"):
			pdata = string.split(line, " ")
			if len(pdata) >= 4:
				pname = pdata[3]
				pdata2 = string.split(pdata[0], ".")
				if len(pdata2) >= 2:
					pid = pdata2[1]
					swid = 0
					swpid = 0
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
						pgcursor.execute("INSERT INTO z_eye_port_id_cache (device,portname,pid,switchid,switchportid) VALUES ('%s','%s','%s','%s','%s')" % (devname,pname,pid,swid,swpid))
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()
		pgsqlCon.close()
	except Exception, e:
		print "[FATAL] %s" % e
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()

now = datetime.datetime.now()
print "[Z-Eye][PortId-Caching] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
try:
	global threadCounter
	pgsqlCon = PgSQL.connect(host=pgsqlHost,user=pgsqlUser,password=pgsqlPwd,database=pgsqlDb)
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT ip,name FROM device ORDER BY ip")
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
			if pgres2:
				devcom = pgres2[0]
			else:
				devcom = defaultSNMPRO
			thread.start_new_thread(fetchSNMPInfos,(devip,devname,devcom))
	except StandardError, e:
		print "[Z-Eye][PortId-Caching] Fatal Error: %s" % e
		
except PgSQL.Error, e:
	print "[Z-Eye][PortId-Caching] Pgsql Error %s" % e
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
