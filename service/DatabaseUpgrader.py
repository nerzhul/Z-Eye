#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2011-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
import sys,re

import Logger
import netdiscoCfg

class ZEyeDBUpgrade():
	dbVersion = 0
	nextDBVersion = 1200 
	pgsqlCon = None

	def checkAndDoUpgrade(self):
		self.dbVersion = self.getDBVersion()
		if self.dbVersion < self.nextDBVersion:
			self.doUpgrade()

		if(self.pgsqlCon):
			self.pgsqlCon.close()

	def do12Upgrade(self):
		try:
			if self.dbVersion == 1:
				self.tryCreateTable("z_eye_dhcp_cluster","clustername varchar(64), dhcpaddr varchar(128), primary key (clustername,dhcpaddr)")
				self.tryCreateTable("z_eye_dhcp_subnet_v4_declared","netid varchar(16), netmask varchar(16), vlanid int unique, subnet_short_name varchar(32), subnet_desc varchar(128), primary key (netid, netmask)")
				self.tryAddColumn("z_eye_dhcp_servers","alias","varchar(64)")
				self.tryAddColumn("z_eye_dhcp_servers","description","varchar(128)")
				self.tryCreateTable("z_eye_dhcp_subnet_cluster","clustername varchar(64) NOT NULL, subnet varchar(16) NOT NULL, PRIMARY KEY (clustername,subnet)")
				self.tryCreateTable("z_eye_dhcp_ip","ip varchar(16) NOT NULL, macaddr varchar(18), hostname varchar(128), reserv bool, comment varchar(512), PRIMARY KEY(ip)")
				self.setDBVersion("1200")
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def doUpgrade(self):
		print "DB Upgrade needed, we perform this upgrade for you..."
		Logger.ZEyeLogger().write("DBUpgrade is needed. Starting...")
		if self.dbVersion == 0:
			self.initDBVersionTable()

		if self.dbVersion <= 1299:
			self.do12Upgrade()

		print "DB Upgrade done."
		Logger.ZEyeLogger().write("DBUpgrade Done.")

	def tryAddColumn(self,tablename,columnname,attributes):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("ALTER TABLE %s ADD COLUMN %s %s" % (tablename,columnname,attributes))
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			if re.search("column \"%s\" of relation \"%s\" already exists" % (columnname,tablename),"%s" % e):	
				return

			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def tryCreateTable(self,tablename,attributes):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("create table %s (%s)" % (tablename,attributes))
			pgcursor.execute("grant all on %s to netdisco")
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			if re.search("relation \"%s\" already exists" % tablename,"%s" % e):	
				return

			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def setDBVersion(self,version):
		pgcursor = self.pgsqlCon.cursor()
		pgcursor.execute("DELETE FROM z_eye_db_version")
		pgcursor.execute("INSERT INTO z_eye_db_version (dbver) VALUES ('%s')" % version)
		self.pgsqlCon.commit()

	def initDBVersionTable(self):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("CREATE TABLE z_eye_db_version (dbver int NOT NULL)")
			pgcursor.execute("INSERT INTO z_eye_db_version (dbver) VALUES ('1')")
			self.pgsqlCon.commit()
			self.dbVersion = 1
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def getDBVersion(self):
		try:
			self.pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("SELECT count(*) FROM pg_tables WHERE tablename='z_eye_db_version'")
			pgres = pgcursor.fetchone()
			if pgres[0] == 0:
				return 0

			pgcursor.execute("SELECT dbver FROM z_eye_db_version")
			pgres = pgcursor.fetchone()
			return pgres[0]
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "There is problem with your table z_eye_db_version: %s" % e
                        sys.exit(1);


