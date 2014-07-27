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

from django import forms
from django.shortcuts import render
from django.utils.translation import ugettext_lazy as _

def community(request):
	if request.method == "GET":
		form = CommunityForm(request.GET)

		return render(request, "interface/forms/generic.html", {
			'form': form,
			'formaction': '?mod=18&act=1',
			'modificationlabel': _('Modification'),
			'submitname' : _('Save')
		})
	else:
		return ""

class CommunityForm(forms.Form):
	name = forms.CharField(label=_("snmp-community"),required=True,max_length=64)
	ro = forms.BooleanField(label=_("Reading"),required=False)
	rw = forms.BooleanField(label=_("Writing"),required=False)
