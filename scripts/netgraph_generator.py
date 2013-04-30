#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2013 Lo\xc3\xafc BLOT, CNRS <http://www.unix-experience.fr/>
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
import re
from threading import Lock
import netdiscoCfg

log_header = "[Z-Eye][Netgraph-Generator]"

pgsqlCon = None
devlist = {}
devlistbyip = {}
portbuffer = {}

def zeye_log(text):
	logfile = open("/usr/local/www/z-eye/datas/logs/z_eye_collector.log","a")
	logfile.writelines("%s\n"  % text)
	logfile.close()

def load_devlist():
	global pgsqlCon
	global devlist,devlistbyip
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT name,model,ip FROM device")
	try:
		pgres = pgcursor.fetchall()
		for idx in pgres:
			devlist[idx[0]] = (idx[1],idx[2])
			devlistbyip[idx[2]] = (idx[1],idx[0])
		
	except PgSQL.Error, e:
        	print "%s Pgsql Error %s" % (log_header,e)
		zeye_log("%s Pgsql Error %s" % (log_header,e))
        	sys.exit(1);
	

def load_portbuffer():
	global pgsqlCon
	global portbuffer 
	pgcursor = pgsqlCon.cursor()
	pgcursor.execute("SELECT ip,port,speed,remote_id FROM device_port WHERE remote_id != '' ORDER BY ip,remote_id")
	try:
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if not portbuffer.has_key(idx[0]):
				portbuffer[idx[0]] = {}
			portbuffer[idx[0]][idx[1]] = (idx[2],idx[3])
		
	except PgSQL.Error, e:
        	print "%s Pgsql Error %s" % (log_header,e)
		zeye_log("%s Pgsql Error %s" % (log_header,e))
        	sys.exit(1);
	
def generate_graph(filename,options=[],size=""):
	global pgsqlCon,devlist,devlistbyip,portbuffer
	graphopts = "bgcolor=white, nodesep=1"
	if(len(size) > 0):
		graphopts += ", size=\"%s\"" % size
	if "NO-DIRECTION" in options:
		graphbuffer = "graph %s {\n graph [%s];\n node [label=\"\N\", color=white, fontcolor=black, fontname=lucon, shape=plaintext];\n edge [color=black];\n" % (re.sub("-","_",filename),graphopts);
	else:
		graphbuffer = "digraph %s {\n graph [%s];\n node [label=\"\N\", color=white, fontcolor=black, fontname=lucon, shape=plaintext];\n edge [color=black];\n" % (re.sub("-","_",filename),graphopts);


	""" Search each port in and out bw """
	outlink = {}
	for (devkey,dev) in portbuffer.items():
		devname = devlistbyip[devkey][1]
		for (pkey,devport) in dev.items():
			if "NO-WIFI" in options:
				if re.match("AIRAP",devlist[devname][0]):
					continue
				if re.match("AIRAP",devlistbyip[devkey][0]):
					continue
				outcharge = 0
				incharge = 0
				pgcursor = pgsqlCon.cursor()
				pgcursor.execute("SELECT pid FROM z_eye_port_id_cache WHERE device = '%s' AND portname = '%s'" % (devname,pkey))
				try:
					pid = pgcursor.fetchone()
					if pid != None:
						pid = pid[0]
						""" if PID there is a MRTG file to read, maybe """
						try:
							mrtgfile = open("/usr/local/www/z-eye/datas/rrd/%s_%s.log" % (devkey,pid))
							mrtgfilecontent = mrtgfile.readlines()
							for i in range(1,2):
								outputbw = 0
								res = re.split(" ",mrtgfilecontent[i])
								if len(res) == 5:
									if devport[0] == 0:
										inputbw = 0
										outputbw = 0
									else:
										inputbw = res[1]
										outputbw = res[2]
										maxbw = re.split(" ",devport[0])
										if len(maxbw) == 2:
											if maxbw[1] == "Gbit" or maxbw[1] == "Gbps":
												maxbw = float(maxbw[0]) * 1000000000
											elif maxbw[1] == "Mbit" or maxbw[1] == "Mbps":
												maxbw = float(maxbw[0]) * 1000000
											else:
												maxbw = float(maxbw[0])
										else:
											maxbw = maxbw[0]
										outcharge = float(outputbw) / float(maxbw)
										incharge = float(inputbw) / float(maxbw)
										if not outlink.has_key(devname):
											outlink[devname] = {}
										if not ("NO-DIRECTION" in options) or "NO-DIRECTION" in options and not outlink[devname].has_key(devport[1]):
											outlink[devname][devport[1]] = {"lock": 1, "chrg": outcharge}
										if not ("NO-DIRECTION" in options):
											if not outlink.has_key(devport[1]):
												outlink[devport[1]] = {}
											if not outlink[devport[1]].has_key(devname) or outlink[devport[1]][devname]["lock"] == 0:
												outlink[devport[1]][devname] = {"lock": 0, "chrg": incharge}
					 	except IOError, e:				
        						print "%s IOError: %s" % (log_header,e)
							zeye_log("%s IOError: %s" % (log_header,e))
				except PgSQL.Error, e:
        				print "%s Pgsql Error %s" % (log_header,e)
					zeye_log("%s Pgsql Error %s" % (log_header,e))
        				sys.exit(1);
	print outlink
	""" Generate .dot file """
	for (devsrc,devsrcdata) in outlink:
		for (devdst,devdstdata) in devsrcdata:
			outcharge = outlink[devsrc][devdst]["chrg"]
			penwidth = "1.0"
			pencolor = "black"

			if outcharge > 0 and outcharge < 10:
				penwidth = "1.0"
				pencolor = "#8C00FF"
			elif outcharge < 25:
				penwidth = "1.5"
				pencolor = "#2020FF"
			elif outcharge < 40:
				penwidth = "2.0"
				pencolor = "#00C0FF"
			elif outcharge < 55:
				penwidth = "3.0"
				pencolor = "#00F000"
			elif outcharge < 70:
				penwidth = "4.0"
				pencolor = "#F0F000"
			elif outcharge < 85:
				penwidth = "4.5"
				pencolor = "#FFC000"
			else:
				penwidth = "5.0"
				pencolor = "red"

			if "NO-DIRECTION" in options:
				graphbuffer += re.sub("[.-]","_",devdst) + " -- " + re.sub("[.-]","_",devsrc) + " [color=\"" + pencolor + "\", penwidth=" + penwidth + "];\n"
			else:
				graphbuffer += re.sub("[.-]","_",devdst) + " -> " + re.sub("[.-]","_",devsrc) + " [color=\"" + pencolor + "\", penwidth=" + penwidth + "];\n"


	graphbuffer += "}\n"

	dotfile = open("/usr/local/www/z-eye/datas/weathermap/%s.dot" % filename,"w+")
	dotfile.write(graphbuffer)
	dotfile.close()

	os.system("/usr/local/bin/circo -Tsvg /usr/local/www/z-eye/datas/weathermap/%s.dot -o /usr/local/www/z-eye/datas/weathermap/%s.svg" % (filename,filename));

now = datetime.datetime.now()
print "%s Start at: %s" % (log_header,now.strftime("%Y-%m-%d %H:%M"))
zeye_log("%s Start at: %s" % (log_header,now.strftime("%Y-%m-%d %H:%M")))
try:
	global pgsqlCon

	pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
	pgcursor = pgsqlCon.cursor()

	opts = ["NO-WIFI"]
	tinysize = "22,14"

	load_devlist()
	load_portbuffer()

	generate_graph("main")
	generate_graph("main-tiny",[],tinysize)
	generate_graph("main-nowifi",opts)
	generate_graph("main-nowifi-tiny",opts,tinysize)

except PgSQL.Error, e:
	print "%s Pgsql Error %s" % (log_header,e)
	zeye_log("%s Pgsql Error %s" % (log_header,e))
	sys.exit(1);	
finally:
	if pgsqlCon:
		pgsqlCon.close()

	totaltime = datetime.datetime.now() - now
	now = datetime.datetime.now()
	print "%s End at: %s (Total time %s)" % (log_header, now.strftime("%Y-%m-%d %H:%M"), totaltime)
	zeye_log("%s End at: %s (Total time %s)" % (log_header, now.strftime("%Y-%m-%d %H:%M"), totaltime))
