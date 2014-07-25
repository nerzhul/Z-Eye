# -*- coding: utf-8 -*-

from django.views.generic import TemplateView

from datetime import date

class Footer(TemplateView):
	template_name="footer.html"
	
	def get_context_data(self, **kwargs):
		context = super(Footer, self).get_context_data(**kwargs)
		context.update({
			'year': date.today().year
		})
		return context

