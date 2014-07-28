from django.conf.urls import patterns, include, url

from django.contrib import admin
admin.autodiscover()

import views

import InterfaceManager
import engine.snmpmgmt.Forms

urlpatterns = patterns('',
    #url(r'^admin/', include(admin.site.urls)),
    url(r'^templates/footer', views.Footer.as_view(), name='footer'),
    url(r'^snmpmgmt/forms/community', engine.snmpmgmt.Forms.showCommunity),
    url(r'^locale/get', InterfaceManager.get_locale),
)
