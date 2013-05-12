#! /usr/local/bin/python
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

import Logger

import Daemon
import MRTGCfgDiscoverer
import MRTGDataRefresh
import PortIDCacher
import SwitchesBackup
import serviceManager

class ZEyeDaemon(Daemon.Daemon):
	def run(self):
		MRTGCfgDiscoverer.ZEyeMRTGDiscoverer().start()
		MRTGDataRefresh.ZEyeMRTGDataRefresher().start()
		PortIDCacher.ZEyeSwitchesPortIDCacher().start()
		SwitchesBackup.ZEyeSwitchesBackup().start()
		serviceManager.ZEyeServiceMgr().start()	
		while True:
			time.sleep(1)

def usage():
	print "usage: %s start|stop|restart" % sys.argv[0]

if __name__ == "__main__":
        daemon = ZEyeDaemon("/var/run/z-eye.pid")
        if len(sys.argv) == 2:
                if 'start' == sys.argv[1]:
			print "Starting Z-Eye daemon"
			Logger.ZEyeLogger().write("Starting Z-Eye daemon")
                        daemon.start()
                elif 'stop' == sys.argv[1]:
			print "Stopping Z-Eye daemon"
			Logger.ZEyeLogger().write("Stopping Z-Eye daemon")
                        daemon.stop()
                elif 'restart' == sys.argv[1]:
			print "Restarting Z-Eye daemon"
                        daemon.restart()
                else:
			print "Unknown arg"
			usage()
                        sys.exit(1)
                sys.exit(0)
        else:
		usage()	
                sys.exit(1)
