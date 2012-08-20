#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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

import datetime
import time
import os
import thread
from threading import Lock

tc_mutex = Lock()
threadCounter = 0
max_threads = 20

def zeye_log(text):
        logfile = open("/var/www/datas/logs/z_eye_collector.log","a")
        logfile.writelines("%s\n"  % text)
        logfile.close()

def refreshMRTG(filename,useless):
	global threadCounter
	try:
		tc_mutex.acquire()
		threadCounter += 1
		tc_mutex.release()
		cmd = "env LANG=C mrtg %s" % filename
		pipe = os.popen('{ ' + cmd + '; }', 'r')
		text = pipe.read()
		pipe.close()
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()
	except Exception, e:
		print "[FATAL] %s" % e
		zeye_log("[FATAL] %s" % e)
		tc_mutex.acquire()
		threadCounter = threadCounter - 1
		tc_mutex.release()

now = datetime.datetime.now()
print "[Z-Eye][mrtg-data-refresh] Start at: %s" % now.strftime("%Y-%m-%d %H:%M")
zeye_log("[Z-Eye][mrtg-data-refresh] Start at: %s" % now.strftime("%Y-%m-%d %H:%M"))

print "[Z-Eye][mrtg-data-refresh] Search datas in dir: %s" % os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/"
zeye_log("[Z-Eye][mrtg-data-refresh] Search datas in dir: %s" % os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/")
_dir = os.listdir(os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/");
for file in _dir:
	filename = os.path.dirname(os.path.abspath(__file__))+"/../datas/mrtg-config/"+file
	if(os.path.isfile(filename)):
		while threadCounter >= max_threads:
			print "Waiting for %d threads..." % threadCounter
			time.sleep(1)
        	thread.start_new_thread(refreshMRTG,(filename,0))

while threadCounter > 0:
	print "Waiting for %d threads..." % threadCounter
	time.sleep(1)
totaltime = datetime.datetime.now() - now
now = datetime.datetime.now()
print "[Z-Eye][mrtg-data-refresh] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime)
zeye_log("[Z-Eye][mrtg-data-refresh] End at: %s (Total time %s)" % (now.strftime("%Y-%m-%d %H:%M"), totaltime))
