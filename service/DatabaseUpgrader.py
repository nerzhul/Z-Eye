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
	nextDBVersion = "1318"
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
				self.tryAddColumn("z_eye_dhcp_servers","dhcptype","integer NOT NULL DEFAULT '0'")
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
			if self.dbVersion == "1215":
				self.tryCreateTable("z_eye_dns_tsig","keyalias varchar(64) NOT NULL, keyid varchar(32) NOT NULL, keyalgo integer NOT NULL DEFAULT '0', keyvalue varchar(128) NOT NULL, PRIMARY KEY(keyalias)")
				self.setDBVersion("1216")
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);

	def do13Upgrade(self):
		try:
			if self.dbVersion < "1300":
				self.tryCreateTable("z_eye_dns_servers","addr varchar(16) NOT NULL, sshuser varchar(64) NOT NULL, sshpwd varchar(64),namedpath varchar(256),chrootpath varchar(256), PRIMARY KEY (addr)")
				self.tryDropTable("z_eye_server_list")
				self.setDBVersion("1300")
			if self.dbVersion == "1300":
				self.tryDropTable("z_eye_dns_zone_cache")
				self.tryCreateTable("z_eye_dns_zone_cache","zonename varchar(192) NOT NULL, zonetype int NOT NULL, server varchar(256) NOT NULL, PRIMARY KEY(zonename,server)")
				self.setDBVersion("1301")
			if self.dbVersion == "1301":
				self.tryAddColumn("z_eye_dns_zone_record_cache","ttl","int")
				self.setDBVersion("1302")
			if self.dbVersion == "1302":
				self.tryCreateTable("z_eye_dns_acls","aclname varchar(64) NOT NULL, description varchar(256) NOT NULL, PRIMARY KEY(aclname)")
				self.tryCreateTable("z_eye_dns_acl_ip","aclname varchar(64) NOT NULL, ip varchar(16) NOT NULL, PRIMARY KEY(aclname,ip)")
				self.tryCreateTable("z_eye_dns_acl_network","aclname varchar(64) NOT NULL, netid varchar(19) NOT NULL, PRIMARY KEY(aclname,netid)")
				self.tryCreateTable("z_eye_dns_acl_tsig","aclname varchar(64) NOT NULL, tsig varchar(64) NOT NULL, PRIMARY KEY(aclname,tsig)")
				self.tryCreateTable("z_eye_dns_acl_acl","aclname varchar(64) NOT NULL, aclchild varchar(64) NOT NULL, PRIMARY KEY(aclname,aclchild)")
				self.tryCreateTable("z_eye_dns_acl_dnsname","aclname varchar(64) NOT NULL, dnsname varchar(256) NOT NULL, PRIMARY KEY(aclname,dnsname)")
				self.setDBVersion("1303")
			if self.dbVersion == "1303":
				self.tryAddColumn("z_eye_dns_servers","mzonepath","varchar(128)")
				self.tryAddColumn("z_eye_dns_servers","szonepath","varchar(128)")
				self.setDBVersion("1304")
			if self.dbVersion == "1304":
				self.tryAddColumn("z_eye_dns_servers","zeyenamedpath","varchar(256)")
				self.setDBVersion("1305")
			if self.dbVersion == "1305":
				self.tryCreateTable("z_eye_dns_clusters","clustername varchar(64) NOT NULL, description varchar(256), PRIMARY KEY(clustername)")
				self.tryCreateTable("z_eye_dns_cluster_masters","clustername varchar(64) NOT NULL, server varchar(16) NOT NULL, PRIMARY KEY(clustername,server)")
				self.tryCreateTable("z_eye_dns_cluster_slaves","clustername varchar(64) NOT NULL, server varchar(16) NOT NULL, PRIMARY KEY(clustername,server)")
				self.tryCreateTable("z_eye_dns_cluster_caches","clustername varchar(64) NOT NULL, server varchar(16) NOT NULL, PRIMARY KEY(clustername,server)")
				self.tryCreateTable("z_eye_dns_cluster_allow_recurse","clustername varchar(64) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(clustername,aclname)")
				self.tryCreateTable("z_eye_dns_cluster_allow_transfer","clustername varchar(64) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(clustername,aclname)")
				self.tryCreateTable("z_eye_dns_cluster_allow_update","clustername varchar(64) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(clustername,aclname)")
				self.tryCreateTable("z_eye_dns_cluster_allow_query","clustername varchar(64) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(clustername,aclname)")
				self.tryCreateTable("z_eye_dns_cluster_allow_notify","clustername varchar(64) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(clustername,aclname)")
				self.setDBVersion("1306")
			if self.dbVersion == "1306":
				self.tryDropColumn("z_eye_users","ulevel")
				self.tryAddColumn("z_eye_users","failauthnb","int DEFAULT '0'")
				self.setDBVersion("1307")
			if self.dbVersion == "1307":
				self.tryCreateTable("z_eye_dns_zones","zonename varchar(256) NOT NULL, description varchar(256), zonetype int NOT NULL, PRIMARY KEY(zonename)")
				self.tryCreateTable("z_eye_dns_zone_forwarders","zonename varchar(256) NOT NULL, zoneforwarder varchar(16) NOT NULL, PRIMARY KEY(zonename,zoneforwarder)")
				self.tryCreateTable("z_eye_dns_zone_masters","zonename varchar(256) NOT NULL, zonemaster varchar(16) NOT NULL, PRIMARY KEY(zonename,zonemaster)")
				self.tryCreateTable("z_eye_dns_zone_allow_transfer","zonename varchar(256) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(zonename,aclname)")
				self.tryCreateTable("z_eye_dns_zone_allow_update","zonename varchar(256) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(zonename,aclname)")
				self.tryCreateTable("z_eye_dns_zone_allow_query","zonename varchar(256) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(zonename,aclname)")
				self.tryCreateTable("z_eye_dns_zone_allow_notify","zonename varchar(256) NOT NULL, aclname varchar(64) NOT NULL, PRIMARY KEY(zonename,aclname)")
				self.setDBVersion("1308")
			if self.dbVersion == "1308":
				self.tryCreateTable("z_eye_dns_zone_clusters","zonename varchar(256) NOT NULL, clustername varchar(64) NOT NULL, PRIMARY KEY(zonename,clustername)")
				self.setDBVersion("1309")
			if self.dbVersion == "1309":
				self.tryAddColumn("z_eye_dns_servers","nsfqdn","varchar(256) DEFAULT ''")
				self.setDBVersion("1310")
			if self.dbVersion == "1310":
				self.tryAddColumn("z_eye_dns_zones","ttlrefresh","int DEFAULT 0")
				self.tryAddColumn("z_eye_dns_zones","ttlretry","int DEFAULT 0")
				self.tryAddColumn("z_eye_dns_zones","ttlexpire","int DEFAULT 0")
				self.tryAddColumn("z_eye_dns_zones","ttlminimum","int DEFAULT 0")
				self.setDBVersion("1311")
			if self.dbVersion == "1311":
				self.tryAddColumn("z_eye_users","android_api_key","varchar(128) UNIQUE")
				self.setDBVersion("1312")
			if self.dbVersion == "1312":
				self.tryCreateTable("z_eye_user_settings_android","uid int NOT NULL, enable_monitor bool NOT NULL, PRIMARY KEY(uid)")
				self.setDBVersion("1313")
			if self.dbVersion == "1313":
				self.tryAddColumn("z_eye_dns_servers","tsigtransfer","varchar(64)")
				self.tryAddColumn("z_eye_dns_servers","tsigupdate","varchar(64)")
				self.setDBVersion("1314")
			if self.dbVersion == "1314":
				self.tryAddColumn("z_eye_users","lang","varchar(6) DEFAULT ''")
				self.tryAddColumn("z_eye_users","inactivity_timer","int DEFAULT '30'")
				self.setDBVersion("1315")
			if self.dbVersion == "1315":
				self.tryAlterColumn("z_eye_dhcp_subnet_v4_declared","mleasetime","SET DEFAULT '0'")
				self.tryAlterColumn("z_eye_dhcp_subnet_v4_declared","dleasetime","SET DEFAULT '0'")
				self.setDBVersion("1316")
			if self.dbVersion == "1316":
				self.rawRequest("update z_eye_dhcp_custom_option set optname = 'next-server', opttype = 'ip' where optname = 'tftp-server-name'")
				self.rawRequest("update z_eye_dhcp_option set optname = 'next-server' where optname = 'tftp-server-name'")
				self.setDBVersion("1317")
			if self.dbVersion == "1317":
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '$USER1$/check_dummy 0 \"Icinga started with $$(($EVENTSTARTTIME$-$PROCESSSTARTTIME$)) seconds delay | delay=$$(($EVENTSTARTTIME$-$PROCESSSTARTTIME$))\"' WHERE cmd = 'check_icinga_startup_delay'")
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\nHost: $HOSTNAME$\nState: $HOSTSTATE$\nAddress: $HOSTADDRESS$\nInfo: $HOSTOUTPUT$\n\nDate/Time: $LONGDATETIME$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Host Alert: $HOSTNAME$ is $HOSTSTATE$ **\" $CONTACTEMAIL$' WHERE cmd = 'notify-host-by-email'")
				self.dbVersion = "1318"
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

		# Upgrades for Z-Eye 1.3 series
		if self.dbVersion <= "1399":
			self.do13Upgrade()

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

	def tryDropColumn(self,tablename,columnname):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("ALTER TABLE %s DROP COLUMN %s" % (tablename,columnname))
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			# If column exists, maybe the database is already up-to-date
			if re.search("column \"%s\" of relation \"%s\" does not exists" % (columnname,tablename),"%s" % e):	
				return

			if self.pgsqlCon:
				self.pgsqlCon.close()
                        Logger.ZEyeLogger().write("DBUpgrade: PgSQL error %s" % e)
			print "PgSQL Error: %s" % e
                        sys.exit(1);
	def tryAlterColumn(self,tablename,columnname,attributes):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("ALTER TABLE %s ALTER COLUMN %s %s" % (tablename,columnname,attributes))
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			# If column exists, maybe the database is already up-to-date
			if re.search("column \"%s\" of relation \"%s\" does not exists" % (columnname,tablename),"%s" % e):	
				return

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


