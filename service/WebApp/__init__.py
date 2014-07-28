# -*- Coding: utf-8 -*-

"""
* Copyright (C) 2010-2014 Loic BLOT <http://www.unix-experience.fr/>
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

import os, sys, re, time, subprocess

sys.path.append("%s/%s" % (os.path.dirname(os.path.abspath(__file__)),"../"))

import ZEyeUtil

class zWebApp(ZEyeUtil.Thread):
	def __init__(self):
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.logger.info("Web App launched")
		while True:
			self.launchWebApp()
			time.sleep(1)

	def launchWebApp(self):
		self.logger.info("Web App started")
		try:
			cmd = "/usr/local/bin/python %s/manage.py runserver 0.0.0.0:8080" % os.path.dirname(os.path.abspath(__file__))
			subprocess.check_output(cmd,shell=True)
		except Exception, e:
			self.logger.critical("Web App: %s" % e)
		self.logger.info("Web App ended")
