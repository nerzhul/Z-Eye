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

"""
* Version nomenclature for DB is:
* <Z-Eye version><db minor version>
* Z-Eye version: the current Z-Eye major version except the dot
* db minor version: a incremental number from 00 to 99 defined by -current modification for next Z-Eye series 
"""

class ZEyeDBUpgrade():
	dbVersion = "0"
	nextDBVersion = "1215"
	pgsqlCon = None

	def checkAndDoUpgrade(self):
		self.dbVersion = self.getDBVersion()
		# if this version is minor than service, we must upgrade
		if self.dbVersion < self.nextDBVersion:
			self.doUpgrade()
		else:
			print "No upgrade required !"

		if(self.pgsqlCon):
			self.pgsqlCon.close()

	def do12Upgrade(self):
		try:
			# Special: version 1 is a inited database number
			if self.dbVersion == "1":
				self.tryCreateTable("z_eye_dhcp_cluster","clustername varchar(64), dhcpaddr varchar(128), primary key (clustername,dhcpaddr)")
				self.tryCreateTable("z_eye_dhcp_subnet_v4_declared","netid varchar(16), netmask varchar(16), vlanid int unique, subnet_short_name varchar(32), subnet_desc varchar(128), primary key (netid, netmask)")
				self.tryAddColumn("z_eye_dhcp_servers","alias","varchar(64)")
				self.tryAddColumn("z_eye_dhcp_servers","description","varchar(128)")
				self.tryCreateTable("z_eye_dhcp_subnet_cluster","clustername varchar(64) NOT NULL, subnet varchar(16) NOT NULL, PRIMARY KEY (clustername,subnet)")
				self.tryCreateTable("z_eye_dhcp_ip","ip varchar(16) NOT NULL, macaddr varchar(18), hostname varchar(128), reserv bool, comment varchar(512), PRIMARY KEY(ip)")
				self.setDBVersion("1200")
			if self.dbVersion == "1200":
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","router","varchar(16)")
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","dns1","varchar(16)")
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","dns2","varchar(16)")
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","domainname","varchar(128)")
				self.setDBVersion("1201")
			if self.dbVersion == "1201":
				self.tryCreateTable("z_eye_sessions","id varchar(255) NOT NULL, data text NOT NULL, timestamp int NOT NULL, PRIMARY KEY(id)")
				self.setDBVersion("1202")
			if self.dbVersion == "1202":
				self.tryAddColumn("z_eye_dhcp_servers","osname","varchar(64)")
				self.tryAddColumn("z_eye_dhcp_servers","dhcptype","integer NOT NULL")
				self.setDBVersion("1203")
			if self.dbVersion == "1203":
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","mleasetime","integer")
				self.tryAddColumn("z_eye_dhcp_subnet_v4_declared","dleasetime","integer")
				self.setDBVersion("1204")
			if self.dbVersion == "1204":
				self.tryAddColumn("device_port_vlan","vlantype","text")
				self.tryCreateTable("topology","dev1 inet not null, port1 text not null, dev2 inet not null, port2 text not null")
				self.tryAddColumn("device_port_ssid","bssid","macaddr")
				self.tryAddColumn("node","vlan","text default '0'")
				self.rawRequest("alter table node drop constraint node_pkey")
				self.rawRequest("alter table node add primary key (mac, switch, port, vlan)")
				self.tryAddColumn("node_wireless","ssid","text default ''")
				self.rawRequest("alter table node_wireless drop constraint node_wireless_pkey")
				self.rawRequest("alter table node_wireless add primary key (mac, ssid)")
				self.setDBVersion("1205")
			if self.dbVersion == "1205":
				self.tryCreateTable("z_eye_dhcp_custom_option","optname varchar(32) NOT NULL, optcode integer NOT NULL, opttype varchar(32) NOT NULL, PRIMARY KEY (optname)")
				self.tryCreateTable("z_eye_dhcp_option","optid integer, optname varchar(32) NOT NULL, optval varchar(512) NOT NULL, PRIMARY KEY (optid)")
				self.tryCreateTable("z_eye_dhcp_option_group","optgroup varchar(64), optid integer, PRIMARY KEY (optgroup, optid)")
				self.setDBVersion("1206")
			if self.dbVersion == "1206":
				self.tryDropTable("z_eye_dhcp_option")
				self.tryDropTable("z_eye_dhcp_option_group")
				self.tryCreateTable("z_eye_dhcp_option","optalias varchar(64) NOT NULL, optname varchar(32) NOT NULL, optval varchar(512) NOT NULL, PRIMARY KEY (optalias)")
				self.tryCreateTable("z_eye_dhcp_option_group","optgroup varchar(64) NOT NULL, optalias varchar(64) NOT NULL, PRIMARY KEY (optgroup, optalias)")
				self.setDBVersion("1207")
			if self.dbVersion == "1207":
				self.tryCreateTable("z_eye_dhcp_subnet_optgroups","netid varchar(16) NOT NULL, optgroup varchar(64) NOT NULL, PRIMARY KEY (netid, optgroup)")
				self.setDBVersion("1208")
			if self.dbVersion == "1208":
				self.tryAddColumn("z_eye_dhcp_custom_option","protectrm","bool DEFAULT 'f'")
				self.setDBVersion("1209")
			if self.dbVersion == "1209":
				self.rawRequest("INSERT INTO z_eye_dhcp_custom_option (optname,optcode,opttype,protectrm) VALUES ('time-offset','2','int32','t'),('routers','3','ip','t'),\
					('time-servers','4','ip','t'),('domain-name-servers','6','ip','t'),('log-servers','7','ip','t'),('lpr-servers','9','ip','t'),('host-name','12','text','t'),\
					('domain-name','15','text','t'),('broadcast-address','28','ip','t'),('ntp-servers','42','ip','t'),('tftp-server-name','66','text','t'),('bootfile-name','67','text','t')")
				self.setDBVersion("1210")
			if self.dbVersion == "1210":
				self.rawRequest("INSERT INTO z_eye_dhcp_custom_option (optname,optcode,opttype,protectrm) VALUES ('mobile-ip-home-agent','68','ip','t'),('smtp-server','69','ip','t'),\
					('pop-servers','70','ip','t'),('nntp-servers','71','ip','t'),('www-servers','72','ip','t'),('finger-servers','73','ip','t'),('irc-server','74','ip','t'),\
					('nis-domain','40','text','t'),('nis-servers','41','ip','t'),('root-path','17','text','t'),('extensions-path','18','text','t'),('streettalk-server','75','ip','t')")
				self.setDBVersion("1211")
			if self.dbVersion == "1211":
				self.tryCreateTable("z_eye_dhcp_ipv4_optgroups","ipaddr varchar(16) NOT NULL, optgroup varchar(64) NOT NULL, PRIMARY KEY (ipaddr, optgroup)")
				self.setDBVersion("1212")
			if self.dbVersion == "1212":
				self.tryCreateTable("z_eye_dhcp_cluster_options","clustername varchar(64) NOT NULL, clustermode integer default '0', PRIMARY KEY (clustername)")
				self.setDBVersion("1213")
			if self.dbVersion == "1213":
				self.tryAddColumn("z_eye_dhcp_cluster_options","master","varchar(128)")
				self.setDBVersion("1214")
			if self.dbVersion == "1214":
				self.tryCreateTable("z_eye_dhcp_subnet_range","subnet varchar(16) NOT NULL, rangestart varchar(16) NOT NULL, rangestop varchar(16) NOT NULL")
				self.setDBVersion("1215")
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def doUpgrade(self):
		print "DB Upgrade needed, we perform this upgrade for you..."
		Logger.ZEyeLogger().write("DBUpgrade is needed. Starting...")

		# here: the upgrading process hasn't been done
		if self.dbVersion == "0":
			self.initDBVersionTable()

		# Upgrades for Z-Eye 1.2 series 
		if self.dbVersion <= "1299":
			self.do12Upgrade()

		print "DB Upgrade done."
		Logger.ZEyeLogger().write("DBUpgrade Done.")

	def rawRequest(self,request):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("%s" % request)
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def tryAddColumn(self,tablename,columnname,attributes):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("ALTER TABLE %s ADD COLUMN %s %s" % (tablename,columnname,attributes))
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			# If column exists, maybe the database is already up-to-date
			if re.search("column \"%s\" of relation \"%s\" already exists" % (columnname,tablename),"%s" % e):	
				return

			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def tryDropTable(self,tablename):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("drop table %s" % tablename)
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			# If table does not exist, maybe the database is up-to-date
			if re.search("relation \"%s\" does not exist" % tablename,"%s" % e):	
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
			pgcursor.execute("grant all on %s to netdisco" % tablename)
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			# If table exists, maybe the database is up-to-date
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
		self.dbVersion = version

	def initDBVersionTable(self):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("CREATE TABLE z_eye_db_version (dbver int NOT NULL)")
			pgcursor.execute("INSERT INTO z_eye_db_version (dbver) VALUES ('1')")
			self.pgsqlCon.commit()
			self.dbVersion = "1"
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
				return "0"

			pgcursor.execute("SELECT dbver FROM z_eye_db_version")
			pgres = pgcursor.fetchone()
			return "%s" % pgres[0]
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "There is problem with your table z_eye_db_version: %s" % e
                        sys.exit(1);


