#! /usr/bin/python
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

import MySQLdb
from pyPgSQL import PgSQL
import datetime
import sys
import commands
import os
import time
import string
import socket
import struct
import ipaddr
import netdiscoCfg

critdetect = 0
warndetect = 0

def zeye_log(text):
        logfile = open("/usr/local/www/z-eye/datas/logs/z_eye_dhcp_lease_check.log","a")
        logfile.writelines("%s\n" % text)
        logfile.close()

now = datetime.datetime.now()
zeye_log("[Z-Eye][DHCP-lease-check] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))
try:
        global threadCounter
        pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
        pgcursor = pgsqlCon.cursor()
        pgcursor.execute("SELECT subnet,warnuse,crituse FROM z_eye_dhcp_monitoring WHERE enmon = 1")
        try:
                pgres = pgcursor.fetchall()
                for idx in pgres:
			subnet = idx[0]
			wuse = idx[1]
			cuse = idx[2]
			pgcursor.execute("SELECT netmask FROM z_eye_dhcp_subnet_cache WHERE netid = '%s'" % subnet)
			netmask = pgcursor.fetchone()[0]
			pgcursor.execute("SELECT COUNT(ip) FROM z_eye_dhcp_ip_cache WHERE netid = '%s' AND distributed = '2'" % subnet)
			ipcount = pgcursor.fetchone()[0]
			maxips = len(list(ipaddr.IPv4Network("%s/%s" % (subnet,netmask)))) - 2
			if float(ipcount)/float(maxips) >= cuse:
				print "Le subnet %s/%s est utilisé à plus de %f%" % (subnet,netmask,float(ipcount)/float(maxips))
				critdetect = 1
			elif float(ipcount)/float(maxips) >= wuse:
				print "Le subnet %s/%s est utilisé à plus de %f %f%" % (subnet,netmask,float(ipcount)/float(maxips))
				warndetect = 1
        except StandardError, e:
                zeye_log("[Z-Eye][DHCP-lease-check] Fatal Error: %s" % e)
		sys.exit(2)
		

except PgSQL.Error, e:
        zeye_log("[Z-Eye][DHCP-lease-check] Pgsql Error %s" % e)
        sys.exit(2);
finally:

        if pgsqlCon:
                pgsqlCon.close()

        totaltime = datetime.datetime.now() - now
        now = datetime.datetime.now()
        zeye_log("[Z-Eye][DHCP-lease_check] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))

	if critdetect != 0:
		sys.exit(2)
	if warndetect != 0:
		sys.exit(1)

	print "Aucun subnet saturé"
	sys.exit(0)
