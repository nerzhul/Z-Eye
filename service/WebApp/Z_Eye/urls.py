# -*- coding: utf-8 -*-
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

from django.conf.urls import patterns, include, url

from django.contrib import admin
admin.autodiscover()

import views

import InterfaceManager
import engine.snmpmgmt.Forms
import engine.logs.Models.serviceLogs
import engine.Switches.API
import Common.SNMP

urlpatterns = patterns('',
    #url(r'^admin/', include(admin.site.urls)),
    url(r'^templates/footer', views.Footer.as_view(), name='footer'),
    url(r'^logs/service_logs', engine.logs.Models.serviceLogs.show),
    url(r'^snmpmgmt/forms/community', engine.snmpmgmt.Forms.showCommunity),
    url(r'^switches/api/snmp_value/get', engine.Switches.API.getPortMibValue),
    url(r'^switches/api/snmp_value/set', engine.Switches.API.setPortMibValue),
    url(r'^switches/api/get_snmp_mib', engine.Switches.API.getSNMPMib),
    url(r'^locale/get', InterfaceManager.get_locale),
)
