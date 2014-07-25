# -*- coding: utf-8 -*-

from django.views.generic import TemplateView
from django.utils.translation import ugettext_lazy as _


from django.shortcuts import render

from datetime import date

from django import forms
class TestForm(forms.Form):
	def __init__(self, *args, **kwargs):
		return super(TestForm, self).__init__(*args,**kwargs)

	machin = forms.CharField(label="TEST",max_length=10)

def renderTestForm(request):
	return render(request, 'test.html', {'form': TestForm()})

class Footer(TemplateView):
	template_name="footer.html"
	form_class = TestForm

	def get_name(self, request):
		form = TestForm()
		return render(request, "footer.html", {'form': form})
	
	def get_context_data(self, **kwargs):
		context = super(Footer, self).get_context_data(**kwargs)
		context.update({
			'year': date.today().year
		})
		return context
