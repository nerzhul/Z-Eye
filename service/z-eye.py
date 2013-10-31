#! /usr/local/bin/python2.7
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

import sys,time

from Logger import ZEyeLogger

import Daemon,logging

from MRTGCfgDiscoverer import ZEyeMRTGDiscoverer
from MRTGDataRefresh import ZEyeMRTGDataRefresher
from PeriodicCmd import ZEyePeriodicCmd
from PortIDCacher import ZEyeSwitchesPortIDCacher
from SwitchesBackup import ZEyeSwitchesBackup
from serviceManager import ZEyeServiceMgr
from DatabaseUpgrader import ZEyeDBUpgrade
from DHCPManager import ZEyeDHCPManager, ZEyeDHCPRadiusSyncer
from SNMPCommunityCacher import ZEyeSNMPCommCacher
import ZEyeDNS

class ZEyeDaemon(Daemon.Daemon):
	def run(self):
		SNMPcc = ZEyeSNMPCommCacher()
		SNMPcc.start()

		"""
		We wait 1 second to let SNMP caching start and block other SNMP using processes
		"""
		time.sleep(1)

		ZEyeMRTGDiscoverer(SNMPcc).start()
		ZEyeMRTGDataRefresher().start()
		ZEyePeriodicCmd(15*60,15,"Netdisco device discovery","perl netdisco -C /usr/local/etc/netdisco/netdisco.conf -R").start()
		ZEyePeriodicCmd(5*60,60,"Netdisco device MAC walk","perl netdisco -C /usr/local/etc/netdisco/netdisco.conf -m").start()
		ZEyePeriodicCmd(5*60,90,"Netdisco device ARP walk","perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -a").start()
		ZEyePeriodicCmd(15*60,1200,"Netdisco device netbios walk","perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -w").start()
		ZEyeSwitchesPortIDCacher(SNMPcc).start()
		ZEyeSwitchesBackup(SNMPcc).start()
		ZEyeServiceMgr().start()	

		ZEyeDHCPManager().start()
		ZEyeDHCPRadiusSyncer().start()
		ZEyeDNS.DNSManager().start()
		ZEyeDNS.RecordCollector().start()
		
		while True:
			time.sleep(1)

def usage():
	print "usage: %s start|stop|restart|status|updatedb" % sys.argv[0]

if __name__ == "__main__":
	# Init daemon
	daemon = ZEyeDaemon("/var/run/z-eye.pid")
	
	# Init logger
	zlogger = ZEyeLogger()
	zlogger.firstInit()
        
	if len(sys.argv) == 2:
		if 'start' == sys.argv[1]:
			print "Z-Eye daemon pre-start checks..."
			ZEyeDBUpgrade().checkAndDoUpgrade()
			print "Starting Z-Eye daemon"
			zlogger.write("Starting Z-Eye daemon",logging.INFO)
			daemon.start()
		elif 'stop' == sys.argv[1]:
			print "Stopping Z-Eye daemon"
			zlogger.write("Stopping Z-Eye daemon",logging.INFO)
			daemon.stop()
		elif 'restart' == sys.argv[1]:
			print "Restarting Z-Eye daemon"
			zlogger.write("Restarting Z-Eye daemon",logging.INFO)
			daemon.restart()
		elif 'updatedb' == sys.argv[1]:
			ZEyeDBUpgrade().checkAndDoUpgrade()
		elif 'status' == sys.argv[1]:
			daemon.status()
		else:
			print "Unknown arg"
			usage()
			sys.exit(1)
		sys.exit(0)
	else:
		usage()	
		sys.exit(1)
