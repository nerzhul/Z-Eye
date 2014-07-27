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

from InterfaceManager.ModelForms import zModelForm

from django.shortcuts import render
from django.http import HttpResponse

from django.utils.translation import ugettext_lazy as _

from django.core.exceptions import ObjectDoesNotExist

import engine.snmpmgmt.Models

def showCommunity(request):
	if request.method == "GET":
		if "id" in request.GET:
			try:
				communityObj = engine.snmpmgmt.Models.Community.objects.get(pk=request.GET["id"])
			except ValueError:
				return HttpResponse(_('Err-Wrong-Request'))
			except ObjectDoesNotExist:
				return HttpResponse(_('Err-Object-Not-Exists'))
		else:
			communityObj = None

		form = CommunityForm(instance=communityObj)

		return render(request, "interface/forms/generic.html", {
			'form': form,
			'formaction': '?mod=18&act=1',
			'modificationlabel': _('Modification'),
			'submitname' : _('Save')
		})
	else:
		return ""

class CommunityForm(zModelForm):
	class Meta:
		model = engine.snmpmgmt.Models.Community
		fields = "__all__"

