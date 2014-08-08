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
	if request.method == "GET" and "vendor" in request.GET and "device" in request.GET and "mib" in request.GET:
		if request.GET["vendor"] == "cisco":
			SwitchObj = Cisco.CiscoSwitch()
			mib = request.GET["mib"]
			
			if SwitchObj.setDevice(request.GET["device"]) and mib in Cisco.Mibs:
				if "pid" in request.GET and SwitchObj.setPortId(request.GET["pid"]):
					# We don't call methods here, it's faster to use the dictionnary
					return HttpResponse(SwitchObj.snmpget(Cisco.Mibs[mib]))
				else:
					# Invalid the port ID
					SwitchObj.setPortId("")
					return HttpResponse(SwitchObj.snmpget(Cisco.Mibs[mib]))
					
	return HttpResponse(_('Err-Wrong-Request'))
		
def setPortMibValue(request):
	if request.method == "GET" and "vendor" in request.GET and "device" in request.GET and "mib" in request.GET and "value" in request.GET:
		if request.GET["vendor"] == "cisco":
			SwitchObj = Cisco.CiscoSwitch()
			mib = request.GET["mib"]
			
			if SwitchObj.setDevice(request.GET["device"]) and mib in Cisco.Mibs:
				if "pid" in request.GET and SwitchObj.setPortId(request.GET["pid"]):
					# We don't call methods here, it's faster to use the dictionnary
					return HttpResponse(SwitchObj.snmpset(Cisco.Mibs[mib],request.GET["value"]))
				else:
					# Invalid the port ID
					SwitchObj.setPortId("")
					return HttpResponse(SwitchObj.snmpset(Cisco.Mibs[mib],request.GET["value"]))
					
	return HttpResponse(_('Err-Wrong-Request'))
