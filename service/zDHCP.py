# -*- Coding: utf-8 -*-
"""
* Copyright (C) 2010-2012 Loic BLOT, CNRS <http://www.unix-experience.fr/>
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
import datetime, re, sys, time, thread, subprocess
from ipcalc import Network

import ZEyeUtil, zConfig
from DatabaseManager import ZEyeSQLMgr
from SSHBroker import ZEyeSSHBroker

"""
* Please note this element support only ISC DHCP daemon at this time. BSD native daemon will be supported later
* Note: if you use IPManager and Z-Eye DHCP manager you need this line in your crontab:
*   if [ -r /tmp/dhcprestart ] && [ "$(/bin/cat /tmp/dhcprestart)" -eq "1" ]; then <servicecmd> restart && /bin/rm /tmp/dhcprestart; fi
* Replace <servicecmd> with your starting cmd
*   Linux Debian: service isc-dhcp-server restart
*   Other Linux: service dhcpd restart (to verify)
*   FreeBSD: service isc-dhcpd restart
*   OpenBSD: /etc/rc.d/dhcpd restart
"""

class DHCPManager(ZEyeUtil.Thread):
	ipList = {}
	subnetList = {}
	rangeList = {}
	clusterList = {}
	clusterMembers = {}
	clusterOptions = {}
	customOptsList = {}
	optsList = {}
	optgroupsList = {}
	subnetOptgroupsList = {}
	IPv4OptgroupsList = {}

	def __init__(self):
		""" 1 min between two DHCP updates """
		self.sleepingTimer = 60
		self.myName = "DHCP Manager"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.launchDHCPManagement()
			self.setRunning(False)

	def launchDHCPManagement(self):
		try:
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT addr,sshuser,sshpwd,reservconfpath,subnetconfpath FROM z_eye_dhcp_servers")
			pgres = pgcursor.fetchall()
			if pgcursor.rowcount > 0:
				# Buffer for better performances
				self.loadIPList(pgcursor)
				self.loadRangeList(pgcursor)
				self.loadSubnetList(pgcursor)
				self.loadClusterList(pgcursor)
				self.loadCustomOptsList(pgcursor)
				self.loadOptsList(pgcursor)
				self.loadOptgroupsList(pgcursor)
				self.loadSubnetOptions(pgcursor)
				self.loadIPv4Options(pgcursor)
				for idx in pgres:
					if len(idx[1]) > 0 and len(idx[2]) > 0 and len(idx[3]) > 0 and len(idx[4]) > 0:
						thread.start_new_thread(self.doConfigDHCP,(idx[0],idx[1],idx[2],idx[3],idx[4]))
		except Exception, e:
			self.logCritical(e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()
			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				time.sleep(1)

	def doConfigDHCP(self,addr,user,pwd,reservpath,subnetpath):
		self.incrThreadNb()

		subnetBuf = ""
		reservBuf = ""
		try:
			# One pgsql connection per thread
			pgsqlCon = PgSQL.connect(host=zConfig.pgHost,user=zConfig.pgUser,password=zConfig.pgPwd,database=zConfig.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT clustername FROM z_eye_dhcp_cluster WHERE dhcpaddr = '%s'" % addr)
			pgres = pgcursor.fetchall()

			# No cluster, then no action to do
			if pgcursor.rowcount == 0:
				self.decrThreadNb()
				return

			ssh = ZEyeSSHBroker(addr,user,pwd)
			if ssh.connect() == False:
				self.decrThreadNb()
				return

			# We get the remote OS for some commands
			remoteOs = ssh.getRemoteOS()
			if remoteOs != "Linux" and remoteOs != "FreeBSD" and remoteOs != "OpenBSD":
				self.logError("%s OS (on %s) is not supported" % (remoteOs,addr))
				self.decrThreadNb()
				return

			hashCmd = ""
			if remoteOs == "Linux":
				hashCmd = "md5sum"
			elif remoteOs == "FreeBSD" or remoteOs == "OpenBSD":
				hashCmd = "md5 -q"
		
			# We test file existence. If they doesn't exist, we create it. If creation failed, the DHCP manager cannot use this server
			if ssh.isRemoteExists(reservpath) == False:
				ssh.sendCmd("touch %s" % reservpath)

			if ssh.isRemoteWritable(reservpath) == False:
				self.logError("%s (on %s) is not writable" % (reservpath,addr))
				self.decrThreadNb()
				return
		
			if ssh.isRemoteExists(subnetpath) == False:
				ssh.sendCmd("touch %s" % subnetpath)

			if ssh.isRemoteWritable(subnetpath) == False:
				self.logError("%s (on %s) is not writable" % (subnetpath,addr))
				self.decrThreadNb()
				return
		
			if ssh.isRemoteExists("/tmp/dhcprestart") == False:
				ssh.sendCmd("touch %s" % "/tmp/dhcprestart")

			if ssh.isRemoteWritable("/tmp/dhcprestart") == False:
				self.logError("%s (on %s) is not writable" % ("/tmp/dhcprestart",addr))
				self.decrThreadNb()
				return
			
			"""
			This variable enable failover part
			With ISC DHCP failover can be declared only if used
			"""
			showFailover = False
			for idx in pgres:
				if idx[0] in self.clusterList:
					# custom options
					for cOpt in self.customOptsList:
						# Only real custom options are declared
						if self.customOptsList[cOpt][2] == False:
							codeType = ""
							if self.customOptsList[cOpt][1] == "uint8":
								codeType = "unsigned integer 8"
							elif self.customOptsList[cOpt][1] == "uint16":
								codeType = "unsigned integer 16"
							elif self.customOptsList[cOpt][1] == "uint32":
								codeType = "unsigned integer 32"
							elif self.customOptsList[cOpt][1] == "int8":
								codeType = "integer 8"
							elif self.customOptsList[cOpt][1] == "int16":
								codeType = "integer 16"
							elif self.customOptsList[cOpt][1] == "uint32":
								codeType = "integer 32"
							elif self.customOptsList[cOpt][1] == "ip":
								codeType = "ip-address"
							else:
								codeType = self.customOptsList[cOpt][1]
							subnetBuf += "option %s code %s = %s;\n" % (cOpt,self.customOptsList[cOpt][0],codeType)

					if len(subnetBuf) > 0:
						subnetBuf += "\n"

					# Cluster options 
					failoverPeerBuf = ""
					failoverPeerName = ""
					if idx[0] in self.clusterOptions:
						# ISC dhcp clusters
						if self.clusterOptions[idx[0]][0] == 1 or self.clusterOptions[idx[0]][0] == 2:
							peerAddr = ""
							"""
							cluster members has always 2 members on this configuration then,
							peer is the other record
							"""
							for peer in self.clusterMembers[idx[0]]:
								if peer != addr:
									peerAddr = peer

							failoverPeerBuf += "failover peer \"cluster-%s\" {" % idx[0].replace(' ','-')
							failoverPeerName = "cluster-%s" % idx[0].replace(' ','-')
							# This is for cluster master
							if addr == self.clusterOptions[idx[0]][1]:
								failoverPeerBuf += "\n\tprimary;"
							# This is for cluster slave
							else:
								failoverPeerBuf += "\n\tsecondary;"

							failoverPeerBuf += "\n\taddress %s;\n\tport 647;\n\tpeer address %s;\n\tpeer port 647;" % (addr,peerAddr)
							failoverPeerBuf += "\n\tmax-response-delay 3;\n\tmax-unacked-updates 2;\n\tload balance max seconds 10;"
							# This is for cluster master
							if (self.clusterOptions[idx[0]][0] == 1 or self.clusterOptions[idx[0]][0] == 2) and self.clusterOptions[idx[0]][1] == addr:
								if self.clusterOptions[idx[0]][0] == 2:
									failoverPeerBuf += "\n\tmclt 1800;\n\tsplit 127;"
								else:
									failoverPeerBuf += "\n\tmclt 1800;\n\tsplit 255;"

							failoverPeerBuf += "\n}\n\n"

					for subnet in self.clusterList[idx[0]]:
						netmask = self.subnetList[subnet][0]
						subnetIpList = self.subnetList[subnet][1]

						# Special case for DNS2
						dns2 = ""
						if len(self.subnetList[subnet][4]) > 0 and self.subnetList[subnet][4] != self.subnetList[subnet][3]:
							dns2 = ",%s" % self.subnetList[subnet][4]

						subnetBuf += "subnet %s netmask %s {\n\toption routers %s;\n\toption domain-name \"%s\";\n" % (subnet,netmask,self.subnetList[subnet][2],self.subnetList[subnet][5])

						# Set options values
						if subnet in self.subnetOptgroupsList:
							for options in self.subnetOptgroupsList[subnet]:
								# Text values must have braces and strip ' " ' char
								if self.customOptsList[options[0]][1] == "text":
									subnetBuf += "\toption %s \"%s\";\n" % (options[0],ZEyeUtil.addslashes(options[1]))
								else:
									if options[0] == "next-server":
										subnetBuf += "\t%s %s;\n" % (options[0],options[1])
									else:
										subnetBuf += "\toption %s %s;\n" % (options[0],options[1])

						if self.subnetList[subnet][6] != "" and self.subnetList[subnet][6] != 0:
							subnetBuf += "\tdefault-lease-time %s;\n" % self.subnetList[subnet][6]

						if self.subnetList[subnet][7] != "" and self.subnetList[subnet][7] != 0:
							subnetBuf += "\tmax-lease-time %s;\n" % self.subnetList[subnet][7]
			
						subnetBuf += "\toption domain-name-servers %s%s;\n\n" % (self.subnetList[subnet][3],dns2)
						
						# Now create pool with failover peer and ranges
						if subnet in self.rangeList:
							# Show this part only if we have rangelist
							if len(self.rangeList[subnet]) > 0:
								# Start pool brace
								subnetBuf += "\tpool {\n"

								# Show failover part and enable showFailover variable
								showFailover = True
								if len(failoverPeerName) > 0:
									subnetBuf += "\t\tfailover peer \"%s\";\n\n" % failoverPeerName

								for range in self.rangeList[subnet]:
									subnetBuf += "\t\trange %s %s;\n" % (range[0],range[1])

								subnetBuf += "\t}\n"
						subnetBuf += "}\n\n"


						for ip in subnetIpList:
							reservBuf += "host %s {\n\thardware ethernet %s;\n\tfixed-address %s;\n" % (self.ipList[ip][1],self.ipList[ip][0],ip)
							if ip in self.IPv4OptgroupsList:
								for options in self.IPv4OptgroupsList[ip]:
									# Text values must have braces and strip ' " ' char
									if self.customOptsList[options[0]][1] == "text":
										reservBuf += "\toption %s \"%s\";\n" % (options[0],ZEyeUtil.addslashes(options[1]))
									else:
										if options[0] == "next-server":
											reservBuf += "\t%s %s;\n" % (options[0],options[1])
										else:
											reservBuf += "\toption %s %s;\n" % (options[0],options[1])
							reservBuf += "}\n"
	
			
			if showFailover == True:
				subnetBuf = "%s%s" % (failoverPeerBuf,subnetBuf)

			# check md5 trace to see if subnet file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (subnetpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % subnetBuf])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (subnetBuf,subnetpath))
				ssh.sendCmd("echo 1 > /tmp/dhcprestart")
				self.logger.info("DHCP Manager: subnets modified on %s" % addr)
			
			# check md5 trace to see if reserv file is different
			tmpmd5 = ssh.sendCmd("cat %s|%s" % (reservpath,hashCmd))
			tmpmd52 = subprocess.check_output(["/sbin/md5","-qs","%s\n" % reservBuf])
			if tmpmd5 != tmpmd52:
				ssh.sendCmd("echo '%s' > %s" % (reservBuf,reservpath))
				ssh.sendCmd("echo 1 > /tmp/dhcprestart")
				self.logger.info("DHCP Manager: reservations modified on %s" % addr)

			ssh.close()
		except Exception, e:
			self.logger.critical("DHCP Manager: %s" % e)
		finally:
			self.decrThreadNb()

	def loadIPv4Options(self,pgcursor):
		self.IPv4OptgroupsList = {}
		tmpIPv4OptgroupsList = {}
		pgcursor.execute("SELECT ipaddr,optgroup FROM z_eye_dhcp_ipv4_optgroups")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] in self.ipList:
				if idx[0] not in self.IPv4OptgroupsList:
					self.IPv4OptgroupsList[idx[0]] = []
					tmpIPv4OptgroupsList[idx[0]] = []
				if idx[1] not in self.IPv4OptgroupsList[idx[0]]:
					tmpIPv4OptgroupsList[idx[0]].append(idx[1])
					
		for ip in tmpIPv4OptgroupsList:
			for optionlist in tmpIPv4OptgroupsList[ip]:
				for optalias in self.optgroupsList[optionlist]:
					self.IPv4OptgroupsList[ip].append((self.optsList[optalias][0],self.optsList[optalias][1]))

	def loadSubnetOptions(self,pgcursor):
		self.subnetOptgroupsList = {}
		tmpsubnetOptgroupsList = {}
		pgcursor.execute("SELECT netid,optgroup FROM z_eye_dhcp_subnet_optgroups")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] in self.subnetList:
				if idx[0] not in self.subnetOptgroupsList:
					self.subnetOptgroupsList[idx[0]] = []
					tmpsubnetOptgroupsList[idx[0]] = []
				if idx[1] not in self.subnetOptgroupsList[idx[0]]:
					tmpsubnetOptgroupsList[idx[0]].append(idx[1])
					
		for subnet in tmpsubnetOptgroupsList:
			for optionlist in tmpsubnetOptgroupsList[subnet]:
				for optalias in self.optgroupsList[optionlist]:
					self.subnetOptgroupsList[subnet].append((self.optsList[optalias][0],self.optsList[optalias][1]))

	def loadOptgroupsList(self,pgcursor):
		self.optgroupsList = {}
		pgcursor.execute("SELECT optgroup,optalias FROM z_eye_dhcp_option_group")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.optgroupsList:
				self.optgroupsList[idx[0]] = []
			if idx[1] not in self.optgroupsList[idx[0]]:
				self.optgroupsList[idx[0]].append(idx[1])

	def loadOptsList(self,pgcursor):
		self.optsList = {}
		pgcursor.execute("SELECT optalias,optname,optval FROM z_eye_dhcp_option")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			self.optsList[idx[0]] = (idx[1],idx[2])

	def loadCustomOptsList(self,pgcursor):
		self.customOptsList = {}
		pgcursor.execute("SELECT optname,optcode,opttype,protectrm FROM z_eye_dhcp_custom_option")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			self.customOptsList[idx[0]] = (idx[1],idx[2],idx[3])

	def loadIPList(self,pgcursor):
		self.ipList = {}
		pgcursor.execute("SELECT ip,macaddr,hostname FROM z_eye_dhcp_ip WHERE reserv = 't'")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# We need hostname and mac addr for a reservation
			if len(idx[1]) > 0 and len(idx[2]) > 0:	
				self.ipList[idx[0]] = (idx[1],idx[2])

	def loadRangeList(self,pgcursor):
		self.rangeList = {}
		pgcursor.execute("SELECT subnet,rangestart,rangestop FROM z_eye_dhcp_subnet_range")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.rangeList:
				self.rangeList[idx[0]] = []
			self.rangeList[idx[0]].append((idx[1],idx[2]))

	def loadClusterList(self,pgcursor):
		self.clusterList = {}
		pgcursor.execute("SELECT clustername,subnet FROM z_eye_dhcp_subnet_cluster")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			if idx[0] not in self.clusterList:
				# Init buffers
				self.clusterList[idx[0]] = []
				self.clusterMembers[idx[0]] = []
				self.clusterOptions[idx[0]] = []
				# Load cluster options when create the cluster buffer
				pgcursor.execute("SELECT clustermode,master FROM z_eye_dhcp_cluster_options WHERE clustername = '%s'" % idx[0])
				pgres2 = pgcursor.fetchone()
				if pgres2 != None:
					self.clusterOptions[idx[0]].append(pgres2[0])
					self.clusterOptions[idx[0]].append(pgres2[1])

				# Load cluster members
				pgcursor.execute("SELECT dhcpaddr FROM z_eye_dhcp_cluster WHERE clustername = '%s'" % idx[0])
				pgres2 = pgcursor.fetchall()
				for idx2 in pgres2:
					self.clusterMembers[idx[0]].append(idx2[0])

			if idx[1] not in self.clusterList[idx[0]] and idx[1] in self.subnetList:
				self.clusterList[idx[0]].append(idx[1])

	def loadSubnetList(self,pgcursor):
		self.subnetList = {}
		# We only load netid attached to clusters
		pgcursor.execute("SELECT netid,netmask,router,dns1,dns2,domainname,dleasetime,mleasetime FROM z_eye_dhcp_subnet_v4_declared WHERE netid in (SELECT subnet FROM z_eye_dhcp_subnet_cluster)")
		pgres = pgcursor.fetchall()
		for idx in pgres:
			# Those fields are required
			if len(idx[2]) > 0 and len(idx[3]) > 0 and len(idx[5]) > 0:
				ipList = []
				for ip in self.ipList:
					# If ip in subnet we add it to list
					if ip in Network("%s/%s" % (idx[0],idx[1])):
						ipList.append(ip)
				self.subnetList[idx[0]] = (idx[1],ipList,idx[2],idx[3],idx[4],idx[5],idx[6],idx[7])
				
class DHCPRadiusSyncer(ZEyeUtil.Thread):
	zeyeDB = None
	radiusList = {}
	subnetList = {}
	ipList = {}
	
	def __init__(self):
		""" 2 min between two syncs updates """
		self.sleepingTimer = 120
		self.myName = "DHCP/Radius Sync"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.syncDHCPAndRadius()
			self.setRunning(False)
	
	def syncDHCPAndRadius(self):
		try:
			self.zeyeDB = ZEyeSQLMgr()
			self.zeyeDB.initForZEye()
			pgres = self.zeyeDB.Select("z_eye_radius_dhcp_import","dbname,addr,port,groupname,dhcpsubnet")
			if self.zeyeDB.getRowCount() > 0:
				# Buffer for better performances
				self.loadRadiusList()
				self.loadIPList()
				self.loadSubnetList()
				for idx in pgres:
					if len(idx[0]) > 0 and len(idx[1]) > 0 and idx[2] != 0 and len(idx[3]) > 0 and len(idx[4]) > 0:
						thread.start_new_thread(self.doSyncDHCPRadius,(idx[0],idx[1],idx[2],idx[3],idx[4]))
		except Exception, e:
			self.logger.critical("DHCP/Radius Sync: %s" % e)
			sys.exit(1);
		finally:
			if self.zeyeDB != None:
				self.zeyeDB.close()
			# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
			time.sleep(1)
			while self.getThreadNb() > 0:
				self.logger.debug("DHCP/Radius Sync: waiting %d threads" % self.getThreadNb())
				time.sleep(1)

	def doSyncDHCPRadius(self,dbname,addr,port,groupname,subnet):
		self.incrThreadNb()
		
		# We test if Radius has been loaded
		if addr not in self.radiusList or port not in self.radiusList[addr] or dbname not in self.radiusList[addr][port]:
			self.logger.error("DHCP/Radius Sync: Warning %s cannot be sync with radius (%s:%s/%s). Radius not exists" % (subnet,addr,port,dbname))
			self.decrThreadNb()
			return
		
		dbtype = self.radiusList[addr][port][dbname][2]
		login = self.radiusList[addr][port][dbname][0]
		pwd = self.radiusList[addr][port][dbname][1]
		
		zdbMgr = ZEyeSQLMgr()
		
		try:
			self.logger.debug("DHCP/Radius Sync: sync subnet '%s' with '%s:%s/%s'" % (subnet,addr,port,dbname))
			if zdbMgr.initForZEye() == False:
				self.logger.error("DHCP/Radius Sync: unable to connect to Z-Eye database" % (addr,port,dbname))
				self.decrThreadNb()
				return
			
			radDBMgr = ZEyeSQLMgr()
			if radDBMgr.configAndTryConnect(dbtype,addr,port,dbname,login,pwd) == False:
				self.logger.error("DHCP/Radius Sync: unable to connect to %s:%s/%s" % (addr,port,dbname))
				self.decrThreadNb()
				return
			
			# We must get the MAX id to create the new entries
			maxId = radDBMgr.GetMax("radcheck","id")
			if maxId == None:
				maxId = 1
			
			for mac in self.subnetList[subnet]["mac"]:
				maxId += 1
				# We must improve it to don't create already existing entries
				radDBMgr.Delete("radusergroup","username = '%s'" % mac)
				radDBMgr.Delete("radcheck","username = '%s'" % mac)
				radDBMgr.Insert("radusergroup","username,groupname,priority","'%s','%s','0'" % (mac,groupname))
				radDBMgr.Insert("radcheck","id,username,attribute,op,value","'%s','%s','Auth-Type',':=','Accept'" % (maxId,mac))
				
			radDBMgr.Commit()			
			self.logger.debug("DHCP/Radius Sync: sync subnet '%s' with '%s:%s/%s' finished" % (subnet,addr,port,dbname))
			radDBMgr.close()
			zdbMgr.close()
		except Exception, e:
			self.logger.critical("DHCP/Radius Sync: doSyncDHCPRadius %s" % e)
		finally:
			self.decrThreadNb()
	
	def loadSubnetList(self):
		self.subnetList = {}
		
		# We load required subnets from cache and IPM
		pgres = self.zeyeDB.Select("z_eye_dhcp_subnet_cache","netid,netmask","netid in (SELECT dhcpsubnet FROM z_eye_radius_dhcp_import)")
		for idx in pgres:
			if idx[0] not in self.subnetList:
				self.subnetList[idx[0]] = {}
				self.subnetList[idx[0]]["mask"] = idx[1]
				self.subnetList[idx[0]]["mac"] = []
		
		pgres = self.zeyeDB.Select("z_eye_dhcp_subnet_v4_declared","netid,netmask","netid in (SELECT dhcpsubnet FROM z_eye_radius_dhcp_import)")
		for idx in pgres:
			if idx[0] not in self.subnetList:
				self.subnetList[idx[0]] = {}
				self.subnetList[idx[0]]["mask"] = idx[1]
				self.subnetList[idx[0]]["mac"] = []
		
		for subnet in self.subnetList:
			# Then we load all MAC addr informations (per subnet), from cache
			pgres = self.zeyeDB.Select("z_eye_dhcp_ip_cache","macaddr","netid = '%s'" % subnet)
			for idx in pgres:
				if idx[0] not in self.subnetList[subnet]["mac"]:
					fmtMac = re.sub(":","",idx[0])
					fmtMac = re.sub("-","",fmtMac)
					self.subnetList[subnet]["mac"].append(fmtMac.lower())
			
			for ip in self.ipList:
				try:
					if ip in Network("%s/%s" % (subnet,self.subnetList[subnet]["mask"])):
						if self.ipList[ip] not in self.subnetList[subnet]["mac"]:
							self.subnetList[subnet]["mac"].append(self.ipList[ip])
				except Exception, e:
					self.logger.error("DHCP/Radius Sync: loadSubnetList/Network %s" % e)
					
	def loadIPList(self):
		""" We load IPs from IPM """
		pgres = self.zeyeDB.Select("z_eye_dhcp_ip","ip,macaddr","reserv = 't'")
		for idx in pgres:
			fmtMac = re.sub(":","",idx[1])
			fmtMac = re.sub("-","",fmtMac)
			self.ipList[idx[0]] = fmtMac.lower()
	
	def loadRadiusList(self):
		self.radiusList = {}
		pgres = self.zeyeDB.Select("z_eye_radius_db_list","addr,port,dbname,login,pwd,dbtype")
		for idx in pgres:
			if idx[0] not in self.radiusList:
				self.radiusList[idx[0]] = {}
			if idx[1] not in self.radiusList[idx[0]]:
				self.radiusList[idx[0]][idx[1]] = {}
			if idx[5] == "pg" or idx[5] == "my":
				self.radiusList[idx[0]][idx[1]][idx[2]] = (idx[3],idx[4],idx[5])
			else:
				self.logger.error("DHCP/Radius Sync: %s is not a valid DB type (%s:%s/%s)" % (idx[0],idx[1],idx[2]))

class DHCPCleaner(ZEyeUtil.Thread):
	zeyeDB = None
	radiusList = {}
	subnetList = {}
	ipList = {}
	
	def __init__(self):
		""" 2 min between two syncs updates """
		self.sleepingTimer = 86400
		self.myName = "DHCP Cleaner"
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.launchMsg()
		while True:
			self.setRunning(True)
			self.cleanOldDHCPEntries()
			self.setRunning(False)
			
			# We launch cleanup tommorow at midnight
			tommorow = datetime.datetime.now().replace(hour=0,minute=0,second=0) + datetime.timedelta(days=1)
			tommorow_interval = int(tommorow.strftime('%s')) - int(datetime.datetime.now().strftime('%s'))
			time.sleep(tommorow_interval)
	
	def cleanOldDHCPEntries(self):
		try:
			self.zeyeDB = ZEyeSQLMgr()
			self.zeyeDB.initForZEye()
			# Cleanup old reservations
			self.zeyeDB.Delete("z_eye_dhcp_ip","expiration_date < now()")
			self.zeyeDB.Commit()
		except Exception, e:
			self.logger.critical("DHCP cleaner: %s" % e)
			sys.exit(1);
		finally:
			if self.zeyeDB != None:
				self.zeyeDB.close()
		
		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			self.logger.debug("DHCP cleaner: waiting %d threads" % self.getThreadNb())
			time.sleep(1)
