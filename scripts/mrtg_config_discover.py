#! python
# -*- coding: utf-8 -*-

import _mysql
from pyPgSQL import libpq
import datetime
import sys

mysqlCon = None
pgsqlCon = None

mysqlHost = 'localhost'
mysqlUser = 'root'
mysqlPwd = 'root'
mysqlDb = 'fssmanager'

pgsqlHost = 'localhost'
pgsqlUser = 'netdisco'
pgsqlPwd = 'netdisco'
pgsqlDb = 'netdisco'

now = datetime.datetime.now()
print "[Z-Eye][mrtg-config-discover] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")

try:

	mysqlCon = _mysql.connect(mysqlHost,mysqlUser,mysqlPwd,mysqlDb);
	try:
		pgsqlCon = libpq.PQconnectdb("host=%s dbname=%s user=%s password=%s",pgsqlHost,pgsqlDb,pgsqlUser,pgsqlPwd)
		pgcursor = pgsqlCon.cursor()
		pgcursor.execute("SELECT ip,name FROM device")
		try:
			pgres = pgcursor.fetchall()
			for i in res:
				print "0: %s 1: %s" % (res[i][0],res[i][1])
		except StandardError, e:
			print "[Z-Eye][mrtg-config-discover] Pgsql Error %s" % e
		
	except libpq.Error, e:
		print "[Z-Eye][mrtg-config-discover] Pgsql Error %s" % e
		sys.exit(1);	
	
		
except _mysql.Error, e:

	print "[Z-Eye][mrtg-config-discover] Mysql Error %d: %s" % (e.args[0], e.args[1])
	sys.exit(1)

finally:

	if mysqlCon:
		con.close()
	
	totaltime = datetime.datetime.now() - now
	now = datetime.datetime.now()
	print "[Z-Eye][mrtg-config-discover] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime)
