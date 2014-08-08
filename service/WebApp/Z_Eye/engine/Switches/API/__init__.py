# -*- coding: utf-8 -*-
"""
* Copyright (C) 2010-2014 Loic BLOT <http://www.unix-experience.fr/>
*
* This program is free software you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
"""

import json
from django.shortcuts import render
from django.http import HttpResponse
from django.utils.translation import ugettext_lazy as _

import Cisco

def getSNMPMib(request):
	if request.method == "GET" and "mib" in request.GET and "vendor" in request.GET:
		# Only Cisco is supported at this time
		if request.GET["vendor"] == "cisco" and request.GET["mib"] in Cisco.Mibs:
			return HttpResponse(json.dumps(Cisco.Mibs[request.GET["mib"]]), content_type="application/json")
				
	return HttpResponse(_('Err-Wrong-Request'))
	
def getPortMibValue(request):
	if request.method == "GET" and "vendor" in request.GET and "device" in request.GET and "pid" in request.GET and "mib" in request.GET:
		if request.GET["vendor"] == "cisco":
			SwitchObj = Cisco.CiscoSwitch()
			mib = request.GET["mib"]
			
			if SwitchObj.setDevice(request.GET["device"]) and SwitchObj.setPortId(request.GET["pid"]) and mib in Cisco.Mibs:
				if mib == "cdp_enable":
					return HttpResponse(SwitchObj.getPortCDPEnable())
				elif mib == "port_description":
					return HttpResponse(SwitchObj.getPortDesc())
				elif mib == "port_enable":
					return HttpResponse(SwitchObj.getPortState())
					
	return HttpResponse(_('Err-Wrong-Request'))
		
