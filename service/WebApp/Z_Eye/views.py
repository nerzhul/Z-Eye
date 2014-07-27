# -*- coding: utf-8 -*-

from django.views.generic import TemplateView
from django.utils.translation import ugettext_lazy as _


from django.shortcuts import render

from datetime import date

class Footer(TemplateView):
	template_name="footer.html"

	def get_name(self, request):
		return render(request, "footer.html", {'form': form})

	def get_context_data(self, **kwargs):
		context = super(Footer, self).get_context_data(**kwargs)
		context.update({
			'year': date.today().year
		})
		return context
