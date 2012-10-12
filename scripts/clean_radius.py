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

import MySQLdb
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

import netdiscoCfg

max_threads = 30

def zeye_log(text):
        logfile = open("/var/www/datas/logs/z_eye_radius_cleaner.log","a")
	print "%s\n"  % text
        logfile.writelines("%s\n"  % text)
        logfile.close()

def cleanRadius(dbhost,dbport,dbname):
	global threadCounter
	try:
		tc_mutex.acquire()
                threadCounter += 1
                tc_mutex.release()
		pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
        	pgcursor = pgsqlCon.cursor()
		pgcursor.execute("SELECT login,pwd FROM z_eye_radius_db_list where addr='%s' and port='%s' and dbname='%s'" % (dbhost,dbport,dbname))
        	pgres2 = pgcursor.fetchone()
		if(pgres2):
			try:
				mysqlconn = MySQLdb.connect(host=dbhost,user=pgres2[0],passwd=pgres2[1],port=dbport,db=dbname)
				zeye_log("[Z-Eye][Radius-Cleaner] Connect to MySQL DB %s@%s:%s (user %s)" % (dbname,dbhost,dbport,pgres2[0]))
				mysqlcur = mysqlconn.cursor()
				mysqlcur.execute("SELECT username from z_eye_radusers WHERE expiration < NOW()")
				mysqlres = mysqlcur.fetchall()
				for idx in mysqlres:
					mysqlcur.execute("DELETE FROM radcheck WHERE username = '%s'" % idx[0])
					mysqlcur.execute("DELETE FROM radreply WHERE username = '%s'" % idx[0])
					mysqlcur.execute("DELETE FROM radusergroup WHERE username = '%s'" % idx[0])
				mysqlconn.commit()
				mysqlcur.close()
				mysqlconn.close()
			except MySQLdb.Error, e:
				zeye_log("[Z-Eye][Radius-Cleaner] MySQL Error %s" % e)
			        sys.exit(1)
		tc_mutex.acquire()
                threadCounter = threadCounter - 1
                tc_mutex.release()
                pgsqlCon.close()
	except PgSQL.Error, e:
	        zeye_log("[Z-Eye][Radius-Cleaner] Pgsql Error %s" % e)
		tc_mutex.acquire()
                threadCounter = threadCounter - 1
                tc_mutex.release()
       		sys.exit(1)
now = datetime.datetime.now()
zeye_log("[Z-Eye][Radius-Cleaner] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))
try:
        global threadCounter
        pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
	pgcursor = pgsqlCon.cursor()
        pgcursor.execute("SELECT addr,port,dbname FROM z_eye_radius_options GROUP BY addr,port,dbname")
        try:
                pgres = pgcursor.fetchall()
                for idx in pgres:
                        while threadCounter >= max_threads:
                                print "Waiting for %d threads..." % threadCounter
                                time.sleep(1)

                        thread.start_new_thread(cleanRadius,(idx[0],idx[1],idx[2]))
        except StandardError, e:
                zeye_log("[Z-Eye][Radius-Cleaner] Fatal Error: %s" % e)

except PgSQL.Error, e:
        zeye_log("[Z-Eye][Radius-Cleaner] Pgsql Error %s" % e)
        sys.exit(1);
finally:

        if pgsqlCon:
                pgsqlCon.close()

        while threadCounter > 0:
                print "Waiting for %d threads..." % threadCounter
                time.sleep(1)

        totaltime = datetime.datetime.now() - now
        now = datetime.datetime.now()
        zeye_log("[Z-Eye][Radius-Cleaner] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))
