# -*- coding: utf-8 -*-
"""
* Copyright (C) 2010-2014 Loic BLOT, UNIX-Experience <http://www.unix-experience.fr/>
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

from django.views.generic import TemplateView
from django.utils.translation import ugettext_lazy as _


from django.shortcuts import render

from datetime import date

class Footer(TemplateView):
	template_name="footer.html"

	def get_name(self, request):
		return render(request, "footer.html", {'form': form })

	def get_context_data(self, **kwargs):
		context = super(Footer, self).get_context_data(**kwargs)
		context.update({
			'year': date.today().year
		})
		return context
