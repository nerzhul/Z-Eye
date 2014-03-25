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

import sys, time, logging

import Daemon

from PeriodicCmd import ZEyePeriodicCmd
from PortIDCacher import ZEyeSwitchesPortIDCacher
from SwitchesBackup import ZEyeSwitchesBackup
from zServiceMgr import ServiceMgr
from DatabaseUpgrader import ZEyeDBUpgrade
from zDHCP import DHCPManager, DHCPRadiusSyncer, DHCPCleaner
from SNMPCommunityCacher import ZEyeSNMPCommCacher
from NetdiscoManager import ZEyeNetdiscoDataRefresher
from zDNS import DNSManager, RecordCollector
from zMRTG import MRTGDiscoverer, MRTGDataRefresher
import zConfig

class ZEyeDaemon(Daemon.Daemon):
	def run(self):
		SNMPcc = ZEyeSNMPCommCacher()
		SNMPcc.start()

		"""
		We wait 1 second to let SNMP caching start and block other SNMP using processes
		"""
		time.sleep(1)

		MRTGDiscoveryTask = MRTGDiscoverer(SNMPcc)
		MRTGDiscoveryTask.start()
		
		"""
		We wait 1 second to let MRTG discoverer start and block other SNMP using processes
		"""
		time.sleep(1)
		
		MRTGDataRefresher(MRTGDiscoveryTask).start()
		ZEyePeriodicCmd(15*60,40,"Netdisco device discovery","/usr/bin/perl /usr/local/bin/netdisco -C /usr/local/etc/netdisco/netdisco.conf -R").start()
		ZEyeSwitchesPortIDCacher(SNMPcc).start()
		ZEyeSwitchesBackup(SNMPcc).start()
		ServiceMgr().start()	
		ZEyeNetdiscoDataRefresher().start()

		DHCPCleaner().start()
		DHCPManager().start()
		DHCPRadiusSyncer().start()
			
		DNSManager().start()
		RecordCollector().start()

		while True:
			time.sleep(1)

def usage():
	print "usage: %s start|stop|restart|status|updatedb" % sys.argv[0]

if __name__ == "__main__":
	# Init daemon
	daemon = ZEyeDaemon("/var/run/z-eye.pid")
	
	# Init logger
	logger = logging.getLogger("Z-Eye")
	handler = logging.FileHandler("/var/log/z-eye.log")
	formatter = logging.Formatter('%(asctime)s [%(levelname)s] - %(message)s')
	handler.setFormatter(formatter)
	logger.addHandler(handler) 
	logger.setLevel(zConfig.logLevel)
        
	if len(sys.argv) == 2:
		if 'start' == sys.argv[1]:
			print "Z-Eye daemon pre-start checks..."
			ZEyeDBUpgrade().checkAndDoUpgrade()
			print "Starting Z-Eye daemon"
			logger.info("Starting Z-Eye daemon")
			
			if zConfig.daemon == True:
				daemon.start()
			else:
				daemon.run()
		elif 'stop' == sys.argv[1]:
			print "Stopping Z-Eye daemon"
			logger.info("Stopping Z-Eye daemon")
			daemon.stop()
		elif 'restart' == sys.argv[1]:
			print "Restarting Z-Eye daemon"
			logger.info("Restarting Z-Eye daemon")
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
