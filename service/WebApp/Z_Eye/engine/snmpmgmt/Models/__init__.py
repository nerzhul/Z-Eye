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

from django.db import models

from django.utils.translation import ugettext_lazy as _

class Community(models.Model):
	name = models.CharField(verbose_name=_('snmp-community'),max_length=64)
	ro = models.BooleanField(verbose_name=_('Reading'))
	rw = models.BooleanField(verbose_name=_('Writing'))

	class Meta:
		app_label = "Z_Eye"
