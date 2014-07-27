from django.conf.urls import patterns, include, url

from django.contrib import admin
admin.autodiscover()

import views

import engine.snmpmgmt.Forms

urlpatterns = patterns('',
    # Examples:
    # url(r'^$', 'Z_Eye.views.home', name='home'),
    # url(r'^blog/', include('blog.urls')),

    #url(r'^admin/', include(admin.site.urls)),
    url(r'^templates/footer', views.Footer.as_view(), name='footer'),
    url(r'^snmpmgmt/forms/community', engine.snmpmgmt.Forms.showCommunity),
)
