# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

from django.http import HttpResponse
from django.utils.translation import ugettext_lazy as _

# Temporary function to permit communication between Z-Eye PHP and Python locales
def get_locale(request):
	if request.method == "GET":
		if "locale" in request.GET:
			if len(request.GET["locale"]) > 0:
				return HttpResponse(_(request.GET["locale"]))
			else:
				return HttpResponse(_('Err-Wrong-Request'))
		else:
			return HttpResponse(_('Err-Wrong-Request'))
	else:
		return HttpResponse(_('Err-Wrong-Request'))

"""
TEMP CALLs to locales, before their migration to Django App, they need to be
referenced in a place. It seems here is a good idea
"""

_("Action")
_("Add")
_('Cancel')
_('comma')
_("Confirm")
_("confirm-disconnect")
_("Connection")
_("Connect-to")
_("CSV-content")
_("day")
_("days")
_("Default")
_("Description")
_("Disconnection")
_("Done")
_("English")
_("Error")
_("err-bad-datas")
_("err-devel")
_("err-devel-locale")
_("err-must-be-connected")
_("err-no-rights")
_("err-sql-query-failed")
_("err-unk-module")
_("French")
_("hour")
_("hours")
_("Import")
_("Loading")
_("minute")
_("minutes")
_("Modify")
_("Name")
_("No")
_("None")
_("Notification")
_("OK")
_("Online")
_("Remove")
_("Replace-?")
_("rule-read-datas")
_("rule-write-datas")
_("Save")
_("Searching...")
_("second")
_("seconds")
_("semi-colon")
_("separator")
_("Settings")
_("Type")
_("unknown")
_('Yes')
