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

from pyPgSQL import PgSQL
import datetime
import sys
import thread
import commands
import os
import time
import string
import smtplib
import re
import netdiscoCfg

from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from threading import Lock

tc_mutex = Lock()
threadCounter = 0

defaultSNMPRO = "public"

max_threads = 30

def zeye_log(text):
        logfile = open("/var/www/datas/logs/z_eye_collector.log","a")
        logfile.writelines("%s\n"  % text)
        logfile.close()

now = datetime.datetime.now()
print "[Z-Eye][DHCP-check-entries] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
zeye_log("[Z-Eye][DHCP-check-entries] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))
try:
	global threadCounter
	pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT ip,macaddr,hostname,netid FROM z_eye_dhcp_ip_cache WHERE distributed = 3 ORDER BY netid,ip")
	try:
		pgres = pgcursor.fetchall()
		counterr = 0
		contactlist = {}
		for idx in pgres:
			pgcursor.execute("SELECT maxage,enmon,contact FROM z_eye_dhcp_monitoring WHERE subnet = '%s'" % idx[3])
                	pgres2 = pgcursor.fetchone()
	                if pgres2:
        	                intval = pgres2[0]
                	        enmon = pgres2[1]
				contact = pgres2[2]
	                else:
        	                intval = 30
                	        enmon = 0
				contact = ""
			maxdate = now - datetime.timedelta(days=int(intval))
	                if enmon == 1 and intval > 0:
				pgcursor.execute("SELECT time_last FROM node WHERE mac = '%s' ORDER BY time_last DESC LIMIT 1" % idx[1])
				pgres2 = pgcursor.fetchone()
				if pgres2:
					regdate = pgres2[0]
					if regdate < maxdate:
						counterr += 1
						print "Vieil enregistrement %s - %s (%s), derniere fois %s" % (idx[0],idx[1],idx[2],regdate)
						if contact in contactlist:
							contactlist[contact]["msg"] += "Enregistrement %s - %s (%s) trop ancien. (Derniere observation: %s)\n" % (idx[0],idx[1],idx[2],regdate)
							contactlist[contact]["count"] += 1
						else:
							contactlist[contact] = {'subject': "[Z-Eye] Reservations DHCP en echec",
							'msg': "Recapitulatif des reservations inutiles\n\nEnregistrement %s - %s (%s) trop ancien. (Derniere observation: %s)\n" % (idx[0],idx[1],idx[2],regdate),
							'count': 1 }
		for key, value in contactlist.items():
			tmp = re.split('@', key)
			me = "z-eye@%s" % tmp[1]
			msg = MIMEMultipart('alternative')
			msg['Subject'] = "%s (%d)" % (value["subject"],value["count"])
			msg['From'] = me
			msg['To'] = key

			mtext = MIMEText("%s%s" % (value["msg"],"\n------------\nCordialement,\n\nZ-Eye"), 'plain')
			msg.attach(mtext)

			s = smtplib.SMTP('localhost')
			s.sendmail(me, key, msg.as_string())
			s.quit()
	except StandardError, e:
		print "[Z-Eye][DHCP-check-entries] Fatal Error: %s" % e
		zeye_log("[Z-Eye][DHCP-check-entries] Fatal Error: %s" % e)
		
except PgSQL.Error, e:
	print "[Z-Eye][DHCP-check-entries] Pgsql Error %s" % e
	zeye_log("[Z-Eye][DHCP-check-entries] Pgsql Error %s" % e)
	sys.exit(1);	

finally:

	if pgsqlCon:
		pgsqlCon.close()

	while threadCounter > 0:
		print "Waiting for %d threads..." % threadCounter
		time.sleep(1)

	totaltime = datetime.datetime.now() - now
	now = datetime.datetime.now()
	print "[Z-Eye][DHCP-check-entries] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime)
	zeye_log("[Z-Eye][DHCP-check-entries] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))

