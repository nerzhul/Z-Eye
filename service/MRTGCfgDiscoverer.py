# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
import datetime,os,re,sys,time,thread,threading

import netdiscoCfg

class ZEyeMRTGDiscoverer(threading.Thread):
	sleepingTimer = 0
	defaultSNMPRO = "public"
	startTime = 0

	def __init__(self):
		""" 30 mins between two discover """
		self.sleepingTimer = 30*60
		threading.Thread.__init__(self)

	def run(self):
		while True:
			self.launchCfgGenerator()
			time.sleep(self.sleepingTimer)

	def launchCfgGenerator(self):
		self.startTime = datetime.datetime.now();
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
						devcom = self.defaultSNMPRO
					thread.start_new_thread(self.fetchMRTGInfos,(devip,devname,devcom))
			except StandardError, e:
				return
				
		except PgSQL.Error, e:
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()
		totaltime = datetime.datetime.now()

	def fetchMRTGInfos(self,ip,devname,devcom):
		try:
			cmd = "cfgmaker %s@%s" % (devcom,ip)
			pipe = os.popen(cmd, 'r')
			text = pipe.read()
			pipe.close()
			text = re.sub("\/var\/www\/mrtg","/usr/local/www/z-eye/datas/rrd",text)
			cfgfile = open("/usr/local/www/z-eye/datas/mrtg-config/mrtg-%s.cfg" % devname,"w")
			cfgfile.writelines(text)
			cfgfile.close()
		except Exception, e:
			return
