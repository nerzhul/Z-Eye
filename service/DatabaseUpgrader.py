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
import sys, re, logging

import zConfig, ZEyeUtil

"""
* Version nomenclature for DB is:
* <Z-Eye version><db minor version>
* Z-Eye version: the current Z-Eye major version except the dot
* db minor version: a incremental number from 00 to 99 defined by -current modification for next Z-Eye series 
"""

class ZEyeDBUpgrade():
	dbVersion = "0"
	nextDBVersion = "1408"
	pgsqlCon = None
	logger = None

	def checkAndDoUpgrade(self):
		self.logger = logging.getLogger("Z-Eye")
		self.dbVersion = self.getDBVersion()
		# if this version is minor than service, we must upgrade
		if self.dbVersion < self.nextDBVersion:
			self.doUpgrade()
		else:
			print "No upgrade required !"
			self.logger.info("No database upgrade required !")

		# Check some system informations and fix it
		self.fixDBConsistence()
		
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
			self.logger.critical("DBUpgrade: %s" % e)
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
				self.rawRequest("UPDATE z_eye_dhcp_custom_option set optname = 'next-server', opttype = 'ip' where optname = 'tftp-server-name'")
				self.rawRequest("UPDATE z_eye_dhcp_option set optname = 'next-server' where optname = 'tftp-server-name'")
				self.setDBVersion("1317")
			if self.dbVersion == "1317":
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '$USER1$/check_dummy 0 \"Icinga started with $$(($EVENTSTARTTIME$-$PROCESSSTARTTIME$)) seconds delay | delay=$$(($EVENTSTARTTIME$-$PROCESSSTARTTIME$))\"' WHERE cmd = 'check_icinga_startup_delay'")
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\nHost: $HOSTNAME$\nState: $HOSTSTATE$\nAddress: $HOSTADDRESS$\nInfo: $HOSTOUTPUT$\n\nDate/Time: $LONGDATETIME$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Host Alert: $HOSTNAME$ is $HOSTSTATE$ **\" $CONTACTEMAIL$' WHERE cmd = 'notify-host-by-email'")
				self.setDBVersion("1318")
			if self.dbVersion == "1318":
				self.rawRequest("DELETE FROM z_eye_dhcp_subnet_cache")
				self.tryAddColumn("z_eye_dhcp_subnet_cache","server","varchar(256)")
				self.rawRequest("alter table z_eye_dhcp_subnet_cache drop constraint z_eye_dhcp_subnet_cache_pkey")
				self.rawRequest("alter table z_eye_dhcp_subnet_cache add primary key (netid,netmask,server)")
				self.setDBVersion("1319")
			if self.dbVersion == "1319":
				self.tryCreateTable("z_eye_radius_user_expiration","radalias varchar(256) NOT NULL, username varchar(64) NOT NULL, expiration_date timestamp NOT NULL, start_date timestamp NOT NULL, name varchar(64), surname varchar(64), creator varchar(64) NOT NULL, creation_date timestamp NOT NULL, PRIMARY KEY(radalias,username)")
				self.setDBVersion("1320")
			if self.dbVersion == "1320":
				self.tryCreateTable("z_eye_dhcp_subnet_v6_declared","netid varchar(40), prefixlen varchar(3), vlanid int unique, PRIMARY KEY(netid,prefixlen)")
				self.setDBVersion("1321")
			if self.dbVersion == "1321":
				self.tryAddColumn("z_eye_dhcp_subnet_v6_declared","netidv4","varchar(16) unique")
				self.setDBVersion("1322")
			if self.dbVersion == "1322":
				self.tryAlterColumn("z_eye_dns_zone_record_cache","record","TYPE varchar(256)")
				self.setDBVersion("1323")
			if self.dbVersion == "1323":
				self.tryAlterColumn("z_eye_dns_zone_record_cache","recval","TYPE varchar(256)")
				self.setDBVersion("1324")
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
			self.logger.critical("DBUpgrade: %s" % e)
			print "PgSQL Error: %s" % e
			sys.exit(1);
			
	def do14Upgrade(self):
		try:
			if self.dbVersion < "1400":
				self.tryCreateTable("z_eye_switch_infos","device varchar(128) NOT NULL, building varchar(64) NOT NULL, PRIMARY KEY(device)")
				self.setDBVersion("1400")
			if self.dbVersion == "1400":
				self.tryAddColumn("z_eye_switch_infos","room","varchar(64)")
				self.setDBVersion("1401")
			if self.dbVersion == "1401":
				self.tryAddColumn("z_eye_dns_clusters","dnssec_enable","bool")
				self.tryAddColumn("z_eye_dns_clusters","dnssec_validation","bool")
				self.setDBVersion("1402")
			if self.dbVersion == "1402":
				self.tryAddColumn("z_eye_dhcp_ip","expiration_date","date")
				self.setDBVersion("1403")
			if self.dbVersion == "1403":
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\nHost: $HOSTNAME$\nState: $HOSTSTATE$\nAddress: $HOSTADDRESS$\nInfo: $HOSTOUTPUT$\n\nDate/Time: $LONGDATETIME$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Host Alert: $HOSTNAME$ is $HOSTSTATE$ **\" $CONTACTEMAIL$' WHERE name = 'notify-host-by-email'")
				self.rawRequest("UPDATE z_eye_icinga_commands set cmd = '/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\n\nService: $SERVICEDESC$\nHost: $ HOSTALIAS$\nAddress: $HOSTADDRESS$\nState: $SERVICESTATE$\n\nDate/Time: $LONGDATETIME$\n\nAdditional Info:\n\n$SERVICEOUTPUT$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Service Alert: $HOSTALIAS$/$SERVICEDESC$ is $SERVICESTATE$ **\" $CONTACTEMAIL$' WHERE name = 'notify-service-by-email'")
				self.setDBVersion("1404")
			if self.dbVersion == "1404":
				self.tryAddColumn("z_eye_icinga_commands","cmd_comment","text")
				self.tryAddColumn("z_eye_icinga_commands","syscmd","bool DEFAULT 'f'")
				self.rawRequest("UPDATE z_eye_icinga_commands set syscmd = 't' WHERE name IN ('notify-host-by-email','notify-service-by-email','process-host-perfdata','process-service-perfdata')")
				self.setDBVersion("1405")
			if self.dbVersion == "1405":
				self.tryCreateTable("z_eye_icinga_notif_strategy","name varchar(64) NOT NULL, alias varchar(64) NOT NULL, interval INT NOT NULL, period VARCHAR(64) NOT NULL, ev_updown boolean NOT NULL, ev_crit boolean NOT NULL, ev_warn boolean NOT NULL, ev_unavailable boolean NOT NULL, ev_flap boolean NOT NULL, ev_recovery boolean NOT NULL, ev_scheduled boolean NOT NULL, PRIMARY KEY(name)")
				self.setDBVersion("1406")
			if self.dbVersion == "1406":
				pgcursor = self.pgsqlCon.cursor()
				
				"""
				Verify if 24x7 timeperiod exists, we need it to migrate our strategies
				If not exist, create it
				"""
				pgcursor.execute("SELECT count(*) FROM z_eye_icinga_timeperiods WHERE name = '24x7'")
				pgres = pgcursor.fetchone()
				if pgres[0] == 0:
					self.rawRequest("INSERT INTO z_eye_icinga_timeperiods (name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume) VALUES ('24x7','24 Hours A Day, 7 Days A Week','0','0','0','0','0','0','0','0','0','0','0','0','0','0','23','59','23','59','23','59','23','59','23','59','23','59','23','59')")
					
				# Now create the default strategies
				pgcursor.execute("SELECT count(*) FROM z_eye_icinga_notif_strategy WHERE name = 'All'")
				pgres = pgcursor.fetchone()
				if pgres[0] == 0:
					self.rawRequest("INSERT INTO z_eye_icinga_notif_strategy (name,alias,interval,period,ev_updown,ev_crit,ev_warn,ev_unavailable,ev_flap,ev_recovery,ev_sheduled) VALUES ('All','Every time, every notification','0','24x7','t','t','t','t','t','t','t')")
					
				pgcursor.execute("SELECT count(*) FROM z_eye_icinga_notif_strategy WHERE name = 'Nothing'")
				pgres = pgcursor.fetchone()
				if pgres[0] == 0:
					self.rawRequest("INSERT INTO z_eye_icinga_notif_strategy (name,alias,interval,period,ev_updown,ev_crit,ev_warn,ev_unavailable,ev_flap,ev_recovery,ev_sheduled) VALUES ('Nothing','No alert','0','24x7','f','f','f','f','f','f','f')")
				
				# we add the strategy column to hosts
				self.tryAddColumn("z_eye_icinga_hosts","notif_strategy","varchar(64) NOT NULL DEFAULT ''")
				
				# Now we apply a patch to use the previous created strategies (BREAKUP some configurations)
				pgcursor.execute("SELECT name FROM z_eye_icinga_hosts WHERE hostoptd = 't' AND hostoptu = 't' AND hostoptr = 't' AND hostoptf = 't' AND hostopts = 't'")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_hosts SET notif_strategy = 'All' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				pgcursor.execute("SELECT name FROM z_eye_icinga_hosts WHERE (hostoptd = 'f' AND hostoptu = 'f' AND hostoptr = 'f' AND hostoptf = 'f' AND hostopts = 'f') OR notifen = 'f'")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_hosts SET notif_strategy = 'Nothing' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				pgcursor.execute("SELECT name FROM z_eye_icinga_hosts WHERE notif_strategy = ''")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_hosts SET notif_strategy = 'All' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				# we add the strategy column to services
				self.tryAddColumn("z_eye_icinga_services","notif_strategy","varchar(64) NOT NULL DEFAULT ''")
				
				# Now we apply a patch to use the previous created strategies (BREAKUP some configurations)
				pgcursor.execute("SELECT name FROM z_eye_icinga_services WHERE srvoptc = 't' AND srvoptw = 't' AND srvoptf = 't' AND srvopts = 't' AND srvoptu = 't'")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_services SET notif_strategy = 'All' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				pgcursor.execute("SELECT name FROM z_eye_icinga_services WHERE (srvoptc = 'f' AND srvoptw = 'f' AND srvoptf = 'f' AND srvopts = 'f' AND srvoptu = 'f') OR notifen = 'f'")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_services SET notif_strategy = 'Nothing' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				pgcursor.execute("SELECT name FROM z_eye_icinga_services WHERE notif_strategy = ''")
				pgres = pgcursor.fetchall()
				for idx in pgres:
					self.rawRequest("UPDATE z_eye_icinga_services SET notif_strategy = 'All' WHERE name = '%s'" % ZEyeUtil.addPgSlashes(idx[0]))
				
				self.setDBVersion("1407")
			if self.dbVersion == "1407":
				self.tryDropColumn("z_eye_icinga_services","notifperiod")
				self.tryDropColumn("z_eye_icinga_services","notifintval")
				self.tryDropColumn("z_eye_icinga_services","srvoptc")
				self.tryDropColumn("z_eye_icinga_services","srvoptw")
				self.tryDropColumn("z_eye_icinga_services","srvoptu")
				self.tryDropColumn("z_eye_icinga_services","srvoptf")
				self.tryDropColumn("z_eye_icinga_services","srvopts")
				self.tryDropColumn("z_eye_icinga_services","srvoptr")
				self.setDBVersion("1408")
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
			self.logger.critical("DBUpgrade: %s" % e)
			print "PgSQL Error: %s" % e
			sys.exit(1);

	def doUpgrade(self):
		print "DB Upgrade needed, we perform this upgrade for you..."
		self.logger.info("DB Upgrade is needed. Starting...")

		# here: the upgrading process hasn't been done
		if self.dbVersion == "0":
			self.initDBVersionTable()

		# Upgrades for Z-Eye 1.2 series 
		if self.dbVersion <= "1299":
			self.do12Upgrade()

		# Upgrades for Z-Eye 1.3 series
		if self.dbVersion <= "1399":
			self.do13Upgrade()
			
		# Upgrades for Z-Eye 1.4 series
		if self.dbVersion <= "1499":
			self.do14Upgrade()

		print "DB Upgrade done."
		self.logger.info("DB Upgrade Done.")

	def fixDBConsistence(self):
		consistenceError = False
		
		pgcursor = self.pgsqlCon.cursor()
		pgcursor.execute("SELECT count(*) FROM z_eye_icinga_commands WHERE name IN ('notify-host-by-email','notify-service-by-email','process-host-perfdata','process-service-perfdata')")
		pgres = pgcursor.fetchone()
		if pgres[0] != 4:
			consistenceError = True
			self.logger.debug("Fixing icinga system commands")
			self.rawRequest("DELETE FROM z_eye_icinga_commands WHERE name IN ('notify-host-by-email','notify-service-by-email','process-host-perfdata','process-service-perfdata')")
			self.rawRequest("INSERT INTO z_eye_icinga_commands VALUES ('notify-host-by-email','/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\nHost: $HOSTNAME$\nState: $HOSTSTATE$\nAddress: $HOSTADDRESS$\nInfo: $HOSTOUTPUT$\n\nDate/Time: $LONGDATETIME$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Host Alert: $HOSTNAME$ is $HOSTSTATE$ **\" $CONTACTEMAIL$','','t')")
			self.rawRequest("INSERT INTO z_eye_icinga_commands VALUES ('notify-service-by-email','/usr/bin/printf \"%b\" \"***** Icinga *****\n\nNotification Type: $NOTIFICATIONTYPE$\n\nService: $SERVICEDESC$\nHost: $ HOSTALIAS$\nAddress: $HOSTADDRESS$\nState: $SERVICESTATE$\n\nDate/Time: $LONGDATETIME$\n\nAdditional Info:\n\n$SERVICEOUTPUT$\n\" | /usr/bin/mail -s \"** $NOTIFICATIONTYPE$ Service Alert: $HOSTALIAS$/$SERVICEDESC$ is $SERVICESTATE$ **\" $CONTACTEMAIL$','','t')")
			# @TODO: add missing sensors
		
		if consistenceError == True:
			print "Some system DB entries have been fixed"
			self.logger.warn("Some system DB entries have been fixed")
		else:
			print "System DB entries are OK"
			self.logger.info("System DB entries are OK")
			

	def rawRequest(self,request):
		try:
			pgcursor = self.pgsqlCon.cursor()
			pgcursor.execute("%s" % request)
			self.pgsqlCon.commit()
		except PgSQL.Error, e:
			if self.pgsqlCon:
				self.pgsqlCon.close()
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
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
			self.logger.critical("DBUpgrade: %s" % e)
			print "PgSQL Error: %s" % e
			sys.exit(1);

	def getDBVersion(self):
		try:
			self.pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
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
			self.logger.critical("DBUpgrade: %s" % e)
			print "There is problem with your table z_eye_db_version: %s" % e
			sys.exit(1);


