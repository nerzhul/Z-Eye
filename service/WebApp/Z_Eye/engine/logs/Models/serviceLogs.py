# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2014 Loïc BLOT <http://www.unix-experience.fr/>
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

import os, re
from django.shortcuts import render
from django.http import HttpResponse
from django.utils.translation import ugettext_lazy as _

LOGFILE = "/var/log/z-eye.log"

def show(request):
	if request.method == "GET":
		linecount = 30;
		
		# Override log number shown
		if "max" in request.GET:
			try:
				linecount = int(request.GET["max"]);
			except ValueError:
				return HttpResponse(_('Err-Wrong-Request'))
		
		fileOutput = []
		with open(LOGFILE) as fd:
			linebuf = ""
			
			fd.seek(0, os.SEEK_END)

			while fd.tell() >0 and linecount > 0:
				ch = fd.read(1);
				if ch == "\n":
					logSplit = re.split(" ",linebuf)
					# Log entries must have greater than 5 fields
					if len(logSplit) > 5:
						lineObj = {
							"date": "%s %s" % (logSplit[0], logSplit[1]),
							"level": re.sub("\[|\]", "", logSplit[2]),
							"entry": ""
						}
						
						# Offset 4: there is a dash into the logfile
						for i in range(4,len(logSplit)):
							lineObj["entry"] += " %s" % logSplit[i]
							
						fileOutput += [lineObj,];
					linebuf = "";
					linecount -= 1
				else:
					linebuf = ch + linebuf
				
				fd.seek(-2, os.SEEK_CUR);
		
		if len(fileOutput) == 0:
			fileOutput = None
		return render(request, "engine/logs/serviceLogs.html", {
			'logs': fileOutput
		})
	else:
		return HttpResponse(_('Err-Wrong-Request'))
